<?php

namespace App\Policies;

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

        $model = Workflow::SUBJECTS[$workflow->entity_type];

        return $workflow->instances()
            ->whereHasMorph('subject', [$model], fn (Builder $items) => UnitVisibility::scopeWorkflowSubjects($items, $user, $model))
            ->exists();
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
