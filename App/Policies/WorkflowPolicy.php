<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use App\Support\UnitVisibility;
use Illuminate\Database\Eloquent\Builder;

class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view workflows');
    }

    public function view(User $user, Workflow $workflow): bool
    {
        if (! $user->can('view workflows')) {
            return false;
        }
        if ($user->can('update workflows')) {
            return true;
        }
        if (! $workflow->is_active) {
            return false;
        }

        return $workflow->instances()->where(function (Builder $query) use ($user) {
            $query->where(function (Builder $tickets) use ($user) {
                $tickets->where('subject_type', Ticket::class)
                    ->whereHasMorph('subject', [Ticket::class], fn (Builder $items) => UnitVisibility::scopeWorkflowTickets($items, $user));
            })->orWhere(function (Builder $tasks) use ($user) {
                $tasks->where('subject_type', Task::class)
                    ->whereHasMorph('subject', [Task::class], fn (Builder $items) => UnitVisibility::scopeWorkflowTasks($items, $user));
            });
        })->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('create workflows');
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->can('update workflows');
    }

    public function toggle(User $user, Workflow $workflow): bool
    {
        return $user->can('toggle workflows');
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->can('delete workflows');
    }
}
