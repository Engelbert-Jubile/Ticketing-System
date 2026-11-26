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
use App\Support\RoleHelpers;
use App\Support\UnitVisibility;
use App\Support\WorkflowDisplay;
use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'type' => $request->string('type')->toString() === 'task' ? 'task' : 'ticket',
            'status' => $request->string('status')->toString(),
        ];
        if ($filters['type'] === 'ticket' && $filters['status'] !== '') {
            $filters['status'] = WorkflowStatus::normalize($filters['status']);
        } elseif (! in_array($filters['status'], WorkflowStatus::all(), true)) {
            $filters['status'] = '';
        }

        [$items, $summary] = $filters['type'] === 'task'
            ? $this->taskIndexData($request, $filters)
            : $this->ticketIndexData($request, $filters);

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

    private function ticketIndexData(Request $request, array $filters): array
    {
        $viewer = $request->user();
        $locale = (string) $request->route('locale');
        $perPage = min(50, max(5, $request->integer('per_page', 15)));
        $query = UnitVisibility::scopeTickets(Ticket::query(), $viewer)->searchReport($filters['search']);
        $counts = WorkflowStatus::countStatuses($query);
        $filteredCounts = $counts;
        if ($filters['status'] !== '') {
            $status = $filters['status'];
            WorkflowStatus::filterStatus($query, $status);
            $filteredCounts = [$status => $counts[$status]];
        }

        $items = $query->with(['requester', 'agent', 'assignee', 'assignedUsers', 'workflowInstance.workflow.stages', 'workflowInstance.currentStage'])
            ->latest('created_at')->orderByDesc('id')
            ->paginate($perPage)->withQueryString()
            ->through(function (Ticket $ticket) use ($viewer, $locale): array {
                $canViewHistory = RoleHelpers::userIsSuperAdmin($viewer) || $ticket->isRequester($viewer) || $ticket->isAgent($viewer);

                return $this->itemPayload($ticket, $canViewHistory ? $ticket->workflowInstance : null, $locale, $viewer);
            });

        return [$items, [
            'total' => array_sum($filteredCounts),
            'in_progress' => $filteredCounts[WorkflowStatus::IN_PROGRESS] ?? 0,
            'completed' => $filteredCounts[WorkflowStatus::DONE] ?? 0,
            'stages' => $this->stageCounts($filteredCounts),
        ]];
    }

    private function taskIndexData(Request $request, array $filters): array
    {
        $viewer = $request->user();
        $locale = (string) $request->route('locale');
        $query = WorkflowInstance::query()
            ->whereHasMorph('subject', [Task::class], function (Builder $tasks) use ($filters, $viewer): void {
                UnitVisibility::scopeWorkflowTasks($tasks, $viewer);
                if ($filters['search'] !== '') {
                    $search = '%'.$filters['search'].'%';
                    $tasks->where(fn (Builder $rows) => $rows->where('title', 'like', $search)
                        ->orWhere('task_no', 'like', $search)
                        ->orWhereHas('requester', fn (Builder $user) => $this->searchUser($user, $search))
                        ->orWhereHas('assignee', fn (Builder $user) => $this->searchUser($user, $search)));
                }
            });
        $summary = $this->itemSummary($query);
        if ($filters['status'] !== '') {
            $query->whereHas('currentStage', fn (Builder $stage) => $stage->where('status_key', $filters['status']));
        }

        $items = $this->withItemRelations($query)
            ->orderByDesc(Task::query()->select('created_at')->whereColumn('tasks.id', 'workflow_instances.subject_id'))
            ->orderByDesc('workflow_instances.id')
            ->paginate(min(50, max(5, $request->integer('per_page', 10))))->withQueryString();
        $assignees = User::query()->whereIn('id', $this->taskAssigneeIds($items->getCollection()))->get()->keyBy('id');
        $items->through(fn (WorkflowInstance $instance) => $this->itemPayload($instance->subject, $instance, $locale, $viewer, $assignees));

        return [$items, $summary];
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

        return redirect()->route('workflows.show', ['locale' => $request->route('locale'), 'workflow' => $workflow])->with('success', __('Workflow berhasil dibuat.'));
    }

    public function showInstance(Request $request, string $locale, string $instance): Response|RedirectResponse
    {
        $legacyIdentifier = ctype_digit($instance);
        $instance = $this->resolveInstance($instance);
        abort_unless($this->scopedItemsQuery($request->user())->whereKey($instance->id)->exists(), 403);
        if ($legacyIdentifier && ($publicIdentifier = $this->instancePublicIdentifier($instance)) !== (string) $instance->id) {
            return redirect()->route('workflows.instances.show', ['locale' => $locale, 'instance' => $publicIdentifier]);
        }
        $instance->load([
            'workflow.stages.responsibleUser',
            'workflow.creator',
            'workflow.updater',
            'currentStage',
            'histories.actor',
            'subject' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Ticket::class => ['requester', 'agent', 'assignee', 'assignedUsers'],
                    Task::class => ['requester', 'assignee'],
                ]);
            },
        ]);

        $taskAssignees = User::query()
            ->whereIn('id', $this->taskAssigneeIds(collect([$instance])))
            ->get()
            ->keyBy('id');
        $subject = $instance->subject;
        $item = $this->itemPayload($subject, $instance, $locale, $request->user(), $taskAssignees);
        $isTicket = $subject instanceof Ticket;
        $targetDate = $isTicket ? $subject->deadline() : ($subject->due_at ?? $subject->end_date);
        $finishedAt = $subject->completed_at;
        $finishedAt = $finishedAt ? \Illuminate\Support\Carbon::parse($finishedAt) : null;
        $sla = $isTicket ? $subject->sla : null;
        $historyByStatus = $instance->histories->groupBy('to_status');
        $currentStageId = $item['process_stages']->firstWhere('is_current', true)['id'] ?? null;
        $currentPosition = (int) ($instance->workflow->stages->firstWhere('id', $currentStageId)?->position ?? 0);

        $item['description'] = $subject->description;
        $item['sla'] = [
            'label' => match ($sla) { 'ontime' => __('On Time'), 'late' => __('Late'), default => '-' },
            'started_at' => $subject->created_at?->toIso8601String(),
            'target_at' => $targetDate?->toIso8601String(),
            'finished_at' => $finishedAt?->toIso8601String(),
            'state' => match ($item['status']) {
                WorkflowStatus::DONE => 'completed',
                WorkflowStatus::CANCELLED => 'cancelled',
                default => $targetDate?->isPast() ? 'overdue' : ($targetDate ? 'on_track' : 'not_set'),
            },
        ];
        $item['timeline'] = $instance->workflow->stages->map(function (WorkflowStage $stage) use ($instance, $item, $historyByStatus, $currentStageId, $currentPosition): array {
            $entries = $historyByStatus->get($stage->status_key, collect())->sortBy('created_at');
            $active = (int) $currentStageId === (int) $stage->id;
            $reached = $active || $entries->isNotEmpty() || $stage->position < $currentPosition;
            $leftAt = $instance->histories->first(fn ($history) => $history->from_status === $stage->status_key)?->created_at;

            return [
                'id' => $stage->id,
                'position' => $stage->position,
                'name' => \App\Support\WorkflowDisplay::text($instance->workflow, $stage->name),
                'status_key' => $stage->status_key,
                'status_label' => WorkflowStatus::label($stage->status_key),
                'responsible_role' => $stage->responsible_role,
                'responsible_user' => $stage->responsibleUser?->display_name,
                'is_required' => (bool) $stage->is_required,
                'notes' => $stage->instructions,
                'action_label' => \App\Support\WorkflowDisplay::text($instance->workflow, $stage->action_label),
                'state' => $active ? 'active' : ($reached ? 'completed' : 'pending'),
                'entered_at' => $active
                    ? ($item['stage_started_at'] ?? $entries->last()?->created_at?->toIso8601String())
                    : $entries->first()?->created_at?->toIso8601String(),
                'completed_at' => $leftAt?->toIso8601String(),
            ];
        })->values();
        $item['history'] = $instance->histories->map(fn ($history) => [
            'id' => $history->id,
            'event' => $history->event,
            'actor_name' => $history->actor?->display_name ?? __('Sistem'),
            'from_status' => $history->from_status,
            'to_status' => $history->to_status,
            'from_stage' => \App\Support\WorkflowDisplay::text($instance->workflow, $history->from_stage_name),
            'to_stage' => \App\Support\WorkflowDisplay::text($instance->workflow, $history->to_stage_name),
            'metadata' => $history->metadata,
            'reason' => $history->metadata['reason'] ?? null,
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
            ->orderByRaw('COALESCE(
                (SELECT created_at FROM tickets WHERE tickets.id = workflow_instances.subject_id AND workflow_instances.subject_type = ?),
                (SELECT created_at FROM tasks WHERE tasks.id = workflow_instances.subject_id AND workflow_instances.subject_type = ?)
            ) DESC', [Ticket::class, Task::class])
            ->orderByDesc('workflow_instances.id')
            ->paginate(10)
            ->withQueryString();

        $taskAssignees = User::query()
            ->whereIn('id', $this->taskAssigneeIds($instances->getCollection()))
            ->get()
            ->keyBy('id');
        $instances->through(
            fn (WorkflowInstance $instance) => $this->itemPayload($instance->subject, $instance, $locale, $request->user(), $taskAssignees)
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

    public function edit(Request $request, string $locale, Workflow $workflow): Response
    {
        $this->authorize('update', $workflow);
        $workflow->load(['stages.responsibleUser', 'creator']);

        return Inertia::render('Workflows/Form', [
            ...$this->formOptions(),
            'workflow' => $this->payload($workflow, true),
            'canSwapStatuses' => RoleHelpers::userIsSuperAdmin($request->user()),
        ]);
    }

    public function update(Request $request, string $locale, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow);
        $data = $this->validated($request, $workflow);
        $canSwapStatuses = RoleHelpers::userIsSuperAdmin($request->user());
        try {
            DB::transaction(function () use ($data, $request, $workflow, $canSwapStatuses) {
                $before = $workflow->only(['name', 'code', 'entity_type', 'description', 'trigger_conditions', 'is_active']);
                $beforeStages = $this->stageSnapshot($workflow);
                $workflow->update([...$this->attributes($data), 'version' => $workflow->version + 1, 'updated_by' => $request->user()->id]);
                $statusSwaps = $this->replaceStages($workflow, $data['stages'], $canSwapStatuses);
                $this->assertStagesPersisted($workflow, $data['stages']);
                $this->history($workflow, $request, 'updated', [
                    'before' => $before,
                    'after' => $workflow->fresh()->only(array_keys($before)),
                    'stages' => ['before' => $beforeStages, 'after' => $this->stageSnapshot($workflow)],
                    'status_swaps' => $statusSwaps,
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', __('Workflow gagal diperbarui. Tidak ada perubahan yang disimpan.'));
        }
        app(WorkflowRuntimeService::class)->syncWorkflow($workflow->fresh());

        return redirect()->route('workflows.show', ['locale' => $locale, 'workflow' => $workflow])->with('success', __('Workflow berhasil diperbarui.'));
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
            return back()->with('error', __('Workflow yang sudah digunakan tidak dapat dihapus. Nonaktifkan workflow sebagai gantinya.'));
        }
        $this->history($workflow, $request, 'deleted', ['workflow' => $workflow->only(['name', 'code', 'entity_type', 'version'])]);
        $workflow->delete();

        return redirect()->route('workflows.index', ['locale' => $locale])->with('success', __('Workflow berhasil dihapus.'));
    }

    public function updateInstanceStatus(Request $request, string $locale, string $instance): JsonResponse|RedirectResponse
    {
        $instance = $this->resolveInstance($instance);
        abort_unless($this->scopedItemsQuery($request->user())->whereKey($instance->id)->exists(), 403);
        $data = $request->validate([
            'status' => ['required_without:rejection_reason', 'nullable', Rule::in(WorkflowStatus::all())],
            'rejection_reason' => ['nullable', 'string', 'max:4000', 'prohibits:status'],
            'confirmation_note' => ['nullable', 'string', 'max:4000'],
            'evidence' => ['nullable', 'array', 'max:10'],
            'evidence.*' => ['uuid', 'distinct'],
        ]);
        $instance->load(['workflow.stages', 'subject']);

        $cancelledTicket = DB::transaction(function () use ($instance, $request, $data): ?Ticket {
            $subject = $instance->subject;
            abort_unless($subject instanceof Ticket || $subject instanceof Task, 422);
            $subject = $subject->newQuery()->lockForUpdate()->findOrFail($subject->getKey());
            $instance->refresh()->load(['workflow.stages', 'currentStage']);
            abort_unless($instance->workflow?->is_active, 422, __('Workflow sedang nonaktif.'));
            $from = $subject->status;
            $actor = $request->user();
            $rejecting = isset($data['rejection_reason']);
            $to = $rejecting ? $this->rejectionStatus($instance, $subject, $actor) : $data['status'];
            abort_if($to === null, 403);

            if (! WorkflowStatus::canTransition($from, $to)) {
                throw ValidationException::withMessages(['status' => __('Transisi status tidak valid.')]);
            }
            abort_unless($rejecting || $subject->canUserSetStatus($actor, $to), 403);
            $stage = $instance->workflow->stages->firstWhere('status_key', $to);
            if (! $stage) {
                throw ValidationException::withMessages(['status' => __('Tahap tujuan tidak tersedia pada workflow ini.')]);
            }
            if ($subject instanceof Ticket) {
                app(\App\Services\TicketLifecycle::class)->changeStatus($subject, $actor, $to, $data, $rejecting);
            } else {
                $subject->status = $to;
            }
            $subject->save();

            if ($rejecting) {
                $instance->histories()->create([
                    'workflow_id' => $instance->workflow_id,
                    'user_id' => $actor->id,
                    'event' => 'rejected',
                    'from_status' => $from,
                    'to_status' => $to,
                    'from_stage_name' => $instance->currentStage?->name,
                    'to_stage_name' => $stage->name,
                    'metadata' => ['reason' => $data['rejection_reason']],
                ]);
            }

            return $subject instanceof Ticket && $from !== $to && $to === WorkflowStatus::CANCELLED ? $subject : null;
        });
        if ($cancelledTicket) {
            app(\App\Services\WorkItemNotifier::class)->notifyTicketCancelled($cancelledTicket, $request->user());
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Status workflow berhasil diperbarui.')]);
        }

        return back()->with('success', __('Status workflow berhasil diperbarui.'));
    }

    private function rejectionStatus(WorkflowInstance $instance, Ticket|Task $subject, User $actor): ?string
    {
        if (! $instance->workflow?->is_active) {
            return null;
        }

        $from = $subject->status;
        $to = $from === WorkflowStatus::CONFIRMATION ? WorkflowStatus::REVISION : WorkflowStatus::CANCELLED;
        $assigned = $subject instanceof Ticket ? $subject->isAgent($actor) : $subject->isAssignee($actor);
        $canReject = $subject->canUserSetStatus($actor, $to) || ($from === WorkflowStatus::NEW && $assigned);

        return $from !== $to && $canReject && WorkflowStatus::canTransition($from, $to)
            && $instance->workflow->stages->contains('status_key', $to) ? $to : null;
    }

    public function storeStage(Request $request, string $locale, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow);
        $data = $this->validatedStage($request, $workflow);
        DB::transaction(function () use ($workflow, $data, $request) {
            $stages = $workflow->stages()->get()->values();
            $stage = $workflow->stages()->create([...$data, 'position' => $this->temporaryStagePosition($workflow)]);
            $stages->splice($data['position'] - 1, 0, [$stage]);
            $this->normalizeStagePositions($workflow, $stages->pluck('id')->all());
            $this->touchStructure($workflow, $request, 'stage_created', ['stage' => $stage->fresh()->only(['name', 'status_key', 'position'])]);
        });

        return back()->with('success', __('Tahap workflow berhasil ditambahkan.'));
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
            $ordered = $workflow->stages()->whereKeyNot($stage->id)->lockForUpdate()->get()->values();
            $ordered->splice($position - 1, 0, [$stage]);
            $this->normalizeStagePositions($workflow, $ordered->pluck('id')->all());
            $this->touchStructure($workflow, $request, 'stage_updated', ['before' => $before, 'after' => $stage->fresh()->only(array_keys($before))]);
        });

        return back()->with('success', __('Tahap workflow berhasil diperbarui.'));
    }

    public function destroyStage(Request $request, string $locale, Workflow $workflow, WorkflowStage $stage): RedirectResponse
    {
        $this->authorize('update', $workflow);
        abort_unless((int) $stage->workflow_id === (int) $workflow->id, 404);
        if ($workflow->stages()->count() <= 2) {
            return back()->with('error', 'Workflow minimal memiliki dua tahap.');
        }
        if ($stage->instances()->exists()) {
            return back()->with('error', __('Tahap yang sedang digunakan tidak dapat dihapus.'));
        }
        DB::transaction(function () use ($workflow, $stage, $request) {
            $snapshot = $stage->only(['name', 'status_key', 'position']);
            $stage->delete();
            $this->normalizeStagePositions($workflow, $workflow->stages()->pluck('id')->all());
            $this->touchStructure($workflow, $request, 'stage_deleted', ['stage' => $snapshot]);
        });

        return back()->with('success', __('Tahap workflow berhasil dihapus.'));
    }

    public function reorderStages(Request $request, string $locale, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow);
        $ids = $request->validate(['stage_ids' => ['required', 'array', 'min:2'], 'stage_ids.*' => ['required', 'integer', 'distinct']])['stage_ids'];
        if ($workflow->stages()->pluck('id')->sort()->values()->all() !== collect($ids)->map('intval')->sort()->values()->all()) {
            throw ValidationException::withMessages(['stage_ids' => __('Daftar tahap tidak valid.')]);
        }
        DB::transaction(function () use ($workflow, $ids, $request) {
            $this->normalizeStagePositions($workflow, $ids);
            $this->touchStructure($workflow, $request, 'stages_reordered', ['stage_ids' => $ids]);
        });

        return back()->with('success', __('Urutan tahap berhasil diperbarui.'));
    }

    private function touchStructure(Workflow $workflow, Request $request, string $event, array $changes): void
    {
        $workflow->increment('version');
        $workflow->update(['updated_by' => $request->user()->id]);
        $this->history($workflow, $request, $event, $changes);
    }

    private function validatedStage(Request $request, Workflow $workflow, ?WorkflowStage $stage = null): array
    {
        $data = $request->validate([
            'position' => ['required', 'integer', 'min:1', 'max:'.($workflow->stages()->count() + ($stage ? 0 : 1))], 'name' => ['required', 'string', 'max:100'],
            'status_key' => ['required', Rule::in(WorkflowStatus::all())],
            'responsible_role' => ['nullable', 'string', 'max:80', 'exists:roles,name'], 'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_required' => ['required', 'boolean'], 'action_label' => ['nullable', 'string', 'max:100'], 'instructions' => ['nullable', 'string', 'max:1000'],
        ]);
        $conflict = $workflow->stages()->where('status_key', $data['status_key'])->when($stage, fn ($query) => $query->whereKeyNot($stage->id))->first();
        if ($conflict) {
            throw ValidationException::withMessages([
                'status_key' => __(':status is already used by stage :position (:name). Use Edit Workflow to swap statuses.', ['status' => WorkflowStatus::label($data['status_key']), 'position' => $conflict->position, 'name' => $conflict->name]),
            ]);
        }

        return $data;
    }

    private function validated(Request $request, ?Workflow $workflow = null): array
    {
        $entityTypes = $workflow && $workflow->instances()->exists()
            ? [$workflow->entity_type]
            : ['ticket', 'task'];

        $data = $request->validate([
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
            'stages.*.status_key' => ['required', Rule::in(WorkflowStatus::all())],
            'stages.*.responsible_role' => ['nullable', 'string', 'max:80', 'exists:roles,name'],
            'stages.*.responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'stages.*.is_required' => ['sometimes', 'boolean'],
            'stages.*.action_label' => ['nullable', 'string', 'max:100'],
            'stages.*.instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $conflicts = collect($data['stages'])->groupBy('status_key')->filter(fn ($items) => $items->count() > 1);
        if ($conflicts->isNotEmpty()) {
            $status = (string) $conflicts->keys()->first();
            $positions = $conflicts->first()->keys()->map(fn ($index) => __('Stage :number', ['number' => (int) $index + 1]))->implode(__(' dan '));
            throw ValidationException::withMessages([
                'stages' => __(':status is already used by :stages. Select that status and confirm the swap.', ['status' => WorkflowStatus::label($status), 'stages' => $positions]),
            ]);
        }

        return $data;
    }

    private function attributes(array $data): array
    {
        return ['name' => $data['name'], 'code' => strtoupper($data['code']), 'entity_type' => $data['entity_type'], 'description' => $data['description'] ?? null, 'trigger_conditions' => $data['trigger_conditions'] ?? [], 'is_active' => $data['is_active']];
    }

    private function replaceStages(Workflow $workflow, array $stages, bool $canSwapStatuses = false): array
    {
        $existing = $workflow->stages()->lockForUpdate()->get();
        $existingById = $existing->keyBy(fn (WorkflowStage $stage) => (string) $stage->id);
        $statusOwners = $existing->keyBy('status_key');
        $statusSwaps = [];

        foreach (array_values($stages) as $index => $data) {
            $current = ! empty($data['id']) ? $existingById->get((string) $data['id']) : null;
            $owner = $statusOwners->get($data['status_key']);
            if (! $owner || ($current && $owner->is($current))) {
                continue;
            }
            if (! $canSwapStatuses) {
                throw ValidationException::withMessages([
                    'stages' => __(':status is already used by stage :position (:name). Only a Super Admin can swap statuses.', ['status' => WorkflowStatus::label($data['status_key']), 'position' => $owner->position, 'name' => $owner->name]),
                ]);
            }
            $statusSwaps[] = [
                'source_stage_id' => $current?->id,
                'target_stage_id' => $owner->id,
                'source_position' => $index + 1,
                'from_status' => $current?->status_key,
                'to_status' => $data['status_key'],
            ];
        }

        $kept = [];
        $statusChanges = [];

        foreach (array_values($stages) as $index => $data) {
            $stage = isset($data['id'])
                ? $existing->firstWhere('id', (int) $data['id'])
                : $existing->first(fn (WorkflowStage $item) => $item->status_key === $data['status_key'] && ! in_array($item->id, $kept, true));
            $attributes = [
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
                $stage = $workflow->stages()->create([...$attributes, 'position' => $this->temporaryStagePosition($workflow)]);
            }
            $kept[] = $stage->id;
        }

        foreach ($existing->whereNotIn('id', $kept) as $removed) {
            if ($removed->instances()->exists()) {
                throw ValidationException::withMessages(['stages' => __('Tahap yang sedang digunakan tidak dapat dihapus.')]);
            }
            $removed->delete();
        }

        $this->normalizeStagePositions($workflow, $kept);

        foreach ($statusChanges as [$stage, $from, $to]) {
            $this->migrateStageRuntimeStatus($stage, $from, $to);
        }

        return $statusSwaps;
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

    private function temporaryStagePosition(Workflow $workflow): int
    {
        return ((int) $workflow->stages()->max('position')) + $workflow->stages()->count() + 100;
    }

    private function normalizeStagePositions(Workflow $workflow, array $orderedIds): void
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Normalisasi urutan tahap harus dijalankan di dalam transaksi.');
        }

        $orderedIds = collect($orderedIds)->map(fn ($id) => (int) $id)->values();
        $locked = $workflow->stages()->lockForUpdate()->get(['workflow_stages.id', 'workflow_stages.position']);
        if ($locked->pluck('id')->sort()->values()->all() !== $orderedIds->sort()->values()->all()) {
            throw ValidationException::withMessages(['stages' => __('Urutan tahap tidak lengkap. Muat ulang halaman lalu coba lagi.')]);
        }

        $offset = ((int) $locked->max('position')) + $locked->count() + 100;
        $workflow->stages()->update(['position' => DB::raw("position + {$offset}")]);
        foreach ($orderedIds as $index => $id) {
            DB::table('workflow_stages')
                ->where('workflow_id', $workflow->id)
                ->where('id', $id)
                ->update(['position' => $index + 1]);
        }

        $persisted = $workflow->stages()->pluck('position', 'id');
        $expectedPositions = range(1, $orderedIds->count());
        if ($persisted->values()->all() !== $expectedPositions || $persisted->keys()->map(fn ($id) => (int) $id)->all() !== $orderedIds->all()) {
            throw ValidationException::withMessages(['stages' => __('Urutan tahap gagal dinormalisasi. Tidak ada perubahan yang disimpan.')]);
        }
    }

    private function assertStagesPersisted(Workflow $workflow, array $stages): void
    {
        $persisted = $workflow->stages()->get()->keyBy('id');
        if ($workflow->stages()->pluck('position')->all() !== range(1, count($stages))) {
            throw ValidationException::withMessages([
                'stages' => __('Urutan tahap gagal tersimpan. Tidak ada perubahan yang disimpan.'),
            ]);
        }
        foreach ($stages as $stage) {
            if (empty($stage['id'])) {
                continue;
            }
            $saved = $persisted->get((int) $stage['id']);
            if (! $saved || $saved->status_key !== WorkflowStatus::normalize($stage['status_key'])) {
                throw ValidationException::withMessages([
                    'stages' => __('Perubahan status tahap gagal tersimpan. Silakan muat ulang dan coba lagi.'),
                ]);
            }
        }
    }

    private function history(Workflow $workflow, Request $request, string $event, array $changes): void
    {
        $workflow->histories()->create(['user_id' => $request->user()->id, 'event' => $event, 'changes' => $changes]);
    }


    private function stageSnapshot(Workflow $workflow): array
    {
        return $workflow->stages()->get()->map(fn (WorkflowStage $stage) => [
            'id' => $stage->id,
            'position' => $stage->position,
            'name' => $stage->name,
            'status_key' => $stage->status_key,
        ])->values()->all();
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
        $display = fn ($user) => $user?->display_name ?? __('Sistem');
        $payload = [
            'uuid' => $workflow->uuid, 'slug' => $workflow->slug, 'name' => $workflow->name, 'code' => $workflow->code, 'entity_type' => $workflow->entity_type,
            'description' => $workflow->description, 'description_label' => \App\Support\WorkflowDisplay::text($workflow, $workflow->description), 'trigger_conditions' => $workflow->trigger_conditions ?? [], 'is_active' => $workflow->is_active,
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
                'id' => $stage->id, 'position' => $stage->position, 'name' => $stage->name, 'name_label' => \App\Support\WorkflowDisplay::text($workflow, $stage->name), 'status_key' => $stage->status_key,
                'responsible_role' => $stage->responsible_role, 'responsible_user_id' => $stage->responsible_user_id,
                'responsible_user_name' => $stage->responsible_user_id ? $display($stage->responsibleUser) : null,
                'is_required' => (bool) $stage->is_required,
                'action_label' => $stage->action_label, 'action_label_display' => \App\Support\WorkflowDisplay::text($workflow, $stage->action_label), 'instructions' => $stage->instructions,
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
        })->oldest('id')->firstOrFail();
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
            'stages' => $this->stageCounts($stageRows->all()),
        ];
    }

    private function stageCounts(array $counts): Collection
    {
        return collect(WorkflowStatus::all())->map(fn (string $status) => [
            'status_key' => $status,
            'label' => WorkflowStatus::label($status),
            'count' => (int) ($counts[$status] ?? 0),
        ]);
    }

    private function withItemRelations(Builder $query): Builder
    {
        return $query->with([
            'workflow.stages',
            'currentStage',
            'subject' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Ticket::class => ['requester', 'agent', 'assignee', 'assignedUsers'],
                    Task::class => ['requester', 'assignee'],
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

    private function itemPayload(Ticket|Task $subject, ?WorkflowInstance $instance, string $locale, User $viewer, ?Collection $taskAssignees = null): array
    {
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
            $targetDate = $subject->deadline();
        } else {
            $ids = $this->taskAssigneeIds(collect([$instance]));
            $pic = collect($ids)->map(fn ($id) => $taskAssignees->get($id)?->display_name)->filter();
            $number = $subject->task_no ?: 'Task #'.$subject->id;
            $detailUrl = route('tasks.view', ['locale' => $locale, 'task' => $subject]);
            $editUrl = route('tasks.edit', ['locale' => $locale, 'task' => $subject->public_slug]);
            $targetDate = $subject->due_at ?? $subject->end_date;
        }

        $status = WorkflowStatus::normalize($subject->status);
        $statusLabel = WorkflowStatus::label($status);
        $workflow = $instance?->workflow;
        $stages = $workflow?->stages ?? collect();
        $currentStage = $isTicket
            ? $stages->first(fn (WorkflowStage $stage) => WorkflowStatus::normalize($stage->status_key) === $status)
            : $instance?->currentStage;
        $stageStartedAt = $currentStage?->is($instance?->currentStage) ? ($instance->stage_started_at ?? $instance->started_at) : null;
        $identifier = $isTicket ? $subject->ticket_no : $subject->task_no;
        $instanceParams = ['locale' => $locale, 'instance' => $identifier ?: $instance?->id];
        $statusOptions = collect(WorkflowStatus::allowedTransitions($status))
            ->filter(fn (string $candidate) => $workflow?->is_active
                && $subject->canUserSetStatus($viewer, $candidate)
                && $stages->contains('status_key', $candidate))
            ->map(fn (string $candidate) => ['value' => $candidate, 'label' => WorkflowStatus::label($candidate)])
            ->values();

        return [
            'id' => $instance?->id ?? 'ticket-'.$subject->id,
            'workflow_uuid' => $workflow?->uuid,
            'workflow_slug' => $workflow?->slug,
            'workflow_name' => $workflow?->name,
            'workflow_active' => (bool) $workflow?->is_active,
            'number' => $number,
            'title' => $subject->title,
            'type' => $isTicket ? 'ticket' : 'task',
            'requester' => $requester,
            'creator' => $requester,
            'pic' => $pic->unique()->values()->implode(', ') ?: '—',
            'priority' => $subject->priority ?: '-',
            'target_date' => $targetDate?->toIso8601String(),
            'status' => $status,
            'status_label' => $statusLabel,
            'stage_name' => $isTicket ? $statusLabel : (WorkflowDisplay::text($workflow, $currentStage?->name) ?? $statusLabel),
            'process_stages' => $stages->map(fn (WorkflowStage $stage) => [
                'id' => $stage->id,
                'name' => $isTicket ? WorkflowStatus::label($stage->status_key) : WorkflowDisplay::text($workflow, $stage->name),
                'is_current' => $stage->is($currentStage),
            ])->values(),
            'created_at' => $subject->created_at?->toIso8601String(),
            'updated_at' => $subject->updated_at?->toIso8601String(),
            'date' => $subject->updated_at?->toIso8601String(),
            'stage_started_at' => $stageStartedAt?->toIso8601String(),
            'detail_url' => $instance ? route('workflows.instances.show', $instanceParams) : null,
            'related_url' => $detailUrl,
            'edit_url' => $editUrl,
            'status_options' => $statusOptions,
            'status_update_url' => $instance ? route('workflows.instances.status', $instanceParams) : null,
            'can_view_workflow' => $workflow && $viewer->can('view', $workflow),
            'can_edit' => $viewer->can($isTicket ? 'update tickets' : 'update tasks'),
            'can_update_status' => $statusOptions->isNotEmpty(),
            'can_reject' => $instance && $this->rejectionStatus($instance, $subject, $viewer) !== null,
            'can_update_workflow' => $workflow && $viewer->can('update', $workflow),
            'can_toggle_workflow' => $workflow && $viewer->can('toggle', $workflow),
            'can_delete_workflow' => false,
            'workflow_toggle_url' => $workflow ? route('workflows.toggle', ['locale' => $locale, 'workflow' => $workflow]) : null,
            'workflow_delete_url' => $workflow ? route('workflows.destroy', ['locale' => $locale, 'workflow' => $workflow]) : null,
            'workflow_url' => $workflow ? route('workflows.show', ['locale' => $locale, 'workflow' => $workflow]) : null,
            'workflow_edit_url' => $workflow ? route('workflows.edit', ['locale' => $locale, 'workflow' => $workflow]) : null,
        ];
    }
}
