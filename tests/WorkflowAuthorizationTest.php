<?php

use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            'database/migrations/2026_07_20_000000_upgrade_workflow_runtime_audit.php',
            'database/migrations/2026_07_20_120000_add_semantic_workflow_identifiers.php',
        ],
        '--force' => true,
    ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::create('tickets', function ($table) {
        $table->id();
        $table->uuid('uuid')->nullable()->unique();
        $table->string('ticket_no')->nullable();
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('priority')->default('medium');
        $table->date('due_date')->nullable();
        $table->date('finish_date')->nullable();
        $table->string('sla')->nullable();
        $table->timestamp('due_at')->nullable();
        $table->timestamp('finish_at')->nullable();
        $table->string('type')->default('incident');
        $table->string('status_id')->nullable();
        $table->string('status')->default('new');
        $table->foreignId('requester_id')->nullable();
        $table->foreignId('agent_id')->nullable();
        $table->foreignId('assigned_id')->nullable();
        $table->timestamps();
    });
    Schema::create('tasks', function ($table) {
        $table->id();
        $table->uuid('uuid')->nullable()->unique();
        $table->string('task_no')->nullable();
        $table->foreignId('ticket_id')->nullable();
        $table->foreignId('project_id')->nullable();
        $table->foreignId('assignee_id')->nullable();
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('status')->default('new');
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->foreignId('created_by')->nullable();
        $table->text('planning')->nullable();
        $table->string('priority')->nullable();
        $table->text('assigned_to')->nullable();
        $table->timestamp('due_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->string('public_slug')->nullable();
        $table->timestamps();
    });
    Schema::create('ticket_assignees', function ($table) {
        $table->foreignId('ticket_id');
        $table->foreignId('user_id');
        $table->timestamps();
    });
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
        ->assertDontSee('Active Flow')
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
        ->assertForbidden();

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
        ->and(Workflow::query()->where('code', 'TICKET_DEFAULT')->first()->stages()->count())->toBe(7)
        ->and(Workflow::query()->where('code', 'TASK_DEFAULT')->first()->stages()->count())->toBe(7);
});

test('sync existing command backfills legacy statuses idempotently with scoped counts', function () {
    $user = workflowUserWithRole('user');
    $superadmin = workflowUserWithRole('superadmin');
    $other = User::factory()->create();
    $now = now();

    DB::table('tickets')->insert([
        ['ticket_no' => 'TCK-LEGACY-CLOSED', 'title' => 'Legacy closed ticket', 'status' => 'closed', 'requester_id' => $user->id, 'created_at' => $now, 'updated_at' => $now],
        ['ticket_no' => 'TCK-LEGACY-OPEN', 'title' => 'Legacy open ticket', 'status' => 'open', 'requester_id' => $user->id, 'created_at' => $now, 'updated_at' => $now],
        ['ticket_no' => 'TCK-LEGACY-PROGRESS', 'title' => 'Legacy progress ticket', 'status' => 'on_progress', 'requester_id' => $other->id, 'created_at' => $now, 'updated_at' => $now],
    ]);
    DB::table('tasks')->insert([
        ['task_no' => 'TSK-LEGACY-COMPLETED', 'title' => 'Legacy completed task', 'status' => 'completed', 'created_by' => $user->id, 'created_at' => $now, 'updated_at' => $now],
        ['task_no' => 'TSK-LEGACY-PENDING', 'title' => 'Legacy pending task', 'status' => 'pending', 'created_by' => $other->id, 'created_at' => $now, 'updated_at' => $now],
        ['task_no' => 'TSK-LEGACY-CONF', 'title' => 'Legacy confirmation task', 'status' => 'CONF', 'created_by' => $other->id, 'created_at' => $now, 'updated_at' => $now],
    ]);

    expect(WorkflowInstance::query()->count())->toBe(0);
    expect(Artisan::call('workflows:sync-existing'))->toBe(0);
    expect(WorkflowInstance::query()->count())->toBe(6);
    expect(Artisan::call('workflows:sync-existing'))->toBe(0);
    expect(WorkflowInstance::query()->count())->toBe(6);

    $expectedStages = [
        'TCK-LEGACY-CLOSED' => 'done',
        'TCK-LEGACY-OPEN' => 'new',
        'TCK-LEGACY-PROGRESS' => 'in_progress',
        'TSK-LEGACY-COMPLETED' => 'done',
        'TSK-LEGACY-PENDING' => 'in_progress',
        'TSK-LEGACY-CONF' => 'confirmation',
    ];
    foreach ($expectedStages as $number => $expectedStage) {
        $subject = str_starts_with($number, 'TCK-')
            ? Ticket::query()->where('ticket_no', $number)->firstOrFail()
            : Task::query()->where('task_no', $number)->firstOrFail();
        $instance = WorkflowInstance::query()
            ->where('subject_type', $subject::class)
            ->where('subject_id', $subject->id)
            ->with('currentStage')
            ->sole();
        expect($instance->currentStage->status_key)->toBe($expectedStage);
    }

    $this->actingAs($user)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id']))
        ->assertOk()
        ->assertJsonPath('props.summary.total', 3)
        ->assertJsonPath('props.summary.completed', 2)
        ->assertJsonCount(3, 'props.items.data');

    $this->actingAs($superadmin)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id']))
        ->assertOk()
        ->assertJsonPath('props.summary.total', 6)
        ->assertJsonPath('props.summary.completed', 2)
        ->assertJsonCount(6, 'props.items.data')
        ->assertJsonFragment(['status_key' => 'in_progress', 'label' => 'In Progress', 'count' => 2])
        ->assertJsonFragment(['status_key' => 'confirmation', 'label' => 'Confirmation', 'count' => 1]);
});

test('existing tickets and tasks use default workflows and follow status changes', function () {
    Artisan::call('db:seed', ['--class' => WorkflowSeeder::class, '--force' => true]);
    $viewer = workflowUserWithRole('superadmin');
    $requester = User::factory()->create(['first_name' => 'Rina', 'last_name' => 'Requester']);
    $pic = User::factory()->create(['first_name' => 'Budi', 'last_name' => 'PIC']);

    $ticket = Ticket::create([
        'ticket_no' => 'TCK-REAL-001',
        'title' => 'Ticket production existing',
        'status' => 'new',
        'requester_id' => $requester->id,
        'assigned_id' => $pic->id,
    ]);
    $task = Task::create([
        'task_no' => 'TSK-REAL-001',
        'title' => 'Task production existing',
        'status' => 'in_progress',
        'created_by' => $requester->id,
        'assignee_id' => $pic->id,
        'assigned_to' => json_encode([$pic->id]),
    ]);

    expect(Ticket::query()->count())->toBe(1)
        ->and(Task::query()->count())->toBe(1)
        ->and(WorkflowInstance::query()->count())->toBe(2)
        ->and(WorkflowInstance::query()->where('subject_type', \App\Models\Project::class)->count())->toBe(0);

    $ticket->update(['status' => 'in_progress']);
    $task->update(['status' => 'done']);

    $ticketInstance = WorkflowInstance::query()->where('subject_type', Ticket::class)->with('currentStage')->firstOrFail();
    $taskInstance = WorkflowInstance::query()->where('subject_type', Task::class)->with('currentStage')->firstOrFail();
    expect($ticketInstance->currentStage->status_key)->toBe('in_progress')
        ->and($ticketInstance->status)->toBe('running')
        ->and($taskInstance->currentStage->status_key)->toBe('done')
        ->and($taskInstance->status)->toBe('completed')
        ->and($taskInstance->completed_at)->not->toBeNull();

    $this->actingAs($viewer)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id']))
        ->assertOk()
        ->assertJsonPath('props.summary.total', 2)
        ->assertJsonPath('props.summary.in_progress', 1)
        ->assertJsonPath('props.summary.completed', 1)
        ->assertJsonCount(2, 'props.items.data')
        ->assertJsonFragment([
            'number' => 'TCK-REAL-001',
            'title' => 'Ticket production existing',
            'type' => 'ticket',
        ])
        ->assertJsonFragment([
            'number' => 'TSK-REAL-001',
            'title' => 'Task production existing',
            'type' => 'task',
        ]);

    $ticketWorkflow = Workflow::query()->where('code', 'TICKET_DEFAULT')->firstOrFail();
    $this->actingAs($viewer)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.show', ['locale' => 'id', 'workflow' => $ticketWorkflow]))
        ->assertOk()
        ->assertJsonPath('props.instances.total', 1)
        ->assertJsonPath('props.instances.data.0.number', 'TCK-REAL-001')
        ->assertJsonPath('props.instances.data.0.requester', 'Rina Requester')
        ->assertJsonPath('props.instances.data.0.pic', 'Budi PIC')
        ->assertJsonPath('props.instances.data.0.status', 'in_progress')
        ->assertJsonPath('props.workflow.stages.1.instances_count', 1);

    $taskWorkflow = Workflow::query()->where('code', 'TASK_DEFAULT')->firstOrFail();
    $this->actingAs($viewer)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.show', ['locale' => 'id', 'workflow' => $taskWorkflow]))
        ->assertOk()
        ->assertJsonPath('props.instances.total', 1)
        ->assertJsonPath('props.instances.data.0.number', 'TSK-REAL-001')
        ->assertJsonPath('props.instances.data.0.pic', 'Budi PIC')
        ->assertJsonPath('props.instances.data.0.status', 'done');
});

test('workflow index applies search type status pagination and permissions', function () {
    $user = workflowUserWithRole('user');
    $admin = workflowUserWithRole('admin');
    $superadmin = workflowUserWithRole('superadmin');
    $unrelated = User::factory()->create();
    Artisan::call('db:seed', ['--class' => WorkflowSeeder::class, '--force' => true]);

    Ticket::create([
        'ticket_no' => 'TCK-USER-001',
        'title' => 'User searchable ticket',
        'status' => 'new',
        'requester_id' => $user->id,
    ]);
    Task::create([
        'task_no' => 'TSK-USER-001',
        'title' => 'User completed task',
        'status' => 'done',
        'created_by' => $user->id,
    ]);
    Ticket::create([
        'ticket_no' => 'TCK-ADMIN-001',
        'title' => 'Admin in progress ticket',
        'status' => 'in_progress',
        'requester_id' => $admin->id,
    ]);
    Task::create([
        'task_no' => 'TSK-OTHER-001',
        'title' => 'Unrelated task',
        'status' => 'confirmation',
        'created_by' => $unrelated->id,
    ]);

    $this->actingAs($user)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id']))
        ->assertOk()
        ->assertJsonPath('props.summary.total', 2)
        ->assertJsonCount(2, 'props.items.data')
        ->assertJsonMissing(['number' => 'TCK-ADMIN-001'])
        ->assertJsonMissing(['number' => 'TSK-OTHER-001'])
        ->assertJsonPath('props.can.create', false)
        ->assertJsonPath('props.can.delete', false);

    $this->actingAs($user)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id', 'search' => 'searchable', 'type' => 'ticket', 'status' => 'new']))
        ->assertOk()
        ->assertJsonCount(1, 'props.items.data')
        ->assertJsonPath('props.items.data.0.number', 'TCK-USER-001');

    $this->actingAs($user)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id', 'type' => 'task', 'status' => 'done']))
        ->assertOk()
        ->assertJsonCount(1, 'props.items.data')
        ->assertJsonPath('props.items.data.0.number', 'TSK-USER-001');

    $this->actingAs($admin)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id']))
        ->assertOk()
        ->assertJsonPath('props.summary.total', 1)
        ->assertJsonPath('props.items.data.0.number', 'TCK-ADMIN-001')
        ->assertJsonPath('props.can.create', true)
        ->assertJsonPath('props.can.delete', false);

    $this->actingAs($superadmin)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id']))
        ->assertOk()
        ->assertJsonPath('props.summary.total', 4)
        ->assertJsonCount(4, 'props.items.data')
        ->assertJsonPath('props.can.delete', true);

    $ticketWorkflow = Workflow::query()->where('code', 'TICKET_DEFAULT')->firstOrFail();
    $this->actingAs($user)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.show', ['locale' => 'id', 'workflow' => $ticketWorkflow]))
        ->assertOk()
        ->assertJsonPath('props.workflow.total_items_count', 1)
        ->assertJsonPath('props.instances.total', 1)
        ->assertJsonPath('props.instances.data.0.number', 'TCK-USER-001');
    $this->actingAs($superadmin)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.show', ['locale' => 'id', 'workflow' => $ticketWorkflow]))
        ->assertOk()
        ->assertJsonPath('props.workflow.total_items_count', 2)
        ->assertJsonPath('props.instances.total', 2);

    foreach (range(1, 11) as $number) {
        Ticket::create([
            'ticket_no' => 'TCK-PAGE-'.$number,
            'title' => 'Page item '.$number,
            'status' => 'new',
            'requester_id' => $user->id,
        ]);
    }

    $this->actingAs($user)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.index', ['locale' => 'id', 'search' => 'Page']))
        ->assertOk()
        ->assertJsonCount(10, 'props.items.data')
        ->assertJsonPath('props.items.total', 11)
        ->assertJsonPath('props.items.last_page', 2);
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

test('related runtime actions enforce transitions scope and audit history', function () {
    Artisan::call('db:seed', ['--class' => WorkflowSeeder::class, '--force' => true]);
    $actor = workflowUserWithRole('user');
    $unrelated = workflowUserWithRole('user');
    $ticket = Ticket::create([
        'ticket_no' => 'TCK-RUNTIME-001',
        'title' => 'Runtime transition',
        'status' => 'new',
        'requester_id' => $actor->id,
        'assigned_id' => $actor->id,
    ]);
    $instance = $ticket->workflowInstances()->with('currentStage')->sole();

    $this->actingAs($actor)->patch(route('workflows.instances.status', [
        'locale' => 'id', 'instance' => $instance,
    ]), ['status' => 'in_progress'])->assertRedirect();

    expect($ticket->fresh()->status)->toBe('in_progress')
        ->and($instance->fresh()->currentStage->status_key)->toBe('in_progress')
        ->and($instance->histories()->where('event', 'status_changed')->exists())->toBeTrue();

    $this->actingAs($actor)->patch(route('workflows.instances.status', [
        'locale' => 'id', 'instance' => $instance,
    ]), ['status' => 'done'])->assertSessionHasErrors('status');
    expect($ticket->fresh()->status)->toBe('in_progress');

    $this->actingAs($unrelated)->patch(route('workflows.instances.status', [
        'locale' => 'id', 'instance' => $instance,
    ]), ['status' => 'confirmation'])->assertForbidden();
});

test('admin manages stages safely while users remain forbidden', function () {
    $admin = workflowUserWithRole('admin');
    $user = workflowUserWithRole('user');
    $workflow = Workflow::create(['name' => 'Safe Flow', 'code' => 'SAFE_FLOW', 'entity_type' => 'ticket', 'is_active' => true]);
    $first = $workflow->stages()->create(['position' => 1, 'name' => 'New', 'status_key' => 'new', 'is_required' => true]);
    $workflow->stages()->create(['position' => 2, 'name' => 'Done', 'status_key' => 'done', 'is_required' => true]);

    $stagePayload = [
        'position' => 2, 'name' => 'Confirmation', 'status_key' => 'confirmation',
        'responsible_role' => 'admin', 'responsible_user_id' => null,
        'is_required' => true, 'action_label' => 'Confirm', 'instructions' => null,
    ];
    $this->actingAs($user)->post(route('workflows.stages.store', ['locale' => 'id', 'workflow' => $workflow]), $stagePayload)->assertForbidden();
    $this->actingAs($admin)->post(route('workflows.stages.store', ['locale' => 'id', 'workflow' => $workflow]), $stagePayload)->assertRedirect();
    $added = $workflow->stages()->where('status_key', 'confirmation')->sole();

    $this->actingAs($admin)->put(route('workflows.stages.update', ['locale' => 'id', 'workflow' => $workflow, 'stage' => $added]), [
        ...$stagePayload, 'name' => 'Quality Confirmation',
    ])->assertRedirect();
    expect($added->fresh()->name)->toBe('Quality Confirmation');

    $orderedIds = [$added->id, $first->id, $workflow->stages()->where('status_key', 'done')->value('id')];
    $this->actingAs($admin)->patch(route('workflows.stages.reorder', ['locale' => 'id', 'workflow' => $workflow]), ['stage_ids' => $orderedIds])->assertRedirect();
    expect($workflow->stages()->pluck('id')->all())->toBe($orderedIds);

    $this->actingAs($admin)->delete(route('workflows.stages.destroy', ['locale' => 'id', 'workflow' => $workflow, 'stage' => $added]))->assertRedirect();
    expect($workflow->stages()->count())->toBe(2)
        ->and($workflow->histories()->whereIn('event', ['stage_created', 'stage_updated', 'stages_reordered', 'stage_deleted'])->count())->toBe(4);
});

test('active workflow edits preserve referenced stages and related records', function () {
    $superadmin = workflowUserWithRole('superadmin');
    $workflow = Workflow::create(['name' => 'Versioned Flow', 'code' => 'VERSIONED_FLOW', 'entity_type' => 'ticket', 'is_active' => true]);
    $first = $workflow->stages()->create(['position' => 1, 'name' => 'New', 'status_key' => 'new', 'is_required' => true]);
    $last = $workflow->stages()->create(['position' => 2, 'name' => 'Done', 'status_key' => 'done', 'is_required' => true]);
    $ticket = Ticket::create(['ticket_no' => 'TCK-SAFE-001', 'title' => 'Must survive', 'status' => 'new', 'requester_id' => $superadmin->id]);
    $instance = WorkflowInstance::query()->where('subject_id', $ticket->id)->first();
    if (! $instance || $instance->workflow_id !== $workflow->id) {
        $instance?->delete();
        $instance = WorkflowInstance::create(['workflow_id' => $workflow->id, 'workflow_version' => 1, 'subject_type' => Ticket::class, 'subject_id' => $ticket->id, 'current_stage_id' => $first->id, 'status' => 'running']);
    }

    $this->actingAs($superadmin)->put(route('workflows.update', ['locale' => 'id', 'workflow' => $workflow]), workflowPayload([
        'name' => 'Versioned Flow Updated', 'code' => 'VERSIONED_FLOW',
        'stages' => [
            ['id' => $first->id, 'name' => 'New Queue', 'status_key' => 'new', 'responsible_role' => null, 'responsible_user_id' => null, 'is_required' => true, 'action_label' => null, 'instructions' => null],
            ['id' => $last->id, 'name' => 'Done', 'status_key' => 'done', 'responsible_role' => null, 'responsible_user_id' => null, 'is_required' => true, 'action_label' => null, 'instructions' => null],
        ],
    ]))->assertRedirect();

    expect($ticket->fresh())->not->toBeNull()
        ->and($instance->fresh()->current_stage_id)->toBe($first->id)
        ->and($workflow->fresh()->version)->toBe(2);
});

test('workflow instance detail is accurate responsive data and role scoped', function () {
    Artisan::call('db:seed', ['--class' => WorkflowSeeder::class, '--force' => true]);
    $user = workflowUserWithRole('user');
    $unrelated = workflowUserWithRole('user');
    $admin = workflowUserWithRole('admin');
    $superadmin = workflowUserWithRole('superadmin');
    $target = now()->addDay()->startOfMinute();
    $ticket = Ticket::create([
        'ticket_no' => 'TCK-DETAIL-001',
        'title' => 'Workflow detail accuracy',
        'description' => 'Detailed workflow runtime information.',
        'status' => 'new',
        'priority' => 'high',
        'sla' => '1_day',
        'due_at' => $target,
        'requester_id' => $user->id,
        'assigned_id' => $user->id,
    ]);
    $instance = $ticket->workflowInstances()->with(['workflow.stages', 'histories'])->sole();

    $this->actingAs($user)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.instances.show', ['locale' => 'id', 'instance' => 'TCK-DETAIL-001']))
        ->assertOk()
        ->assertJsonPath('component', 'Workflows/InstanceShow')
        ->assertJsonPath('props.item.number', 'TCK-DETAIL-001')
        ->assertJsonPath('props.item.title', 'Workflow detail accuracy')
        ->assertJsonPath('props.item.priority', 'high')
        ->assertJsonPath('props.item.sla.label', '1_day')
        ->assertJsonPath('props.item.sla.state', 'on_track')
        ->assertJsonPath('props.item.timeline.0.state', 'active')
        ->assertJsonPath('props.item.history.0.event', 'started')
        ->assertJsonPath('props.item.can_update_workflow', false)
        ->assertJsonPath('props.item.can_update_status', true)
        ->assertJsonCount(7, 'props.item.timeline');

    $this->actingAs($unrelated)
        ->get(route('workflows.instances.show', ['locale' => 'id', 'instance' => 'TCK-DETAIL-001']))
        ->assertForbidden();

    $adminTicket = Ticket::create(['ticket_no' => 'TCK-DETAIL-ADMIN', 'title' => 'Admin detail', 'status' => 'new', 'requester_id' => $admin->id]);
    $adminInstance = $adminTicket->workflowInstances()->sole();
    $this->actingAs($admin)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.instances.show', ['locale' => 'id', 'instance' => 'TCK-DETAIL-ADMIN']))
        ->assertOk()
        ->assertJsonPath('props.item.can_update_workflow', true);

    $this->actingAs($superadmin)->withHeaders(workflowInertiaHeaders())
        ->get(route('workflows.instances.show', ['locale' => 'id', 'instance' => 'TCK-DETAIL-001']))
        ->assertOk()
        ->assertJsonPath('props.item.can_update_workflow', true);
});
