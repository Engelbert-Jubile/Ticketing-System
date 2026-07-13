<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;
class WorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view workflows');
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $user->can('view workflows')
            && ($workflow->is_active || $user->can('update workflows'));
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
