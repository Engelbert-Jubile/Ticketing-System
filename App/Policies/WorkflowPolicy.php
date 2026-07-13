<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;
use App\Support\RoleHelpers;

class WorkflowPolicy
{
    private function manages(User $user): bool { return RoleHelpers::userIsSuperAdmin($user) || $user->hasRole('admin'); }
    public function viewAny(User $user): bool { return $this->manages($user); }
    public function view(User $user, Workflow $workflow): bool { return $this->manages($user); }
    public function create(User $user): bool { return $this->manages($user); }
    public function update(User $user, Workflow $workflow): bool { return $this->manages($user); }
    public function delete(User $user, Workflow $workflow): bool { return $this->manages($user); }
}
