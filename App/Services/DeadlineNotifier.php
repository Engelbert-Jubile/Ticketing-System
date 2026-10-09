<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Support\SlaClock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeadlineNotifier
{
    public function run(): int
    {
        $notifier = app(WorkItemNotifier::class);
        $count = 0;
        foreach (['ticket' => Ticket::class, 'task' => Task::class, 'project' => Project::class] as $type => $model) {
            $model::query()->whereNotIn('status', ['done', 'completed', 'complete', 'closed', 'cancelled', 'cancel', 'canc'])
                ->chunkById(100, function ($items) use ($type, $notifier, &$count) {
                    foreach ($items as $item) {
                        [$deadline] = SlaClock::dates($item, $type);
                        if (! $deadline || $deadline->greaterThan(now()->addDay())) {
                            continue;
                        }
                        $late = $deadline->isPast();
                        $event = $late ? 'deadline_overdue' : 'deadline_due';
                        $recipients = $notifier->{$type.'Recipients'}($item);
                        $locale = config('app.locale', 'id');
                        $url = match ($type) {
                            'task' => route('tasks.show', ['locale' => $locale, 'taskSlug' => $item->public_slug]),
                            'project' => route('projects.show', ['locale' => $locale, 'project' => $item->public_slug]),
                            default => route('tickets.show', ['locale' => $locale, 'ticket' => $item->id]),
                        };
                        foreach ($recipients as $user) {
                            $scope = match ($type) {
                                'task' => \App\Support\UnitVisibility::scopeTasks(Task::query(), $user),
                                'project' => \App\Support\UnitVisibility::scopeProjects(Project::query(), $user),
                                default => \App\Support\UnitVisibility::scopeTickets(Ticket::query(), $user),
                            };
                            if (! $scope->whereKey($item->id)->exists()) {
                                continue;
                            }
                            $hash = substr(hash('sha256', implode(':', [$type, $item->id, $user->id, $event, $deadline->toDateTimeString(), $late ? now()->toDateString() : 'once'])), 0, 32);
                            $id = substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20);
                            $count += DB::table('notifications')->insertOrIgnore([
                                'id' => $id, 'type' => self::class, 'notifiable_type' => $user->getMorphClass(), 'notifiable_id' => $user->id,
                                'data' => json_encode(['title' => $late ? 'Pekerjaan melewati deadline' : 'Deadline dalam 24 jam', 'message' => $item->title.' · '.$deadline->format('d M Y H:i'), 'url' => $url, 'icon' => 'schedule', 'event' => $event, 'subject_type' => $type, 'subject_id' => $item->id]),
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }
                    }
                });
        }
        Cache::put('health:deadline-notifier:last-success', now()->toIso8601String(), now()->addDays(7));

        return $count;
    }
}
