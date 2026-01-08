<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ReminderLog;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Support\WorkflowStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReminderService
{
    public function __construct(private readonly WorkItemNotifier $notifier) {}

    public function run(): void
    {
        $now = Carbon::now();

        $this->processTickets($now);
        $this->processTasks($now);
        $this->processProjects($now);
    }

    private function processTickets(Carbon $now): void
    {
        $statusScope = $this->reminderStatuses();

        Ticket::query()
            ->whereIn('status', $statusScope)
            ->with(['assignedUsers:id,first_name,last_name,username,email', 'requester:id,first_name,last_name,username,email', 'agent:id,first_name,last_name,username,email'])
            ->chunkById(50, function ($tickets) use ($now) {
                foreach ($tickets as $ticket) {
                    if (! $this->hasActiveDeadline($ticket, $now)) {
                        continue;
                    }

                    $recipients = $this->notifier->ticketRecipients($ticket);
                    $eligible = $this->filterEligibleRecipients(
                        $recipients,
                        'ticket',
                        $ticket->id,
                        $ticket->created_at ?? $ticket->updated_at ?? $now,
                        $now
                    );

                    if ($eligible->isEmpty()) {
                        continue;
                    }

                    $this->notifier->notifyTicketReminder($ticket, $eligible);
                    $this->logRecipients($eligible, 'ticket', $ticket->id, $now);
                }
            });
    }

    private function processTasks(Carbon $now): void
    {
        $statusScope = $this->reminderStatuses();

        Task::query()
            ->whereIn('status', $statusScope)
            ->with([
                'ticket.assignedUsers:id,first_name,last_name,username,email',
                'ticket.requester:id,first_name,last_name,username,email',
                'project.pics',
                'requester:id,first_name,last_name,username,email',
            ])
            ->chunkById(50, function ($tasks) use ($now) {
                foreach ($tasks as $task) {
                    if (! $this->hasActiveDeadline($task, $now)) {
                        continue;
                    }

                    $recipients = $this->notifier->taskRecipients($task);
                    $eligible = $this->filterEligibleRecipients(
                        $recipients,
                        'task',
                        $task->id,
                        $task->created_at ?? $task->updated_at ?? $now,
                        $now
                    );

                    if ($eligible->isEmpty()) {
                        continue;
                    }

                    $this->notifier->notifyTaskReminder($task, $eligible);
                    $this->logRecipients($eligible, 'task', $task->id, $now);
                }
            });
    }

    private function processProjects(Carbon $now): void
    {
        $statusScope = $this->reminderStatuses();

        Project::query()
            ->whereIn('status', $statusScope)
            ->with([
                'pics.user:id,first_name,last_name,username,email',
                'ticket.assignedUsers:id,first_name,last_name,username,email',
                'ticket.requester:id,first_name,last_name,username,email',
            ])
            ->chunkById(50, function ($projects) use ($now) {
                foreach ($projects as $project) {
                    if (! $this->hasActiveDeadline($project, $now)) {
                        continue;
                    }

                    $recipients = $this->notifier->projectRecipients($project);
                    $eligible = $this->filterEligibleRecipients(
                        $recipients,
                        'project',
                        $project->id,
                        $project->created_at ?? $project->updated_at ?? $now,
                        $now
                    );

                    if ($eligible->isEmpty()) {
                        continue;
                    }

                    $this->notifier->notifyProjectReminder($project, $eligible);
                    $this->logRecipients($eligible, 'project', $project->id, $now);
                }
            });
    }

    private function filterEligibleRecipients(Collection $users, string $type, int $itemId, Carbon $baseline, Carbon $now): Collection
    {
        if ($users->isEmpty()) {
            return collect();
        }

        $userIds = $users->pluck('id')->all();

        $logs = ReminderLog::query()
            ->where('item_type', $type)
            ->where('item_id', $itemId)
            ->whereIn('user_id', $userIds)
            ->orderByDesc('sent_at')
            ->get()
            ->groupBy('user_id');

        return $users->filter(function (User $user) use ($logs, $baseline, $now, $itemId) {
            $history = $logs->get($user->id) ?? collect();

            /** @var ReminderLog|null $last */
            $last = $history->first();

            if ($last && $last->sent_at && $last->sent_at->isSameDay($now)) {
                return false;
            }

            $weeklyCount = $history
                ->filter(fn (ReminderLog $log) => $log->sent_at && $log->sent_at->isSameWeek($now))
                ->count();

            if ($weeklyCount >= 2) {
                return false;
            }

            $anchor = $last?->sent_at ?? $baseline;
            if (! $anchor instanceof Carbon) {
                $anchor = Carbon::parse($anchor ?? $now->copy()->subDays(6));
            }

            $days = $anchor->diffInDays($now);
            $threshold = $this->randomizedThreshold($itemId, $user->id);

            return $days >= $threshold;
        })->values();
    }

    private function logRecipients(Collection $users, string $type, int $itemId, Carbon $now): void
    {
        foreach ($users as $user) {
            ReminderLog::create([
                'user_id' => $user->id,
                'item_type' => $type,
                'item_id' => $itemId,
                'event' => 'reminder',
                'sent_at' => $now,
            ]);
        }
    }

    private function reminderStatuses(): array
    {
        return array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION),
            WorkflowStatus::equivalents(WorkflowStatus::REVISION)
        ));
    }

    private function hasActiveDeadline(Ticket|Task|Project $item, Carbon $now): bool
    {
        $deadline = null;

        if ($item instanceof Ticket) {
            $deadline = $item->due_at ?? $item->due_date ?? $item->finish_date;
        } elseif ($item instanceof Task) {
            $deadline = $item->due_at ?? $item->due_date ?? $item->end_date;
        } else {
            $deadline = $item->end_date ?? $item->due_at ?? $item->due_date;
        }

        if (! $deadline) {
            return true;
        }

        try {
            $deadlineDate = $deadline instanceof Carbon ? $deadline->copy() : Carbon::parse($deadline);
        } catch (\Throwable) {
            return true;
        }

        return $deadlineDate->isFuture() || $deadlineDate->isSameDay($now);
    }

    private function randomizedThreshold(int $itemId, int $userId): int
    {
        $minDays = 3;
        $maxDays = 5;

        try {
            return random_int($minDays, $maxDays);
        } catch (\Throwable) {
            $seed = abs(crc32($itemId.'-'.$userId.'-'.microtime(true)));

            return $minDays + ($seed % ($maxDays - $minDays + 1));
        }
    }
}
