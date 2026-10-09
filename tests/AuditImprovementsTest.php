<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
        throw new RuntimeException('Tests require isolated SQLite memory database.');
    }
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
            'database/migrations/2026_08_19_000000_normalize_workflow_stage_positions.php',
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

    Schema::create('projects', function ($table) {
        $table->id();
        $table->string('title');
        $table->string('project_no')->nullable();
        $table->string('public_slug')->nullable();
        $table->text('description')->nullable();
        $table->string('status')->default('new');
        $table->date('end_date')->nullable();
        foreach (['ticket_id', 'created_by', 'requester_id', 'agent_id', 'assigned_id'] as $column) {
            $table->unsignedBigInteger($column)->nullable();
        }
        $table->timestamps();
    });
    Schema::create('project_pics', function ($table) {
        $table->id();
        $table->unsignedBigInteger('project_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamps();
    });
    Schema::create('notifications', function ($table) {
        $table->uuid('id')->primary();
        $table->string('type');
        $table->morphs('notifiable');
        $table->text('data');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });
    (require database_path('migrations/2026_10_10_000000_create_productivity_tables.php'))->up();
    (require database_path('migrations/2026_10_10_000001_enable_password_recovery.php'))->up();
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-10 10:00:00'));
});

function auditTicket(User $user, array $attributes = []): Ticket
{
    return Ticket::withoutEvents(fn () => Ticket::create(array_merge([
        'title' => 'Example', 'ticket_no' => 'TKT-'.uniqid(), 'requester_id' => $user->id,
        'status' => 'in_progress', 'due_at' => now()->subHour(),
    ], $attributes)));
}

test('SLA detail and nested work items respect user visibility', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    $outsider = User::factory()->create(['unit' => 'B']);
    $own = auditTicket($viewer);
    $other = auditTicket($outsider);
    DB::table('tasks')->insert(['title' => 'Secret task', 'ticket_id' => $own->id, 'assignee_id' => $outsider->id, 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
    $service = app(\App\Services\SLAReportService::class);
    expect($service->findDetail('ticket', $other->id, $viewer))->toBeNull();
    $detail = $service->findDetail('ticket', $own->id, $viewer);
    expect($detail['summary']['id'])->toBe($own->id)->and($detail['tasks'])->toBe([]);
    $this->actingAs($viewer)->get('/id/dashboard/sla/ticket/'.$other->id.'/pdf')->assertNotFound();
});

test('SLA filter matches same day overdue and legacy due date fallback', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    auditTicket($viewer);
    auditTicket($viewer, ['due_at' => null, 'due_date' => now()->toDateString()]);
    $service = app(\App\Services\SLAReportService::class);
    $late = $service->fetch('ticket', ['viewer_id' => $viewer->id, 'sla_status' => 'breached']);
    expect($late['stats']['total'])->toBe(1)->and($late['records']->items()[0]['sla']['status'])->toBe('breached');
    $pending = $service->fetch('ticket', ['viewer_id' => $viewer->id, 'sla_status' => 'pending']);
    expect($pending['stats']['total'])->toBe(1)->and($pending['records']->items()[0]['sla']['status'])->toBe('pending');
});

test('SLA summary counts all records and remains unchanged on page two', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    $row = ['title' => 'Bulk', 'status' => 'in_progress', 'requester_id' => $viewer->id, 'due_at' => now()->subHour(), 'created_at' => now(), 'updated_at' => now()];
    for ($i = 0; $i < 21; $i++) {
        DB::table('tickets')->insert(array_fill(0, 250, $row));
    }
    $service = app(\App\Services\SLAReportService::class);
    $first = $service->fetch('ticket', ['viewer_id' => $viewer->id]);
    \Illuminate\Pagination\Paginator::currentPageResolver(fn () => 2);
    $second = $service->fetch('ticket', ['viewer_id' => $viewer->id]);
    expect($first['stats']['total'])->toBe(5250)->and($second['stats'])->toBe($first['stats'])
        ->and($second['records']->count())->toBe(25);
});

test('notification read uses persisted records and rejects other users', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    $outsider = User::factory()->create(['unit' => 'B']);
    $id = (string) \Illuminate\Support\Str::uuid();
    $viewer->notifications()->create(['id' => $id, 'type' => 'test', 'data' => ['url' => url('/id/dashboard'), 'title' => 'Test']]);
    $this->actingAs($outsider)->postJson('/id/dashboard/notifications/'.$id.'/mark')->assertNotFound();
    $this->actingAs($viewer)->post('/id/dashboard/notifications/'.$id.'/read')->assertRedirect('/id/dashboard');
    expect($viewer->notifications()->first()->read_at)->not->toBeNull();
    $this->getJson('/id/dashboard/notifications')->assertOk()->assertJsonPath('notifications.unread_count', 0);
});

test('GET status and logout do not mutate state', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    $ticket = auditTicket($viewer);
    $this->actingAs($viewer)->get('/id/dashboard/tickets/'.$ticket->id.'/status/done')->assertStatus(405);
    expect($ticket->fresh()->status)->toBe('in_progress');
    $this->get('/id/logout')->assertRedirect('/id/dashboard');
    $this->assertAuthenticatedAs($viewer);
});

test('AI context only counts authorized data and omits personal profile', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    $outsider = User::factory()->create(['unit' => 'B']);
    auditTicket($viewer);
    auditTicket($outsider);
    \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['candidates' => [['content' => ['parts' => [['text' => 'OK']]]]]])]);
    (new \App\Services\GeminiAIService('fake-test-key'))->respond($viewer, 'Berapa tiket?');
    \Illuminate\Support\Facades\Http::assertSent(function ($request) use ($viewer) {
        $prompt = $request['systemInstruction']['parts'][0]['text'];

        return str_contains($prompt, '"total": 1') && ! str_contains($prompt, $viewer->email);
    });
});

test('search finds document numbers beyond first ten results without leaking other units', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    $outsider = User::factory()->create(['unit' => 'B']);
    for ($i = 0; $i < 25; $i++) {
        auditTicket($viewer, ['ticket_no' => 'FIND-'.$i]);
    }
    auditTicket($outsider, ['ticket_no' => 'FIND-SECRET']);
    $results = app(\App\Services\WorkListService::class)->fetch($viewer, ['query' => 'FIND-', 'type' => 'ticket']);
    expect($results->total())->toBe(25)->and($results->count())->toBe(20);
});

test('scheduler registers maintenance and reminder commands', function () {
    \Illuminate\Support\Facades\Artisan::call('schedule:list');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('notifications:prune-read', 'attachments:cleanup-tmp', 'reminders:send');
});

test('internal conversation and checklist writes are limited to agents', function () {
    $requester = User::factory()->create(['unit' => 'A']);
    $agent = User::factory()->create(['unit' => 'A']);
    $ticket = auditTicket($requester, ['agent_id' => $agent->id]);
    $base = '/id/dashboard/tickets/'.$ticket->id;
    $this->actingAs($requester)->postJson($base.'/messages', ['body' => 'Secret', 'internal' => true])->assertForbidden();
    $this->actingAs($agent)->postJson($base.'/messages', ['body' => 'Internal diagnosis', 'internal' => true])->assertCreated();
    $this->postJson($base.'/checklist', ['title' => 'Verify resolution'])->assertOk();
    $this->actingAs($requester)->getJson($base.'/collaboration')->assertOk()->assertJsonCount(0, 'messages.data')->assertJsonCount(1, 'checklist');
    $this->postJson($base.'/checklist', ['id' => 1, 'done' => true])->assertForbidden();
});

test('knowledge visibility excludes other units and unpublished drafts', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    $author = User::factory()->create(['unit' => 'B']);
    foreach ([['unit' => 'A', 'published' => true], ['unit' => 'B', 'published' => true], ['unit' => 'A', 'published' => false]] as $attributes) {
        \App\Models\KnowledgeEntry::create($attributes + ['title' => 'Guide', 'body' => 'Text', 'kind' => 'article', 'author_id' => $author->id]);
    }
    expect(\App\Models\KnowledgeEntry::visibleTo($viewer)->count())->toBe(1);
    $this->actingAs($viewer)->post('/id/dashboard/knowledge', ['title' => 'Forbidden'])->assertForbidden();
});

test('only requester can rate a completed ticket', function () {
    $requester = User::factory()->create(['unit' => 'A']);
    $agent = User::factory()->create(['unit' => 'A']);
    $ticket = auditTicket($requester, ['status' => 'done', 'agent_id' => $agent->id]);
    $url = '/id/dashboard/tickets/'.$ticket->id.'/feedback';
    $this->actingAs($agent)->postJson($url, ['rating' => 5, 'reopen_requested' => false])->assertForbidden();
    $this->actingAs($requester)->postJson($url, ['rating' => 4, 'reopen_requested' => false])->assertOk();
    $this->postJson($url, ['rating' => 3, 'reopen_requested' => true])->assertOk();
    expect(DB::table('ticket_feedback')->count())->toBe(1)->and(DB::table('ticket_feedback')->value('rating'))->toBe(3);
    $this->postJson($url, ['rating' => 3, 'reopen_requested' => true])->assertOk();
    expect($agent->notifications()->count())->toBe(1);
});

test('deadline alerts are deduplicated and exclude completed work', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    auditTicket($viewer, ['due_at' => now()->addHour()]);
    auditTicket($viewer, ['status' => 'done']);
    $service = app(\App\Services\DeadlineNotifier::class);
    expect($service->run())->toBe(1)->and($service->run())->toBe(0)->and($viewer->notifications()->count())->toBe(1);
});

test('password recovery tokens work once and invalid tokens never change passwords', function () {
    $user = User::factory()->create(['unit' => 'A']);
    $token = \Illuminate\Support\Facades\Password::createToken($user);
    $payload = ['email' => $user->email, 'token' => 'invalid-token', 'password' => 'Strong-Test-Password-2026!', 'password_confirmation' => 'Strong-Test-Password-2026!'];
    $old = $user->password;
    $this->post('/id/reset-password', $payload)->assertSessionHasErrors('email');
    expect($user->fresh()->password)->toBe($old);
    $payload['token'] = $token;
    $this->post('/id/reset-password', $payload)->assertRedirect('/id/login');
    expect(\Illuminate\Support\Facades\Hash::check($payload['password'], $user->fresh()->password))->toBeTrue();
    $this->post('/id/reset-password', $payload)->assertSessionHasErrors('email');
});

test('password request gives generic response and generates locale aware email', function () {
    \Illuminate\Support\Facades\Notification::fake();
    $user = User::factory()->create(['unit' => 'A']);
    $this->post('/id/forgot-password', ['email' => $user->email])->assertSessionHas('success');
    \Illuminate\Support\Facades\Notification::assertSentTo($user, \App\Notifications\PasswordRecoveryNotification::class,
        fn ($notification) => str_contains($notification->toMail($user)->actionUrl, '/id/reset-password/'));
    $this->post('/id/forgot-password', ['email' => 'missing@example.test'])->assertSessionHas('success');
});

test('AI retrieval excludes inaccessible tickets and unpublished knowledge', function () {
    $user = User::factory()->create(['unit' => 'A']);
    $other = User::factory()->create(['unit' => 'B']);
    auditTicket($user, ['title' => 'Printer authorized']);
    auditTicket($other, ['title' => 'Printer confidential']);
    \App\Models\KnowledgeEntry::create(['title' => 'Printer draft', 'body' => 'Hidden', 'kind' => 'article', 'unit' => 'A', 'published' => false, 'author_id' => $other->id]);
    $sources = app(\App\Services\AiSourceService::class)->find($user, 'Bantu printer');
    expect($sources)->toHaveCount(1)->and($sources[0]['title'])->toContain('authorized');
});

test('personal work query combines modules with pagination and ownership', function () {
    $user = User::factory()->create(['unit' => 'A']);
    $other = User::factory()->create(['unit' => 'A']);
    auditTicket($user);
    auditTicket($other);
    DB::table('tasks')->insert(['title' => 'My Task', 'task_no' => 'TSK-TEST', 'assignee_id' => $user->id, 'assigned_to' => json_encode([$user->id]), 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('tasks')->insert(['title' => 'Legacy task', 'assigned_to' => 'legacy-invalid-json', 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()]);
    $results = app(\App\Services\WorkListService::class)->fetch($user, [], true);
    expect($results->total())->toBe(2);
});

test('nested project SLA details do not reveal an inaccessible parent ticket', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    $other = User::factory()->create(['unit' => 'B']);
    $secret = auditTicket($other);
    $projectId = DB::table('projects')->insertGetId(['title' => 'Shared project', 'created_by' => $viewer->id, 'ticket_id' => $secret->id, 'status' => 'in_progress', 'end_date' => now()->addDay()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
    $taskId = DB::table('tasks')->insertGetId(['title' => 'Visible task', 'assignee_id' => $viewer->id, 'project_id' => $projectId, 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now()]);
    $detail = app(\App\Services\SLAReportService::class)->findDetail('task', $taskId, $viewer);
    expect($detail['project']['ticket_no'])->toBeNull()->and($detail['project']['sla']['status'])->toBe('pending');
});

test('admin can publish unit template with an authorized assignment suggestion', function () {
    $admin = User::factory()->create(['unit' => 'A']);
    $admin->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    $agent = User::factory()->create(['unit' => 'A']);
    $this->actingAs($admin)->post('/id/dashboard/knowledge', ['kind' => 'template', 'title' => 'Printer', 'body' => 'Describe printer problem', 'published' => true, 'checklist' => ['Verify connection'], 'default_assignee_id' => $agent->id])->assertSessionHasNoErrors();
    expect(\App\Models\KnowledgeEntry::first()->default_assignee_id)->toBe($agent->id);
});

test('task work list keeps confirmation items in its default scope', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    foreach (['in_progress', 'confirmation', 'done'] as $status) {
        DB::table('tasks')->insert(['title' => $status, 'assignee_id' => $viewer->id, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
    }
    $results = app(\App\Services\WorkListService::class)->fetch($viewer, ['type' => 'task', 'active_work' => true]);
    expect($results->total())->toBe(2);
});

test('authorized requester sees project confirmations even when another person created the project', function () {
    $viewer = User::factory()->create(['unit' => 'A']);
    $creator = User::factory()->create(['unit' => 'A']);
    DB::table('projects')->insert(['title' => 'Confirmation', 'requester_id' => $viewer->id, 'assigned_id' => $viewer->id, 'created_by' => $creator->id, 'status' => 'confirmation', 'end_date' => now()->addDay()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
    $results = app(\App\Services\WorkListService::class)->fetch($viewer, ['view' => 'waiting'], true);
    expect($results->total())->toBe(1)->and($results->items()[0]->due_at)->toContain('T');
});
