<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class UnitVisibility
{
    public static function requiresRestriction(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $unit = trim((string) $user->unit);
        if ($unit === '') {
            return false;
        }

        if (RoleHelpers::userIsSuperAdmin($user)) {
            return false;
        }

        return true;
    }

    public static function scopeTickets(Builder $query, ?User $user): Builder
    {
        if (! self::requiresRestriction($user)) {
            return $query;
        }

        $userId = (int) ($user?->id ?? 0);
        $unit = $user->unit;

        return $query->where(function (Builder $builder) use ($unit, $userId) {
            $builder->whereHas('requester', fn (Builder $sub) => $sub->where('unit', $unit));

            if ($userId > 0) {
                $builder->orWhere('requester_id', $userId)
                    ->orWhere('agent_id', $userId)
                    ->orWhere('assigned_id', $userId)
                    ->orWhereHas('assignedUsers', fn (Builder $sub) => $sub->where('users.id', $userId))
                    ->orWhereHas('tasks', function (Builder $taskQuery) use ($userId) {
                        $taskQuery->where('assignee_id', $userId)
                            ->orWhere('created_by', $userId)
                            ->orWhere(function (Builder $subQuery) use ($userId) {
                                self::orWhereJsonAssignmentContains($subQuery, 'assigned_to', $userId);
                            });
                    });
            }
        });
    }

    public static function scopeTasks(Builder $query, ?User $user): Builder
    {
        if (! self::requiresRestriction($user)) {
            return $query;
        }

        $userId = (int) ($user?->id ?? 0);
        $unit = $user->unit;

        return $query->where(function (Builder $builder) use ($unit, $userId) {
            $builder->whereHas('requester', fn (Builder $sub) => $sub->where('unit', $unit));

            if ($userId > 0) {
                $builder->orWhere('assignee_id', $userId)
                    ->orWhere('created_by', $userId)
                    ->orWhere(function (Builder $sub) use ($userId) {
                        self::orWhereJsonAssignmentContains($sub, 'assigned_to', $userId);
                    })
                    ->orWhereHas('ticket', function (Builder $ticketQuery) use ($unit, $userId) {
                        $ticketQuery->whereHas('requester', fn (Builder $sub) => $sub->where('unit', $unit))
                            ->orWhere('agent_id', $userId)
                            ->orWhere('assigned_id', $userId)
                            ->orWhereHas('assignedUsers', fn (Builder $sub) => $sub->where('users.id', $userId));
                    });
            }
        });
    }

    public static function scopeProjects(Builder $query, ?User $user): Builder
    {
        if (! self::requiresRestriction($user)) {
            return $query;
        }

        $userId = (int) ($user?->id ?? 0);
        $unit = $user->unit;
        $projectsHasAgent = self::projectHasColumn('agent_id');
        $projectsHasAssigned = self::projectHasColumn('assigned_id');

        return $query->where(function (Builder $builder) use ($unit, $userId, $projectsHasAgent, $projectsHasAssigned) {
            $builder->whereHas('requester', fn (Builder $sub) => $sub->where('unit', $unit))
                ->orWhereHas('user', fn (Builder $sub) => $sub->where('unit', $unit))
                ->orWhereHas('ticket.requester', fn (Builder $sub) => $sub->where('unit', $unit));

            if ($userId > 0) {
                $builder->orWhere('requester_id', $userId)
                    ->orWhere('created_by', $userId)
                    ->when($projectsHasAgent, fn (Builder $sub) => $sub->orWhere('agent_id', $userId))
                    ->when($projectsHasAssigned, fn (Builder $sub) => $sub->orWhere('assigned_id', $userId))
                    ->orWhereHas('pics', fn (Builder $sub) => $sub->where('user_id', $userId))
                    ->orWhereHas('ticket', function (Builder $ticketQuery) use ($unit, $userId) {
                        $ticketQuery->whereHas('requester', fn (Builder $sub) => $sub->where('unit', $unit))
                            ->orWhere('agent_id', $userId)
                            ->orWhere('assigned_id', $userId)
                            ->orWhereHas('assignedUsers', fn (Builder $sub) => $sub->where('users.id', $userId));
                    });
            }
        });
    }

    public static function ensureTicketAccess(?User $user, Ticket $ticket): void
    {
        if (! self::requiresRestriction($user)) {
            return;
        }

        $ticket->loadMissing('requester', 'assignedUsers', 'agent', 'assignee');

        if (
            ($ticket->requester?->unit) === $user->unit
            || self::userRelatesToTicket($user, $ticket)
        ) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke unit ini.');
    }

    public static function ensureTaskAccess(?User $user, Task $task): void
    {
        if (! self::requiresRestriction($user)) {
            return;
        }

        $task->loadMissing('requester', 'assignee', 'ticket.assignedUsers', 'ticket.requester', 'ticket.agent');

        $unitMatches = ($task->requester?->unit) === $user->unit;
        $belongsToTicket = $task->ticket && (
            ($task->ticket->requester?->unit) === $user->unit
            || self::userRelatesToTicket($user, $task->ticket)
        );

        if ($unitMatches || $belongsToTicket || self::userRelatesToTask($user, $task)) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke unit ini.');
    }

    public static function ensureProjectAccess(?User $user, Project $project): void
    {
        if (! self::requiresRestriction($user)) {
            return;
        }

        $project->loadMissing([
            'requester',
            'user',
            'pics.user',
            'ticket.requester',
            'ticket.assignedUsers',
            'ticket.agent',
        ]);

        $unitMatches = (
            ($project->requester?->unit) === $user->unit
            || ($project->user?->unit) === $user->unit
            || ($project->ticket?->requester?->unit) === $user->unit
        );

        $userIsPic = $project->pics->contains(fn ($pic) => (int) $pic->user_id === (int) ($user->id ?? 0));
        $userCreated = (int) ($project->created_by ?? 0) === (int) ($user->id ?? 0);
        $userRequested = (int) ($project->requester_id ?? 0) === (int) ($user->id ?? 0);
        $userAgent = (int) ($project->agent_id ?? 0) === (int) ($user->id ?? 0);
        $userAssigned = (int) ($project->assigned_id ?? 0) === (int) ($user->id ?? 0);

        $viaTicket = $project->ticket && (
            self::userRelatesToTicket($user, $project->ticket)
        );

        if ($unitMatches || $userIsPic || $userCreated || $userRequested || $userAgent || $userAssigned || $viaTicket) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke unit ini.');
    }

    private static function userRelatesToTicket(?User $user, Ticket $ticket): bool
    {
        if (! $user) {
            return false;
        }

        $userId = (int) ($user->id ?? 0);
        if ($userId <= 0) {
            return false;
        }

        if ((int) ($ticket->requester_id ?? 0) === $userId) {
            return true;
        }

        if ((int) ($ticket->agent_id ?? 0) === $userId) {
            return true;
        }

        if ((int) ($ticket->assigned_id ?? 0) === $userId) {
            return true;
        }

        if ($ticket->relationLoaded('assignedUsers')) {
            if ($ticket->assignedUsers->contains(fn ($assigned) => (int) $assigned->id === $userId)) {
                return true;
            }
        } else {
            if ($ticket->assignedUsers()->where('users.id', $userId)->exists()) {
                return true;
            }
        }

        return self::userRelatesToTicketTasks($ticket, $userId);
    }

    private static function userRelatesToTicketTasks(Ticket $ticket, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if ($ticket->relationLoaded('tasks')) {
            foreach ($ticket->tasks as $task) {
                if ((int) ($task->assignee_id ?? 0) === $userId) {
                    return true;
                }

                if ((int) ($task->created_by ?? 0) === $userId) {
                    return true;
                }

                if (self::taskAssignedCollectionContains($task, $userId)) {
                    return true;
                }
            }

            return false;
        }

        return Task::where('ticket_id', $ticket->id)
            ->where(function (Builder $query) use ($userId) {
                $query->where('assignee_id', $userId)
                    ->orWhere('created_by', $userId)
                    ->orWhere(function (Builder $sub) use ($userId) {
                        self::orWhereJsonAssignmentContains($sub, 'assigned_to', $userId);
                    });
            })
            ->exists();
    }

    private static function userRelatesToTask(?User $user, Task $task): bool
    {
        if (! $user) {
            return false;
        }

        $userId = (int) ($user->id ?? 0);
        if ($userId <= 0) {
            return false;
        }

        if ((int) ($task->assignee_id ?? 0) === $userId) {
            return true;
        }

        if ((int) ($task->created_by ?? 0) === $userId) {
            return true;
        }

        if (self::taskAssignedCollectionContains($task, $userId)) {
            return true;
        }

        if ($task->ticket) {
            return self::userRelatesToTicket($user, $task->ticket);
        }

        return false;
    }

    private static function orWhereJsonAssignmentContains(Builder $builder, string $column, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $driver = $builder->getConnection()->getDriverName();
        $qualified = $builder->qualifyColumn($column);
        $jsonValue = json_encode($userId);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $builder->orWhereRaw("JSON_VALID({$qualified}) AND JSON_CONTAINS({$qualified}, ?, '$')", [$jsonValue]);

            return;
        }

        if ($driver === 'sqlite') {
            $builder->orWhereRaw("json_valid({$qualified}) AND EXISTS (SELECT 1 FROM json_each({$qualified}) WHERE json_each.value = ?)", [$userId]);

            return;
        }

        $builder->orWhere($column, 'like', '%"'.$userId.'"%');
    }

    private static function taskAssignedCollectionContains(Task $task, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $raw = $task->assigned_to;
        if (! $raw) {
            return false;
        }

        $values = [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $values = $decoded;
            }
        } elseif (is_array($raw)) {
            $values = $raw;
        }

        foreach ($values as $value) {
            if ((int) $value === $userId) {
                return true;
            }
        }

        return false;
    }

    private static function projectHasColumn(string $column): bool
    {
        static $cache = [];

        if (! array_key_exists($column, $cache)) {
            $cache[$column] = Schema::hasColumn('projects', $column);
        }

        return $cache[$column];
    }
}
