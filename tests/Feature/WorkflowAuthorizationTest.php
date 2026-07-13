<?php

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function workflowUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName($role, 'web'));

    return $user;
}

test('user can only browse and view active workflows', function () {
    $user = workflowUserWithRole('user');
    $active = Workflow::create(['name' => 'Active Flow', 'code' => 'ACTIVE_FLOW', 'entity_type' => 'ticket', 'is_active' => true]);
    $inactive = Workflow::create(['name' => 'Inactive Flow', 'code' => 'INACTIVE_FLOW', 'entity_type' => 'task', 'is_active' => false]);

    $this->actingAs($user)
        ->get(route('workflows.index', ['locale' => 'id']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Workflows/Index')
            ->has('workflows.data', 1)
            ->where('workflows.data.0.uuid', $active->uuid)
            ->where('can.create', false)
            ->where('can.update', false)
            ->where('can.toggle', false)
            ->where('can.delete', false));

    $this->actingAs($user)
        ->get(route('workflows.show', ['locale' => 'id', 'workflow' => $active]))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('workflows.show', ['locale' => 'id', 'workflow' => $inactive]))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('workflows.create', ['locale' => 'id']))
        ->assertForbidden();
});

test('admin can manage workflows but cannot delete', function () {
    $admin = workflowUserWithRole('admin');
    $workflow = Workflow::create(['name' => 'Admin Flow', 'code' => 'ADMIN_FLOW', 'entity_type' => 'ticket', 'is_active' => true]);

    $this->actingAs($admin)->get(route('workflows.create', ['locale' => 'id']))->assertOk();
    $this->actingAs($admin)->get(route('workflows.edit', ['locale' => 'id', 'workflow' => $workflow]))->assertOk();
    $this->actingAs($admin)->patch(route('workflows.toggle', ['locale' => 'id', 'workflow' => $workflow]))->assertRedirect();
    $this->actingAs($admin)->delete(route('workflows.destroy', ['locale' => 'id', 'workflow' => $workflow]))->assertForbidden();
});

test('superadmin has full workflow access including delete', function () {
    $superadmin = workflowUserWithRole('superadmin');
    $workflow = Workflow::create(['name' => 'Super Flow', 'code' => 'SUPER_FLOW', 'entity_type' => 'task', 'is_active' => false]);

    $this->actingAs($superadmin)
        ->delete(route('workflows.destroy', ['locale' => 'id', 'workflow' => $workflow]))
        ->assertRedirect(route('workflows.index', ['locale' => 'id']));

    expect($workflow->fresh()->trashed())->toBeTrue();
});
