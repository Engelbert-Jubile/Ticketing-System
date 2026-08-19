<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Services\WorkflowRuntimeService;
use App\Support\UnitVisibility;
use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class WorkflowController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Workflow::class);
        $filters = [
            'search' => trim($request->string('search')->toString()),
            'type' => $request->string('type')->toString(),
            'status' => $request->string('status')->toString(),
        ];
        $filters['type'] = in_array($filters['type'], ['ticket', 'task'], true) ? $filters['type'] : '';
        $filters['status'] = in_array($filters['status'], WorkflowStatus::all(), true) ? $filters['status'] : '';

        $scopedItems = $this->scopedItemsQuery($request->user());
        $this->applyItemFilters($scopedItems, $filters, false);
        $summary = $this->itemSummary(clone $scopedItems);
        $this->applyItemStatusFilter($scopedItems, $filters['status']);

        $items = $this->withItemRelations($scopedItems)
            ->latest('workflow_instances.updated_at')
            ->paginate(min(50, max(5, $request->integer('per_page', 10))))
            ->withQueryString();
        $taskAssignees = User::query()
            ->whereIn('id', $this->taskAssigneeIds($items->getCollection()))
            ->get()
            ->keyBy('id');
        $locale = (string) $request->route('locale');
        $items->through(
            fn (WorkflowInstance $instance) => $this->instancePayload($instance, $locale, $taskAssignees, $request->user())
        );

        return Inertia::render('Workflows/Index', [
            'items' => $items,
            'summary' => $summary,
            'filters' => $filters,
            'can' => [
                'create' => $request->user()->can('create workflows'),
                'update' => $request->user()->can('update workflows'),
                'toggle' => $request->user()->can('toggle workflows'),
                'delete' => $request->user()->can('delete workflows'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Workflow::class);

        return Inertia::render('Workflows/Form', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Workflow::class);
        $data = $this->validated($request);
        $workflow = DB::transaction(function () use ($data, $request) {
            $workflow = Workflow::create([...$this->attributes($data), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $this->replaceStages($workflow, $data['stages']);
            $this->history($workflow, $request, 'created', ['after' => $workflow->only(['name', 'code', 'entity_type', 'is_active'])]);

            return $workflow;
        });

        return redirect()->route('workflows.show', ['locale' => $request->route('locale'), 'workflow' => $workflow])->with('success', 'Workflow berhasil dibuat.');
    }

    public function showInstance(Request $request, string $locale, string $instance): Response|RedirectResponse
    {
        $legacyIdentifier = ctype_digit($instance);
        $instance = $this->resolveInstance($instance);
        abort_unless($this->scopedItemsQuery($request->user())->whereKey($instance->id)->exists(), 403);
        if ($legacyIdentifier) {
            return redirect()->route('workflows.instances.show', ['locale' => $locale, 'instance' => $this->instancePublicIdentifier($instance)]);
        }
        $instance->load([
            'workflow.stages.responsibleUser',
            'workflow.creator',
            'workflow.updater',
            'currentStage',
            'histories.actor',
            'subject' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Ticket::class => ['requester', 'agent', 'assignee', 'assignedUsers', 'tasks'],
                    Task::class => ['requester', 'assignee', 'ticket'],
                ]);
            },
        ]);

        $taskAssignees = User::query()
            ->whereIn('id', $this->taskAssigneeIds(collect([$instance])))
            ->get()
            ->keyBy('id');
        $item = $this->instancePayload($instance, $locale, $taskAssignees, $request->user());
        $subject = $instance->subject;
        $isTicket = $subject instanceof Ticket;
        $targetDate = $isTicket ? ($subject->due_at ?? $subject->due_date) : ($subject->due_at ?? $subject->end_date);
        $finishedAt = $isTicket ? ($subject->finish_at ?? $subject->finish_date) : $subject->completed_at;
        $finishedAt = $finishedAt ? \Illuminate\Support\Carbon::parse($finishedAt) : null;
        $sla = $isTicket ? $subject->sla : $subject->ticket?->sla;
        $historyByStatus = $instance->histories->groupBy('to_status');
        $currentPosition = (int) ($instance->currentStage?->position ?? 0);

        $item['description'] = $subject->description;
        $item['sla'] = [
            'label' => $sla ?: 'Tidak ditentukan',
            'started_at' => $instance->started_at?->toIso8601String(),
            'target_at' => $targetDate?->toIso8601String(),
            'finished_at' => $finishedAt?->toIso8601String(),
            'state' => $instance->status === 'completed'
                ? 'completed'
                : ($instance->status === 'cancelled' ? 'cancelled' : ($targetDate?->isPast() ? 'overdue' : ($targetDate ? 'on_track' : 'not_set'))),
        ];
        $item['related'] = $isTicket ? [
            'type' => $subject->type,
            'reason' => $subject->reason,
            'tasks_count' => $subject->tasks->count(),
            'parent_number' => null,
            'parent_title' => null,
            'parent_url' => null,
        ] : [
            'type' => 'task',
            'reason' => null,
            'tasks_count' => null,
            'parent_number' => $subject->ticket?->ticket_no,
            'parent_title' => $subject->ticket?->title,
            'parent_url' => $subject->ticket ? route('tickets.show', ['locale' => $locale, 'ticket' => $subject->ticket]) : null,
        ];
        $item['timeline'] = $instance->workflow->stages->map(function (WorkflowStage $stage) use ($instance, $historyByStatus, $currentPosition): array {
            $entries = $historyByStatus->get($stage->status_key, collect())->sortBy('created_at');
            $active = (int) $instance->current_stage_id === (int) $stage->id;
            $reached = $active || $entries->isNotEmpty() || $stage->position < $currentPosition;
            $leftAt = $instance->histories->first(fn ($history) => $history->from_status === $stage->status_key)?->created_at;

            return [
                'id' => $stage->id,
                'position' => $stage->position,
                'name' => $stage->name,
                'status_key' => $stage->status_key,
                'status_label' => WorkflowStatus::label($stage->status_key),
                'responsible_role' => $stage->responsible_role,
                'responsible_user' => $stage->responsibleUser?->display_name,
                'is_required' => (bool) $stage->is_required,
                'notes' => $stage->instructions,
                'action_label' => $stage->action_label,
                'state' => $active ? 'active' : ($reached ? 'completed' : 'pending'),
                'entered_at' => ($active ? ($instance->stage_started_at ?? $entries->first()?->created_at ?? $instance->started_at) : $entries->first()?->created_at)?->toIso8601String(),
                'completed_at' => $leftAt?->toIso8601String(),
            ];
        })->values();
        $item['history'] = $instance->histories->map(fn ($history) => [
            'id' => $history->id,
            'event' => $history->event,
            'actor_name' => $history->actor?->display_name ?? 'Sistem',
            'from_status' => $history->from_status,
            'to_status' => $history->to_status,
            'from_stage' => $history->from_stage_name,
            'to_stage' => $history->to_stage_name,
            'metadata' => $history->metadata,
            'created_at' => $history->created_at?->toIso8601String(),
        ])->values();

        return Inertia::render('Workflows/InstanceShow', [
            'item' => $item,
            'workflow' => [
                ...$this->payload($instance->workflow),
                'description' => $instance->workflow->description,
            ],
        ]);
    }

    public function legacyWorkflow(Request $request, string $locale, string $workflow): RedirectResponse
    {
        $definition = Workflow::withTrashed()->where('uuid', $workflow)->orWhere('id', $workflow)->firstOrFail();
        $this->authorize('view', $definition);

        return redirect()->route('workflows.show', ['locale' => $locale, 'workflow' => $definition]);
    }
    public function show(Request $request, string $locale, Workflow $workflow): Response
    {
        $this->authorize('view', $workflow);
        $workflow->load([
            'stages.responsibleUser',
            'creator',
            'updater',
            'histories.actor',
            'instanceHistories.actor',
        ]);

        $scopedItems = $this->scopedItemsQuery($request->user())
            ->where('workflow_instances.workflow_id', $workflow->id);
        $summary = $this->itemSummary(clone $scopedItems);
        $stageCounts = collect($summary['stages'])->keyBy('status_key');
        $workflow->setAttribute('instances_count', $summary['total']);
        $workflow->setAttribute('running_instances_count', $summary['in_progress']);
        $workflow->setAttribute('completed_instances_count', $summary['completed']);
        $workflow->stages->each(function ($stage) use ($stageCounts): void {
            $stage->setAttribute('instances_count', (int) data_get($stageCounts, $stage->status_key.'.count', 0));
        });

        $instances = $this->withItemRelations($scopedItems)
            ->latest('workflow_instances.updated_at')
            ->paginate(10)
            ->withQueryString();

        $taskAssignees = User::query()
            ->whereIn('id', $this->taskAssigneeIds($instances->getCollection()))
            ->get()
            ->keyBy('id');
        $instances->through(
            fn (WorkflowInstance $instance) => $this->instancePayload($instance, $locale, $taskAssignees, $request->user())
        );

        return Inertia::render('Workflows/Show', [
            'workflow' => $this->payload($workflow, true),
            'instances' => $instances,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'users' => User::query()->orderBy('first_name')->get()->map(fn (User $user) => ['id' => $user->id, 'name' => $user->display_name]),
            'statusOptions' => collect(WorkflowStatus::all())->map(fn ($value) => ['value' => $value, 'label' => WorkflowStatus::label($value)]),
            'can' => [
                'update' => $request->user()->can('update', $workflow),
                'toggle' => $request->user()->can('toggle', $workflow),
                'delete' => $request->user()->can('delete', $workflow) && ! $workflow->instances()->exists(),
                'structure' => $request->user()->can('update', $workflow),
            ],
        ]);
    }

    public function edit(string $locale, Workflow $workflow): Response
    {
        $this->authorize('update', $workflow);
        $workflow->load(['stages.responsibleUser', 'creator']);

        return Inertia::render('Workflows/Form', [...$this->formOptions(), 'workflow' => $this->payload($workflow, true)]);
    }

    public function update(Request $request, string $locale, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow);
        $data = $this->validated($request, $workflow);
        try {
            DB::transaction(function () use ($data, $request, $workflow) {
                $before = $workflow->only(['name', 'code', 'entity_type', 'description', 'trigger_conditions', 'is_active']);
                $workflow->update([...$this->attributes($data), 'version' => $workflow->version + 1, 'updated_by' => $request->user()->id]);
                $this->replaceStages($workflow, $data['stages']);
                $this->assertStagesPersisted($workflow, $data['stages']);
                $this->history($workflow, $request, 'updated', ['before' => $before, 'after' => $workflow->fresh()->only(array_keys($before))]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Workflow gagal diperbarui. Tidak ada perubahan yang disimpan.');
        }
        app(WorkflowRuntimeService::class)->syncWorkflow($workflow->fresh());

        return redirect()->route('workflows.show', ['locale' => $locale, 'workflow' => $workflow])->with('success', 'Workflow berhasil diperbarui.');
    }

    public function toggle(Request $request, string $locale, Workflow $workflow): RedirectResponse
    {
        $this->authorize('toggle', $workflow);
        $workflow->update(['is_active' => ! $workflow->is_active, 'updated_by' => $request->user()->id]);
        $this->history($workflow, $request, $workflow->is_active ? 'activated' : 'deactivated', ['is_active' => $workflow->is_active]);

        return back()->with('success', $workflow->is_active ? 'Workflow diaktifkan.' : 'Workflow dinonaktifkan.');
    }

    public function destroy(Request $request, string $locale, Workflow $workflow): RedirectResponse
    {
        $this->authorize('delete', $workflow);
        if ($workflow->instances()->exists()) {
            return back()->with('error', 'Workflow yang sudah digunakan tidak dapat dihapus. Nonaktifkan workflow sebagai gantinya.');
        }
        $this->history($workflow, $request, 'deleted', ['workflow' => $workflow->only(['name', 'code', 'entity_type', 'version'])]);
        $workflow->delete();

        return redirect()->route('workflows.index', ['locale' => $locale])->with('success', 'Workflow berhasil dihapus.');
    }

    public function updateInstanceStatus(Request $request, string $locale, string $instance): JsonResponse|RedirectResponse
    {
        $instance = $this->resolveInstance($instance);
        abort_unless($this->scopedItemsQuery($request->user())->whereKey($instance->id)->exists(), 403);
        $instance->load(['workflow.stages', 'currentStage', 'subject']);
        abort_unless($instance->workflow?->is_active, 422, 'Workflow sedang nonaktif.');
        $data = $request->validate(['status' => ['required', Rule::in(WorkflowStatus::all())]]);
        $subject = $instance->subject;
        abort_unless($subject instanceof Ticket || $subject instanceof Task, 422);
        $from = WorkflowStatus::normalize($subject->status);
        $to = WorkflowStatus::normalize($data['status']);
        if (! WorkflowStatus::canTransition($from, $to)) {
            throw ValidationException::withMessages(['status' => 'Transisi status tidak valid.']);
        }
        if (! $subject->canUserSetStatus($request->user(), $to)) {
            abort(403);
        }
        if (! $instance->workflow->stages->contains('status_key', $to)) {
            throw ValidationException::withMessages(['status' => 'Tahap tujuan tidak tersedia pada workflow ini.']);
        }
        $subject->update(['status' => $to]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Status workflow berhasil diperbarui.']);
        }

        return back()->with('success', 'Status workflow berhasil diperbarui.');
    }

    public function storeStage(Request $request, string $locale, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow);
        $data = $this->validatedStage($request, $workflow);
        DB::transaction(function () use ($workflow, $data, $request) {
            $stages = $workflow->stages()->get()->values();
            $workflow->stages()->update(['position' => DB::raw('position + 100')]);
            $stage = $workflow->stages()->create([...$data, 'position' => $stages->count() + 101]);
            $stages->splice($data['position'] - 1, 0, [$stage]);
            foreach ($stages as $index => $item) {
                $item->update(['position' => $index + 1]);
            } $this->touchStructure($workflow, $request, 'stage_created', ['stage' => $stage->only(['name', 'status_key', 'position'])]);
        });

        return back()->with('success', 'Tahap workflow berhasil ditambahkan.');
    }

    public function updateStage(Request $request, string $locale, Workflow $workflow, WorkflowStage $stage): RedirectResponse
    {
        $this->authorize('update', $workflow);
        abort_unless((int) $stage->workflow_id === (int) $workflow->id, 404);
        $data = $this->validatedStage($request, $workflow, $stage);
        DB::transaction(function () use ($workflow, $stage, $data, $request) {
            $before = $stage->only(['name', 'status_key', 'responsible_role', 'responsible_user_id', 'is_required', 'action_label', 'instructions']);
            $previousStatus = $stage->status_key;
            $position = (int) $data['position'];
            unset($data['position']);
            $stage->update($data);
            $this->migrateStageRuntimeStatus($stage, $previousStatus, $stage->status_key);
            $ordered = $workflow->stages()->whereKeyNot($stage->id)->get()->values();
            $ordered->splice($position - 1, 0, [$stage]);
            $workflow->stages()->update(['position' => DB::raw('position + 100')]);
            foreach ($ordered as $index => $item) {
                $item->update(['position' => $index + 1]);
            }
            $this->touchStructure($workflow, $request, 'stage_updated', ['before' => $before, 'after' => $stage->fresh()->only(array_keys($before))]);
        });

        return back()->with('success', 'Tahap workflow berhasil diperbarui.');
    }

    public function destroyStage(Request $request, string $locale, Workflow $workflow, WorkflowStage $stage): RedirectResponse
    {
        $this->authorize('update', $workflow);
        abort_unless((int) $stage->workflow_id === (int) $workflow->id, 404);
        if ($workflow->stages()->count() <= 2) {
            return back()->with('error', 'Workflow minimal memiliki dua tahap.');
        }
        if ($stage->instances()->exists()) {
            return back()->with('error', 'Tahap yang sedang digunakan tidak dapat dihapus.');
        }
        DB::transaction(function () use ($workflow, $stage, $request) {
            $snapshot = $stage->only(['name', 'status_key', 'position']);
            $stage->delete();
            $workflow->stages()->get()->each(fn ($item, $index) => $item->update(['position' => $index + 1]));
            $this->touchStructure($workflow, $request, 'stage_deleted', ['stage' => $snapshot]);
        });

        return back()->with('success', 'Tahap workflow berhasil dihapus.');
    }

    public function reorderStages(Request $request, string $locale, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow);
        $ids = $request->validate(['stage_ids' => ['required', 'array', 'min:2'], 'stage_ids.*' => ['required', 'integer', 'distinct']])['stage_ids'];
        if ($workflow->stages()->pluck('id')->sort()->values()->all() !== collect($ids)->map('intval')->sort()->values()->all()) {
            throw ValidationException::withMessages(['stage_ids' => 'Daftar tahap tidak valid.']);
        }
        DB::transaction(function () use ($workflow, $ids, $request) {
            $workflow->stages()->update(['position' => DB::raw('position + 100')]);
            foreach ($ids as $index => $id) {
                $workflow->stages()->whereKey($id)->update(['position' => $index + 1]);
            } $this->touchStructure($workflow, $request, 'stages_reordered', ['stage_ids' => $ids]);
        });

        return back()->with('success', 'Urutan tahap berhasil diperbarui.');
    }

    private function touchStructure(Workflow $workflow, Request $request, string $event, array $changes): void
    {
        $workflow->increment('version');
        $workflow->update(['updated_by' => $request->user()->id]);
        $this->history($workflow, $request, $event, $changes);
    }

    private function validatedStage(Request $request, Workflow $workflow, ?WorkflowStage $stage = null): array
    {
        return $request->validate([
            'position' => ['required', 'integer', 'min:1', 'max:'.($workflow->stages()->count() + ($stage ? 0 : 1))], 'name' => ['required', 'string', 'max:100'],
            'status_key' => ['required', Rule::in(WorkflowStatus::all()), Rule::unique('workflow_stages', 'status_key')->where('workflow_id', $workflow->id)->ignore($stage?->id)],
            'responsible_role' => ['nullable', 'string', 'max:80', 'exists:roles,name'], 'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_required' => ['required', 'boolean'], 'action_label' => ['nullable', 'string', 'max:100'], 'instructions' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validated(Request $request, ?Workflow $workflow = null): array
    {
        $entityTypes = $workflow && $workflow->instances()->exists()
            ? [$workflow->entity_type]
            : ['ticket', 'task'];

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('workflows', 'code')->ignore($workflow?->id)],
            'entity_type' => ['required', Rule::in($entityTypes)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'trigger_conditions' => ['array', 'max:10'],
            'trigger_conditions.*.field' => ['required', Rule::in(['priority', 'status', 'type'])],
            'trigger_conditions.*.operator' => ['required', Rule::in(['equals', 'not_equals', 'contains'])],
            'trigger_conditions.*.value' => ['required', 'string', 'max:100'],
            'stages' => ['required', 'array', 'min:2', 'max:20'],
            'stages.*.id' => ['nullable', 'integer', Rule::exists('workflow_stages', 'id')->where('workflow_id', $workflow?->id)],
            'stages.*.name' => ['required', 'string', 'max:100'],
            'stages.*.status_key' => ['required', 'distinct', Rule::in(WorkflowStatus::all())],
            'stages.*.responsible_role' => ['nullable', 'string', 'max:80', 'exists:roles,name'],
            'stages.*.responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'stages.*.is_required' => ['sometimes', 'boolean'],
            'stages.*.action_label' => ['nullable', 'string', 'max:100'],
            'stages.*.instructions' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function attributes(array $data): array
    {
        return ['name' => $data['name'], 'code' => strtoupper($data['code']), 'entity_type' => $data['entity_type'], 'description' => $data['description'] ?? null, 'trigger_conditions' => $data['trigger_conditions'] ?? [], 'is_active' => $data['is_active']];
    }

    private function replaceStages(Workflow $workflow, array $stages): void
    {
        $existing = $workflow->stages()->get();
        $workflow->stages()->update(['position' => DB::raw('position + 100')]);
        $kept = [];
        $statusChanges = [];

        foreach (array_values($stages) as $index => $data) {
            $stage = isset($data['id'])
                ? $existing->firstWhere('id', (int) $data['id'])
                : $existing->first(fn (WorkflowStage $item) => $item->status_key === $data['status_key'] && ! in_array($item->id, $kept, true));
            $attributes = [
                'position' => $index + 1,
                'name' => $data['name'],
                'status_key' => $data['status_key'],
                'responsible_role' => $data['responsible_role'] ?? null,
                'responsible_user_id' => $data['responsible_user_id'] ?? null,
                'is_required' => $data['is_required'] ?? true,
                'action_label' => $data['action_label'] ?? null,
                'instructions' => $data['instructions'] ?? null,
            ];
            if ($stage) {
                $previousStatus = $stage->status_key;
                $stage->update($attributes);
                $statusChanges[] = [$stage, $previousStatus, $stage->status_key];
            } else {
                $stage = $workflow->stages()->create($attributes);
            }
            $kept[] = $stage->id;
        }

        foreach ($existing->whereNotIn('id', $kept) as $removed) {
            if ($removed->instances()->exists()) {
                throw ValidationException::withMessages(['stages' => 'Tahap yang sedang digunakan tidak dapat dihapus.']);
            }
            $removed->delete();
        }

        foreach ($statusChanges as [$stage, $from, $to]) {
            $this->migrateStageRuntimeStatus($stage, $from, $to);
        }
    }

    private function migrateStageRuntimeStatus(WorkflowStage $stage, string $from, string $to): void
    {
        $from = WorkflowStatus::normalize($from);
        $to = WorkflowStatus::normalize($to);
        if ($from === $to) {
            return;
        }

        $stage->instances()->with('subject')->get()->each(function (WorkflowInstance $instance) use ($from, $to): void {
            $subject = $instance->subject;
            if (($subject instanceof Ticket || $subject instanceof Task) && WorkflowStatus::normalize($subject->status) === $from) {
                $subject->status = $to;
                $subject->saveQuietly();
            }
        });
    }

    private function assertStagesPersisted(Workflow $workflow, array $stages): void
    {
        $persisted = $workflow->stages()->get()->keyBy('id');
        foreach ($stages as $stage) {
            if (empty($stage['id'])) {
                continue;
            }
            $saved = $persisted->get((int) $stage['id']);
            if (! $saved || $saved->status_key !== WorkflowStatus::normalize($stage['status_key'])) {
                throw ValidationException::withMessages([
                    'stages' => 'Perubahan status tahap gagal tersimpan. Silakan muat ulang dan coba lagi.',
                ]);
            }
        }
    }

    private function history(Workflow $workflow, Request $request, string $event, array $changes): void
    {
        $workflow->histories()->create(['user_id' => $request->user()->id, 'event' => $event, 'changes' => $changes]);
    }

    private function formOptions(): array
    {
        return [
            'workflow' => null,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'users' => User::query()->orderBy('first_name')->get()->map(fn (User $user) => ['id' => $user->id, 'name' => $user->display_name]),
            'statusOptions' => collect(WorkflowStatus::all())->map(fn ($value) => ['value' => $value, 'label' => WorkflowStatus::label($value)]),
        ];
    }

    private function payload(Workflow $workflow, bool $detailed = false): array
    {
        $display = fn ($user) => $user?->display_name ?? 'Sistem';
        $payload = [
            'uuid' => $workflow->uuid, 'slug' => $workflow->slug, 'name' => $workflow->name, 'code' => $workflow->code, 'entity_type' => $workflow->entity_type,
            'description' => $workflow->description, 'trigger_conditions' => $workflow->trigger_conditions ?? [], 'is_active' => $workflow->is_active,
            'version' => $workflow->version, 'stages_count' => $workflow->stages_count ?? ($workflow->relationLoaded('stages') ? $workflow->stages->count() : 0),
            'instances_count' => $workflow->instances_count ?? 0, 'creator_name' => $display($workflow->creator), 'updater_name' => $display($workflow->updater),
            'created_at' => $workflow->created_at?->toIso8601String(), 'updated_at' => $workflow->updated_at?->toIso8601String(),
            'total_items_count' => $workflow->instances_count ?? 0,
            'running_items_count' => $workflow->running_instances_count ?? 0,
            'completed_items_count' => $workflow->completed_instances_count ?? 0,
            'stage_counts' => $workflow->relationLoaded('stages') ? $workflow->stages->map(fn ($stage) => [
                'status_key' => $stage->status_key,
                'label' => WorkflowStatus::label($stage->status_key),
                'count' => (int) ($stage->instances_count ?? 0),
            ])->values() : [],
        ];
        if ($detailed) {
            $payload['stages'] = $workflow->stages->map(fn ($stage) => [
                'id' => $stage->id, 'position' => $stage->position, 'name' => $stage->name, 'status_key' => $stage->status_key,
                'responsible_role' => $stage->responsible_role, 'responsible_user_id' => $stage->responsible_user_id,
                'responsible_user_name' => $stage->responsible_user_id ? $display($stage->responsibleUser) : null,
                'is_required' => (bool) $stage->is_required,
                'action_label' => $stage->action_label, 'instructions' => $stage->instructions,
                'instances_count' => (int) ($stage->instances_count ?? 0),
                'created_at' => $stage->created_at?->toIso8601String(), 'updated_at' => $stage->updated_at?->toIso8601String(),
            ])->values();
            $definitionHistory = $workflow->relationLoaded('histories') ? $workflow->histories->map(fn ($history) => [
                'id' => 'definition-'.$history->id, 'event' => $history->event, 'actor_name' => $display($history->actor), 'changes' => $history->changes, 'created_at' => $history->created_at?->toIso8601String(), 'scope' => 'definition',
            ]) : collect();
            $runtimeHistory = $workflow->relationLoaded('instanceHistories') ? $workflow->instanceHistories->map(fn ($history) => [
                'id' => 'runtime-'.$history->id, 'event' => $history->event, 'actor_name' => $display($history->actor),
                'changes' => ['from' => $history->from_stage_name, 'to' => $history->to_stage_name, 'from_status' => $history->from_status, 'to_status' => $history->to_status],
                'created_at' => $history->created_at?->toIso8601String(), 'scope' => 'runtime',
            ]) : collect();
            $payload['histories'] = $definitionHistory->concat($runtimeHistory)->sortByDesc('created_at')->take(100)->values();
        }

        return $payload;
    }

    private function resolveInstance(string $identifier): WorkflowInstance
    {
        $query = WorkflowInstance::query();
        if (ctype_digit($identifier)) {
            return $query->findOrFail((int) $identifier);
        }

        return $query->where(function (Builder $builder) use ($identifier): void {
            $builder->whereHasMorph('subject', [Ticket::class], fn (Builder $subject) => $subject->where('ticket_no', $identifier))
                ->orWhereHasMorph('subject', [Task::class], fn (Builder $subject) => $subject->where('task_no', $identifier));
        })->firstOrFail();
    }

    private function instancePublicIdentifier(WorkflowInstance $instance): string
    {
        $instance->loadMissing('subject');

        return $instance->subject instanceof Ticket
            ? ($instance->subject->ticket_no ?: (string) $instance->id)
            : ($instance->subject->task_no ?: (string) $instance->id);
    }
    private function scopedItemsQuery(User $user): Builder
    {
        return WorkflowInstance::query()->where(function (Builder $instanceQuery) use ($user) {
            $instanceQuery
                ->where(function (Builder $ticketInstances) use ($user) {
                    $ticketInstances
                        ->where('subject_type', Ticket::class)
                        ->whereHasMorph('subject', [Ticket::class], function (Builder $ticketQuery) use ($user) {
                            UnitVisibility::scopeWorkflowTickets($ticketQuery, $user);
                        });
                })
                ->orWhere(function (Builder $taskInstances) use ($user) {
                    $taskInstances
                        ->where('subject_type', Task::class)
                        ->whereHasMorph('subject', [Task::class], function (Builder $taskQuery) use ($user) {
                            UnitVisibility::scopeWorkflowTasks($taskQuery, $user);
                        });
                });
        });
    }

    private function applyItemFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if ($filters['type'] !== '') {
            $query->where('subject_type', $filters['type'] === 'ticket' ? Ticket::class : Task::class);
        }

        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery
                    ->whereHasMorph('subject', [Ticket::class], function (Builder $ticketQuery) use ($search) {
                        $ticketQuery->where(function (Builder $subjectQuery) use ($search) {
                            $subjectQuery->where('title', 'like', $search)
                                ->orWhere('ticket_no', 'like', $search)
                                ->orWhereHas('requester', fn (Builder $userQuery) => $this->searchUser($userQuery, $search))
                                ->orWhereHas('agent', fn (Builder $userQuery) => $this->searchUser($userQuery, $search))
                                ->orWhereHas('assignee', fn (Builder $userQuery) => $this->searchUser($userQuery, $search))
                                ->orWhereHas('assignedUsers', fn (Builder $userQuery) => $this->searchUser($userQuery, $search));
                        });
                    })
                    ->orWhereHasMorph('subject', [Task::class], function (Builder $taskQuery) use ($search) {
                        $taskQuery->where(function (Builder $subjectQuery) use ($search) {
                            $subjectQuery->where('title', 'like', $search)
                                ->orWhere('task_no', 'like', $search)
                                ->orWhereHas('requester', fn (Builder $userQuery) => $this->searchUser($userQuery, $search))
                                ->orWhereHas('assignee', fn (Builder $userQuery) => $this->searchUser($userQuery, $search));
                        });
                    });
            });
        }

        if ($includeStatus) {
            $this->applyItemStatusFilter($query, $filters['status']);
        }
    }

    private function applyItemStatusFilter(Builder $query, string $status): void
    {
        if ($status !== '') {
            $query->whereHas('currentStage', fn (Builder $stageQuery) => $stageQuery->where('status_key', $status));
        }
    }

    private function searchUser(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $userQuery) use ($search) {
            $userQuery->where('first_name', 'like', $search)
                ->orWhere('last_name', 'like', $search)
                ->orWhere('username', 'like', $search)
                ->orWhere('email', 'like', $search);
        });
    }

    private function itemSummary(Builder $query): array
    {
        $stageRows = (clone $query)
            ->join('workflow_stages', 'workflow_stages.id', '=', 'workflow_instances.current_stage_id')
            ->selectRaw('workflow_stages.status_key, COUNT(*) as aggregate')
            ->groupBy('workflow_stages.status_key')
            ->pluck('aggregate', 'status_key');

        return [
            'total' => (clone $query)->count(),
            'in_progress' => (clone $query)->where('workflow_instances.status', 'running')->count(),
            'completed' => (clone $query)->where('workflow_instances.status', 'completed')->count(),
            'stages' => collect(WorkflowStatus::all())->map(fn (string $status) => [
                'status_key' => $status,
                'label' => WorkflowStatus::label($status),
                'count' => (int) ($stageRows[$status] ?? 0),
            ])->values(),
        ];
    }

    private function withItemRelations(Builder $query): Builder
    {
        return $query->with([
            'workflow.stages',
            'currentStage',
            'subject' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Ticket::class => ['requester', 'agent', 'assignee', 'assignedUsers'],
                    Task::class => ['requester', 'assignee', 'ticket'],
                ]);
            },
        ]);
    }

    private function taskAssigneeIds($instances): array
    {
        return $instances
            ->filter(fn (WorkflowInstance $instance) => $instance->subject instanceof Task)
            ->flatMap(function (WorkflowInstance $instance): array {
                $task = $instance->subject;
                $assigned = is_string($task->assigned_to)
                    ? json_decode($task->assigned_to, true)
                    : $task->assigned_to;
                $ids = is_array($assigned) ? $assigned : [];
                if ($task->assignee_id) {
                    $ids[] = $task->assignee_id;
                }

                return array_filter(array_map('intval', $ids));
            })
            ->unique()
            ->values()
            ->all();
    }

    private function instancePayload(WorkflowInstance $instance, string $locale, $taskAssignees, User $viewer): array
    {
        $subject = $instance->subject;
        $isTicket = $subject instanceof Ticket;
        $requester = $subject->requester?->display_name ?? '—';

        if ($isTicket) {
            $pic = $subject->assignedUsers->pluck('display_name');
            if ($pic->isEmpty() && $subject->assignee) {
                $pic->push($subject->assignee->display_name);
            }
            if ($pic->isEmpty() && $subject->agent) {
                $pic->push($subject->agent->display_name);
            }
            $number = $subject->ticket_no ?: 'Ticket #'.$subject->id;
            $detailUrl = route('tickets.show', ['locale' => $locale, 'ticket' => $subject]);
            $editUrl = route('tickets.edit', ['locale' => $locale, 'ticket' => $subject]);
            $targetDate = $subject->due_at ?? $subject->due_date;
        } else {
            $ids = $this->taskAssigneeIds(collect([$instance]));
            $pic = collect($ids)->map(fn ($id) => $taskAssignees->get($id)?->display_name)->filter();
            $number = $subject->task_no ?: 'Task #'.$subject->id;
            $detailUrl = route('tasks.view', ['locale' => $locale, 'task' => $subject]);
            $editUrl = route('tasks.edit', ['locale' => $locale, 'task' => $subject->public_slug]);
            $targetDate = $subject->due_at ?? $subject->end_date;
        }

        $status = WorkflowStatus::normalize($subject->status);
        $statusOptions = collect(WorkflowStatus::allowedTransitions($status))
            ->filter(fn (string $candidate) => $instance->workflow?->is_active && $subject->canUserSetStatus($viewer, $candidate))
            ->filter(fn (string $candidate) => $instance->workflow?->stages->contains('status_key', $candidate))
            ->map(fn (string $candidate) => ['value' => $candidate, 'label' => WorkflowStatus::label($candidate)])
            ->values();

        return [
            'id' => $instance->id,
            'workflow_uuid' => $instance->workflow?->uuid,
            'workflow_slug' => $instance->workflow?->slug,
            'workflow_name' => $instance->workflow?->name,
            'workflow_active' => (bool) $instance->workflow?->is_active,
            'number' => $number,
            'title' => $subject->title,
            'type' => $isTicket ? 'ticket' : 'task',
            'requester' => $requester,
            'creator' => $requester,
            'pic' => $pic->unique()->values()->implode(', ') ?: '—',
            'priority' => $subject->priority ?: '-',
            'target_date' => $targetDate?->toIso8601String(),
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'stage_name' => $instance->currentStage?->name ?? WorkflowStatus::label($status),
            'created_at' => $subject->created_at?->toIso8601String(),
            'updated_at' => $subject->updated_at?->toIso8601String(),
            'date' => $subject->updated_at?->toIso8601String(),
            'stage_started_at' => $instance->stage_started_at?->toIso8601String(),
            'detail_url' => route('workflows.instances.show', ['locale' => $locale, 'instance' => $number]),
            'related_url' => $detailUrl,
            'edit_url' => $editUrl,
            'status_options' => $statusOptions,
            'status_update_url' => route('workflows.instances.status', ['locale' => $locale, 'instance' => $number]),
            'can_view_workflow' => $viewer->can('view', $instance->workflow),
            'can_edit' => $viewer->can($isTicket ? 'update tickets' : 'update tasks'),
            'can_update_status' => $statusOptions->isNotEmpty(),
            'can_update_workflow' => $viewer->can('update', $instance->workflow),
            'can_toggle_workflow' => $viewer->can('toggle', $instance->workflow),
            'can_delete_workflow' => $viewer->can('delete', $instance->workflow) && ! $instance->workflow->instances()->exists(),
            'workflow_toggle_url' => route('workflows.toggle', ['locale' => $locale, 'workflow' => $instance->workflow]),
            'workflow_delete_url' => route('workflows.destroy', ['locale' => $locale, 'workflow' => $instance->workflow]),
            'workflow_url' => route('workflows.show', ['locale' => $locale, 'workflow' => $instance->workflow]),
            'workflow_edit_url' => route('workflows.edit', ['locale' => $locale, 'workflow' => $instance->workflow]),
        ];
    }
}
