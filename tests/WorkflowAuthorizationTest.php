<?php

use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Artisan::call('migrate:fresh', [
        '--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_07_23_023014_create_permission_tables.php',
            'database/migrations/2025_11_19_000001_add_unit_to_users_table.php',
            'database/migrations/2026_07_13_000000_create_workflow_management_tables.php',
            'database/migrations/2026_07_13_000001_add_workflow_permissions.php',
            'database/migrations/2026_07_13_000002_repair_workflow_management_tables.php',
        ],
        '--force' => true,
    ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function workflowUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function workflowPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Approval Flow',
        'code' => 'APPROVAL_FLOW',
        'entity_type' => 'ticket',
        'description' => 'Workflow test',
        'is_active' => true,
        'trigger_conditions' => [],
        'stages' => [
            [
                'name' => 'New',
                'status_key' => 'new',
                'responsible_role' => null,
                'responsible_user_id' => null,
                'action_label' => 'Start',
                'instructions' => null,
            ],
            [
                'name' => 'Done',
                'status_key' => 'done',
                'responsible_role' => null,
                'responsible_user_id' => null,
                'action_label' => 'Finish',
                'instructions' => null,
            ],
        ],
    ], $overrides);
}

function workflowInertiaHeaders(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
    ];
}

test('user can browse index and view active workflow only', function () {
    $user = workflowUserWithRole('user');
    $active = Workflow::create(['name' => 'Active Flow', 'code' => 'ACTIVE_FLOW', 'entity_type' => 'ticket', 'is_active' => true]);
    $inactive = Workflow::create(['name' => 'Inactive Flow', 'code' => 'INACTIVE_FLOW', 'entity_type' => 'task', 'is_active' => false]);

    $indexResponse = $this->actingAs($user)
        ->get(route('workflows.index', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Active Flow')
        ->assertDontSee('Inactive Flow');

    expect($indexResponse->headers->get('Content-Type'))->toContain('text/html');
    expect(ltrim($indexResponse->getContent()))->toStartWith('<!DOCTYPE html>');

    $this->actingAs($user)
        ->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'en']))
        ->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'Workflows/Index');

    $this->actingAs($user)
        ->get(route('workflows.show', ['locale' => 'id', 'workflow' => $active]))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('workflows.show', ['locale' => 'id', 'workflow' => $inactive]))
        ->assertForbidden();
});

test('workflow seeder creates ticket and task defaults idempotently', function () {
    Artisan::call('db:seed', ['--class' => WorkflowSeeder::class, '--force' => true]);
    Artisan::call('db:seed', ['--class' => WorkflowSeeder::class, '--force' => true]);

    expect(Workflow::query()->count())->toBe(2)
        ->and(Workflow::query()->where('entity_type', 'project')->count())->toBe(0)
        ->and(Workflow::query()->where('code', 'TICKET_DEFAULT')->where('is_active', true)->exists())->toBeTrue()
        ->and(Workflow::query()->where('code', 'TASK_DEFAULT')->where('is_active', true)->exists())->toBeTrue()
        ->and(Workflow::query()->where('code', 'TICKET_DEFAULT')->first()->stages()->count())->toBe(4)
        ->and(Workflow::query()->where('code', 'TASK_DEFAULT')->first()->stages()->count())->toBe(3);
});

test('workflow index applies search type status pagination and permissions', function () {
    $user = workflowUserWithRole('user');
    $admin = workflowUserWithRole('admin');
    Workflow::create(['name' => 'Ticket Match', 'code' => 'TICKET_MATCH', 'entity_type' => 'ticket', 'is_active' => true]);
    Workflow::create(['name' => 'Task Match', 'code' => 'TASK_MATCH', 'entity_type' => 'task', 'is_active' => true]);
    Workflow::create(['name' => 'Task Hidden', 'code' => 'TASK_HIDDEN', 'entity_type' => 'task', 'is_active' => false]);

    $this->actingAs($user)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id', 'search' => 'Match', 'type' => 'task', 'status' => 'active']))
        ->assertOk()->assertJsonCount(1, 'props.workflows.data')
        ->assertJsonPath('props.workflows.data.0.code', 'TASK_MATCH')
        ->assertJsonPath('props.can.create', false)->assertJsonPath('props.can.delete', false);

    $this->actingAs($admin)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id', 'type' => 'task', 'status' => 'inactive']))
        ->assertOk()->assertJsonCount(1, 'props.workflows.data')
        ->assertJsonPath('props.workflows.data.0.code', 'TASK_HIDDEN')
        ->assertJsonPath('props.can.create', true)->assertJsonPath('props.can.delete', false);

    foreach (range(1, 11) as $number) {
        Workflow::create(['name' => 'Page '.$number, 'code' => 'PAGE_'.$number, 'entity_type' => 'ticket', 'is_active' => true]);
    }

    $this->actingAs($admin)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id', 'search' => 'Page']))
        ->assertOk()->assertJsonCount(10, 'props.workflows.data')
        ->assertJsonPath('props.workflows.total', 11)
        ->assertJsonPath('props.workflows.last_page', 2);
});

test('user cannot create update toggle or delete workflows', function () {
    $user = workflowUserWithRole('user');
    $workflow = Workflow::create(['name' => 'User Flow', 'code' => 'USER_FLOW', 'entity_type' => 'ticket', 'is_active' => true]);

    $forbiddenResponse = $this->actingAs($user)
        ->get(route('workflows.create', ['locale' => 'id']))
        ->assertForbidden();

    expect($forbiddenResponse->headers->get('Content-Type'))->toContain('text/html');
    expect(ltrim($forbiddenResponse->getContent()))->toStartWith('<!DOCTYPE html>');

    $this->actingAs($user)->post(route('workflows.store', ['locale' => 'id']), workflowPayload())->assertForbidden();
    $this->actingAs($user)->put(route('workflows.update', ['locale' => 'id', 'workflow' => $workflow]), workflowPayload())->assertForbidden();
    $this->actingAs($user)->patch(route('workflows.toggle', ['locale' => 'id', 'workflow' => $workflow]))->assertForbidden();
    $this->actingAs($user)->delete(route('workflows.destroy', ['locale' => 'id', 'workflow' => $workflow]))->assertForbidden();
});

test('admin can browse view create update and toggle but cannot delete', function () {
    $admin = workflowUserWithRole('admin');
    $workflow = Workflow::create(['name' => 'Admin Flow', 'code' => 'ADMIN_FLOW', 'entity_type' => 'ticket', 'is_active' => true]);

    $this->actingAs($admin)->get(route('workflows.index', ['locale' => 'id']))->assertOk();
    $this->actingAs($admin)->get(route('workflows.show', ['locale' => 'id', 'workflow' => $workflow]))->assertOk();
    $this->actingAs($admin)->get(route('workflows.create', ['locale' => 'id']))->assertOk();
    $this->actingAs($admin)->post(route('workflows.store', ['locale' => 'id']), workflowPayload(['code' => 'ADMIN_CREATED']))->assertRedirect();
    $this->actingAs($admin)->put(
        route('workflows.update', ['locale' => 'id', 'workflow' => $workflow]),
        workflowPayload(['name' => 'Admin Updated', 'code' => 'ADMIN_FLOW'])
    )->assertRedirect();
    $this->actingAs($admin)->get(route('workflows.edit', ['locale' => 'id', 'workflow' => $workflow]))->assertOk();
    $this->actingAs($admin)->patch(route('workflows.toggle', ['locale' => 'id', 'workflow' => $workflow]))->assertRedirect();
    $this->actingAs($admin)->delete(route('workflows.destroy', ['locale' => 'id', 'workflow' => $workflow]))->assertForbidden();
});

test('superadmin can browse view create update toggle and delete workflows', function () {
    $superadmin = workflowUserWithRole('superadmin');
    $workflow = Workflow::create(['name' => 'Super Flow', 'code' => 'SUPER_FLOW', 'entity_type' => 'task', 'is_active' => false]);

    $this->actingAs($superadmin)
        ->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'en']))
        ->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'Workflows/Index');
    $this->actingAs($superadmin)->get(route('workflows.show', ['locale' => 'id', 'workflow' => $workflow]))->assertOk();
    $this->actingAs($superadmin)->get(route('workflows.create', ['locale' => 'id']))->assertOk();
    $this->actingAs($superadmin)->post(route('workflows.store', ['locale' => 'id']), workflowPayload(['code' => 'SUPER_CREATED']))->assertRedirect();
    $this->actingAs($superadmin)->put(
        route('workflows.update', ['locale' => 'id', 'workflow' => $workflow]),
        workflowPayload(['name' => 'Super Updated', 'code' => 'SUPER_FLOW', 'entity_type' => 'task'])
    )->assertRedirect();
    $this->actingAs($superadmin)->patch(route('workflows.toggle', ['locale' => 'id', 'workflow' => $workflow]))->assertRedirect();

    $this->actingAs($superadmin)
        ->delete(route('workflows.destroy', ['locale' => 'id', 'workflow' => $workflow]))
        ->assertRedirect(route('workflows.index', ['locale' => 'id']));

    expect($workflow->fresh()->trashed())->toBeTrue();
});
