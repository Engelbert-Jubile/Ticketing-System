<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPic;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\WorkItemNotification;
use App\Support\WorkflowStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class WorkItemNotifier
{
    private string $tz;

    public function __construct()
    {
        $this->tz = config('app.timezone') ?: 'UTC';
    }

    public function notifyTicketCreated(Ticket $ticket, ?User $actor = null): void
    {
        $ticket->loadMissing('assignedUsers:id,first_name,last_name,username,email', 'requester:id,first_name,last_name,username,email', 'agent:id,first_name,last_name,username,email');

        $recipients = $this->collectTicketRecipients($ticket, $actor);
        if ($recipients->isEmpty()) {
            return;
        }

        $payload = $this->buildTicketPayload($ticket, 'created', $actor);
        $this->dispatch($recipients, 'created', $payload, $actor);
    }

    public function notifyTicketReminder(Ticket $ticket, Collection $recipients, ?User $actor = null): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        $payload = $this->buildTicketPayload($ticket, 'reminder', $actor);
        $this->dispatch($recipients, 'reminder', $payload, $actor);
    }

    public function notifyTicketCancelled(Ticket $ticket, ?User $actor = null): void
    {
        $ticket->loadMissing(
            'assignedUsers:id,first_name,last_name,username,email',
            'requester:id,first_name,last_name,username,email',
            'agent:id,first_name,last_name,username,email',
        );

        $recipients = $this->collectTicketRecipients($ticket, $actor);
        if ($recipients->isEmpty()) {
            return;
        }

        $payload = $this->buildTicketPayload($ticket, 'cancelled', $actor);
        $this->dispatch($recipients, 'cancelled', $payload, $actor);
    }

    public function notifyTicketAssigned(Ticket $ticket, array $userIds, ?User $actor = null): void
    {
        if (empty($userIds)) {
            return;
        }

        $users = User::whereIn('id', array_unique($userIds))->get();
        if ($users->isEmpty()) {
            return;
        }

        $ticket->loadMissing('requester:id,first_name,last_name,username,email');
        $payload = $this->buildTicketPayload($ticket, 'assigned', $actor);
        $this->dispatch($users, 'assigned', $payload, $actor);
    }

    public function notifyTaskCreated(Task $task, array $assigneeIds = [], ?User $actor = null, bool $sendMail = true): void
    {
        $task->loadMissing(
            'requester:id,first_name,last_name,username,email',
        );

        $recipients = $this->collectTaskRecipients($task, $assigneeIds, $actor);
        if ($recipients->isEmpty()) {
            return;
        }

        $payload = $this->buildTaskPayload($task, 'created', $actor);
        $this->dispatch($recipients, 'created', $payload, $actor, $sendMail);
    }

    public function notifyTaskAssigned(Task $task, array $userIds, ?User $actor = null): void
    {
        if (empty($userIds)) {
            return;
        }

        $users = User::whereIn('id', array_unique($userIds))->get();
        if ($users->isEmpty()) {
            return;
        }

        $task->loadMissing(
        );
        $payload = $this->buildTaskPayload($task, 'assigned', $actor);
        $this->dispatch($users, 'assigned', $payload, $actor);
    }

    public function notifyTaskReminder(Task $task, Collection $recipients, ?User $actor = null): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        $payload = $this->buildTaskPayload($task, 'reminder', $actor);
        $this->dispatch($recipients, 'reminder', $payload, $actor);
    }

    public function notifyTaskCancelled(Task $task, ?User $actor = null): void
    {
        $task->loadMissing(
            'requester:id,first_name,last_name,username,email',
        );

        $recipients = $this->collectTaskRecipients($task, [], $actor);
        if ($recipients->isEmpty()) {
            return;
        }

        $payload = $this->buildTaskPayload($task, 'cancelled', $actor);
        $this->dispatch($recipients, 'cancelled', $payload, $actor);
    }

    public function notifyProjectCreated(Project $project, ?User $actor = null, bool $sendMail = true): void
    {
        $project->loadMissing(
            'pics.user:id,first_name,last_name,username,email',
            'requester:id,first_name,last_name,username,email'
        );

        $recipients = $this->collectProjectRecipients($project, $actor);
        if ($recipients->isEmpty()) {
            return;
        }

        $payload = $this->buildProjectPayload($project, 'created', $actor);
        $this->dispatch($recipients, 'created', $payload, $actor, $sendMail);
    }

    public function notifyProjectReminder(Project $project, Collection $recipients, ?User $actor = null): void
    {
        if ($recipients->isEmpty()) {
            return;
        }

        $payload = $this->buildProjectPayload($project, 'reminder', $actor);
        $this->dispatch($recipients, 'reminder', $payload, $actor);
    }

    public function notifyProjectCancelled(Project $project, ?User $actor = null): void
    {
        $project->loadMissing(
            'pics.user:id,first_name,last_name,username,email',
            'requester:id,first_name,last_name,username,email'
        );

        $recipients = $this->collectProjectRecipients($project, $actor);
        if ($recipients->isEmpty()) {
            return;
        }

        $payload = $this->buildProjectPayload($project, 'cancelled', $actor);
        $this->dispatch($recipients, 'cancelled', $payload, $actor);
    }

    public function notifyPasswordChanged(User $user, ?User $actor = null): void
    {
        $payload = $this->buildAccountPayload($user, 'password_changed', $actor);
        $this->dispatch(collect([$user]), 'password_changed', $payload, $actor);
    }

    public function ticketRecipients(Ticket $ticket): Collection
    {
        return $this->collectTicketRecipients($ticket);
    }

    public function taskRecipients(Task $task): Collection
    {
        return $this->collectTaskRecipients($task);
    }

    public function projectRecipients(Project $project): Collection
    {
        return $this->collectProjectRecipients($project);
    }

    /**
     * Dispatch notification to recipients.
     *
     * @param  Collection<int,User>  $recipients
     * @param  array<string,mixed>  $payload
     */
    private function dispatch(Collection $recipients, string $event, array $payload, ?User $actor = null, bool $sendMail = true): void
    {
        $actorName = $actor?->display_name ?? $actor?->name ?? null;
        $actorUnit = $actor?->unit ? trim((string) $actor->unit) : null;

        foreach ($recipients->unique('id') as $user) {
            $allowMail = $sendMail && $this->shouldSendMail($user);
            $data = $payload;
            $data['actor'] = $actorName;
            $data['actor_unit'] = $actorUnit;

            try {
                if (method_exists($user, 'notifyNow')) {
                    $user->notifyNow(new WorkItemNotification($event, $data, $allowMail));
                } else {
                    $user->notify(new WorkItemNotification($event, $data, $allowMail));
                }
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    private function shouldSendMail(User $user): bool
    {
        $email = strtolower((string) $user->email);
        if ($email === '') {
            return false;
        }

        $rawDomains = config('services.workitems.mail_domains', '*');
        $domains = is_array($rawDomains)
            ? $rawDomains
            : preg_split('/[\s,;|]+/', (string) $rawDomains, -1, PREG_SPLIT_NO_EMPTY);

        $domains = collect($domains)
            ->map(fn($value) => ltrim(strtolower(trim((string) $value))))
            ->filter()
            ->values();

        if ($domains->isEmpty() || $domains->contains('*')) {
            return true;
        }

        foreach ($domains as $domain) {
            $domain = ltrim($domain, '@');
            if ($domain === '') {
                continue;
            }

            if (Str::endsWith($email, '@' . $domain)) {
                return true;
            }
        }

        return false;
    }

    private function collectTicketRecipients(Ticket $ticket, ?User $actor = null): Collection
    {
        $ids = [];

        foreach ($ticket->assignedUsers ?? [] as $user) {
            $ids[] = $user->id;
        }

        if ($ticket->assigned_id) {
            $ids[] = (int) $ticket->assigned_id;
        }

        if ($ticket->requester) {
            $ids[] = $ticket->requester->id;
        }

        if ($ticket->agent) {
            $ids[] = $ticket->agent->id;
        }

        if ($actor && $actor->id) {
            $ids[] = (int) $actor->id;
        }

        return User::whereIn('id', array_unique($ids))->get();
    }

    private function collectTaskRecipients(Task $task, array $assigneeIds = [], ?User $actor = null): Collection
    {
        $ids = [];

        if ($task->assignee_id) {
            $ids[] = (int) $task->assignee_id;
        }

        $fromJson = $this->decodeJsonIds($task->assigned_to ?? null);
        $ids = array_merge($ids, $fromJson, $assigneeIds);

        if ($task->created_by) {
            $ids[] = (int) $task->created_by;
        }

        $rawRequesterId = $task->getAttribute('requester_id');
        if ($rawRequesterId) {
            $ids[] = (int) $rawRequesterId;
        }

        if ($task->requester) {
            $ids[] = $task->requester->id;
        }


        if ($actor && $actor->id) {
            $ids[] = (int) $actor->id;
        }

        return User::whereIn('id', array_unique($ids))->get();
    }

    private function collectProjectRecipients(Project $project, ?User $actor = null): Collection
    {
        $ids = [];

        foreach ($project->pics as $pic) {
            if ($pic instanceof ProjectPic && $pic->user_id) {
                $ids[] = (int) $pic->user_id;
            }
        }

        if ($project->created_by) {
            $ids[] = (int) $project->created_by;
        }

        $rawRequesterId = $project->getAttribute('requester_id');
        if ($rawRequesterId) {
            $ids[] = (int) $rawRequesterId;
        }

        if ($project->requester) {
            $ids[] = (int) $project->requester->id;
        }


        if ($actor && $actor->id) {
            $ids[] = (int) $actor->id;
        }

        return User::whereIn('id', array_unique($ids))->get();
    }

    /**
     * @return array<int>
     */
    private function decodeJsonIds(?string $value): array
    {
        if (! $value) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $decoded), fn($v) => $v > 0)));
    }

    /**
     * @return array<string,mixed>
     */
    private function buildTicketPayload(Ticket $ticket, string $event, ?User $actor = null): array
    {
        $status = WorkflowStatus::label(WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW));
        $priority = ucfirst(strtolower((string) ($ticket->priority ?? 'normal')));

        $details = [
            'Judul' => $ticket->title,
            'Nomor Ticket' => $ticket->ticket_no ?? '—',
            'Status' => $status,
            'Prioritas' => $priority,
            'Jatuh Tempo' => $this->formatDateTime($ticket->due_at ?? $ticket->due_date),
        ];

        if ($ticket->requester) {
            $details['Requester'] = $ticket->requester->display_name;
        }
        if ($ticket->agent) {
            $details['Agent'] = $ticket->agent->display_name;
        }
        $ticketPics = $ticket->assignedUsers
            ? $ticket->assignedUsers
                ->map(fn($user) => $user->display_name)
                ->filter()
                ->unique()
                ->values()
                ->all()
            : [];
        if (! empty($ticketPics)) {
            $details['PIC'] = implode(', ', $ticketPics);
        } else {
            $details['PIC'] = '-';
        }

        $intro = match ($event) {
            'assigned' => 'Anda baru saja ditetapkan sebagai penanggung jawab ticket berikut.',
            'reminder' => 'Ini adalah pengingat otomatis untuk ticket berikut.',
            'cancelled' => 'Ticket berikut telah dibatalkan dan tidak memerlukan tindak lanjut lanjutan.',
            default => 'Ticket berikut telah dibuat dan memerlukan perhatian Anda.',
        };

        $dbTitle = match ($event) {
            'assigned' => 'Ticket Assigned',
            'reminder' => 'Ticket Reminder',
            'cancelled' => 'Ticket Cancelled',
            default => 'Ticket Baru',
        };

        $dbMessage = match ($event) {
            'assigned' => 'Anda ditugaskan pada ticket: ' . $ticket->title,
            'reminder' => 'Pengingat ticket: ' . $ticket->title,
            'cancelled' => 'Ticket "' . $ticket->title . '" dibatalkan.',
            default => 'Ticket "' . $ticket->title . '" telah dibuat.',
        };

        $mailSubject = match ($event) {
            'assigned' => 'Penugasan Ticket: ' . $ticket->title,
            'reminder' => 'Reminder Ticket: ' . $ticket->title,
            'cancelled' => 'Ticket Cancelled: ' . $ticket->title,
            default => 'Ticket Baru: ' . $ticket->title,
        };

        $remark = match ($event) {
            'reminder' => 'Harap selesaikan atau perbarui status ticket ini jika sudah ditangani.',
            'cancelled' => 'Pemberitahuan ini dikirim satu kali sebagai arsip pembatalan.',
            default => null,
        };

        return [
            'type_label' => 'Ticket',
            'title' => $ticket->title,
            'url' => route('tickets.show', ['locale' => app()->getLocale(), 'ticket' => $ticket->id]),
            'icon' => $event === 'assigned' ? 'assignment_ind' : 'confirmation_number',
            'db_title' => $dbTitle,
            'db_message' => $dbMessage,
            'subject_type' => 'ticket',
            'subject_id' => $ticket->id,
            'mail_subject' => $mailSubject,
            'mail_intro' => $intro,
            'mail_remark' => $remark,
            'details' => $details,
            'related' => [],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function buildTaskPayload(Task $task, string $event, ?User $actor = null): array
    {
        $status = WorkflowStatus::label(WorkflowStatus::normalize($task->status ?? WorkflowStatus::NEW));
        $details = [
            'Judul' => $task->title,
            'Nomor Task' => $task->task_no ?? '-',
            'Status' => $status,
            'Prioritas' => ucfirst(strtolower((string) ($task->priority ?? 'normal'))),
            'Jatuh Tempo' => $this->formatDateTime($task->due_at),
        ];

        if ($task->requester) {
            $details['Requester'] = $task->requester->display_name;
        }
        if ($task->assignee) {
            $details['Agent'] = $task->assignee->display_name;
        }
        $picNames = collect();

        $assignedIds = $this->decodeJsonIds($task->assigned_to ?? null);
        if (! empty($assignedIds)) {
            $assignedUsers = User::whereIn('id', $assignedIds)->get()->keyBy('id');
            foreach ($assignedIds as $id) {
                $user = $assignedUsers->get($id);
                if ($user) {
                    $picNames->push($user->display_name ?? $user->email);
                }
            }
        }


        if ($picNames->isEmpty() && $task->assignee) {
            $picNames->push($task->assignee->display_name ?? $task->assignee->email);
        }

        $picList = $picNames
            ->filter()
            ->map(fn($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $details['PIC'] = ! empty($picList) ? implode(', ', $picList) : '-';

        $related = [];

        $intro = match ($event) {
            'assigned' => 'Anda baru saja ditetapkan sebagai penanggung jawab task berikut.',
            'reminder' => 'Ini adalah pengingat otomatis untuk task berikut.',
            'cancelled' => 'Task berikut telah dibatalkan.',
            default => 'Task berikut telah dibuat.',
        };

        $dbTitle = match ($event) {
            'assigned' => 'Task Assigned',
            'reminder' => 'Task Reminder',
            'cancelled' => 'Task Cancelled',
            default => 'Task Baru',
        };

        $dbMessage = match ($event) {
            'assigned' => 'Anda ditugaskan pada task: ' . $task->title,
            'reminder' => 'Pengingat task: ' . $task->title,
            'cancelled' => 'Task "' . $task->title . '" dibatalkan.',
            default => 'Task "' . $task->title . '" telah dibuat.',
        };

        $mailSubject = match ($event) {
            'assigned' => 'Penugasan Task: ' . $task->title,
            'reminder' => 'Reminder Task: ' . $task->title,
            'cancelled' => 'Task Cancelled: ' . $task->title,
            default => 'Task Baru: ' . $task->title,
        };

        $remark = match ($event) {
            'reminder' => 'Harap selesaikan atau perbarui status task ini jika sudah ditangani.',
            'cancelled' => 'Tindak lanjut dihentikan karena task ini dibatalkan.',
            default => null,
        };

        return [
            'type_label' => 'Task',
            'title' => $task->title,
            'url' => route('tasks.show', ['locale' => app()->getLocale(), 'taskSlug' => $task->public_slug ?? 'task-tsk'.base_convert((string) $task->id, 10, 36)]),
            'icon' => $event === 'assigned' ? 'assignment' : 'checklist',
            'db_title' => $dbTitle,
            'db_message' => $dbMessage,
            'subject_type' => 'task',
            'subject_id' => $task->id,
            'mail_subject' => $mailSubject,
            'mail_intro' => $intro,
            'mail_remark' => $remark,
            'details' => $details,
            'related' => $related,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function buildProjectPayload(Project $project, string $event, ?User $actor = null): array
    {
        $status = WorkflowStatus::label(WorkflowStatus::normalize($project->status ?? WorkflowStatus::NEW));
        $details = [
            'Judul' => $project->title,
            'Nomor Project' => $project->project_no ?? '—',
            'Status' => $status,
            'Mulai' => $this->formatDate($project->start_date),
            'Selesai' => $this->formatDate($project->end_date),
        ];

        $related = [];
        $projectPics = collect($project->pics ?? []);
        $agentName = $projectPics
            ->first(fn($pic) => strtolower((string) ($pic->role_type ?? '')) === 'agent')
            ?->user?->display_name;
        $picNames = $projectPics
            ->map(fn($pic) => $pic->user?->display_name)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $details['Agent'] = $agentName ?: '-';
        if (! empty($picNames)) {
            $details['PIC'] = implode(', ', $picNames);
        } else {
            $details['PIC'] = '-';
        }

        $intro = match ($event) {
            'assigned' => 'Anda baru saja ditetapkan sebagai PIC pada project berikut.',
            'reminder' => 'Ini adalah pengingat otomatis untuk project berikut.',
            'cancelled' => 'Project berikut telah dibatalkan.',
            default => 'Project berikut telah dibuat.',
        };

        $dbTitle = match ($event) {
            'assigned' => 'Project Assigned',
            'reminder' => 'Project Reminder',
            'cancelled' => 'Project Cancelled',
            default => 'Project Baru',
        };

        $dbMessage = match ($event) {
            'assigned' => 'Anda ditetapkan pada project: ' . $project->title,
            'reminder' => 'Pengingat project: ' . $project->title,
            'cancelled' => 'Project "' . $project->title . '" dibatalkan.',
            default => 'Project "' . $project->title . '" telah dibuat.',
        };

        $mailSubject = match ($event) {
            'assigned' => 'Penugasan Project: ' . $project->title,
            'reminder' => 'Reminder Project: ' . $project->title,
            'cancelled' => 'Project Cancelled: ' . $project->title,
            default => 'Project Baru: ' . $project->title,
        };

        $remark = match ($event) {
            'reminder' => 'Harap tinjau progres project ini dan lakukan pembaruan status bila perlu.',
            'cancelled' => 'Pemberitahuan tunggal ini mengonfirmasi pembatalan project.',
            default => null,
        };

        return [
            'type_label' => 'Project',
            'title' => $project->title,
            'url' => route('projects.show', ['locale' => app()->getLocale(), 'project' => $project->public_slug]),
            'icon' => $event === 'assigned' ? 'work' : 'work_outline',
            'db_title' => $dbTitle,
            'db_message' => $dbMessage,
            'subject_type' => 'project',
            'subject_id' => $project->id,
            'mail_subject' => $mailSubject,
            'mail_intro' => $intro,
            'mail_remark' => $remark,
            'details' => $details,
            'related' => $related,
        ];
    }

    private function buildAccountPayload(User $user, string $event, ?User $actor = null): array
    {
        $display = $user->display_name
            ?? trim(implode(' ', array_filter([$user->first_name ?? null, $user->last_name ?? null])))
            ?: $user->username
            ?: $user->email
            ?: ('User #' . $user->id);

        $details = [
            'Username' => $user->username ?? $user->email ?? $display,
            'Perubahan' => now($this->tz)->format('d M Y H:i'),
        ];

        return [
            'type_label' => 'Akun',
            'title' => 'Perubahan Password',
            'url' => route('account.change-password'),
            'icon' => 'lock',
            'db_title' => 'Password Diperbarui',
            'db_message' => 'Password akun Anda telah diperbarui.',
            'subject_type' => 'account',
            'subject_id' => $user->id,
            'mail_subject' => 'Konfirmasi Perubahan Password',
            'mail_intro' => 'Password akun Anda telah berhasil diperbarui.',
            'mail_remark' => 'Jika Anda tidak melakukan perubahan ini, segera hubungi administrator.',
            'details' => $details,
            'related' => [],
        ];
    }

    private function mergeRecipients(Collection ...$groups): Collection
    {
        $merged = collect();

        foreach ($groups as $group) {
            if (! $group instanceof Collection) {
                continue;
            }

            $merged = $merged->merge($group);
        }

        return $merged->unique('id')->values();
    }

    private function formatDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            if ($value instanceof Carbon) {
                return $value->copy()->timezone($this->tz)->format('d M Y H:i');
            }

            return Carbon::parse($value)->timezone($this->tz)->format('d M Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            if ($value instanceof Carbon) {
                return $value->copy()->timezone($this->tz)->format('d M Y');
            }

            return Carbon::parse($value)->timezone($this->tz)->format('d M Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
