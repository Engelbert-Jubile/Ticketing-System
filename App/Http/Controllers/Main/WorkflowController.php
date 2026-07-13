<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Services\WorkflowRuntimeService;
use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        $filters['status'] = in_array($filters['status'], ['active', 'inactive'], true) ? $filters['status'] : '';
        $canUpdate = $request->user()->can('update workflows');
        $workflows = Workflow::query()
            ->with([
                'creator',
                'stages' => fn ($query) => $query->withCount('instances'),
            ])
            ->withCount([
                'stages',
                'instances',
                'instances as running_instances_count' => fn ($query) => $query->where('status', 'running'),
                'instances as completed_instances_count' => fn ($query) => $query->where('status', 'completed'),
            ])
            ->when(! $canUpdate, fn ($q) => $q->where('is_active', true))
            ->when($filters['search'] !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', '%'.$filters['search'].'%')
                ->orWhere('code', 'like', '%'.$filters['search'].'%')))
            ->when($filters['type'] !== '', fn ($q) => $q->where('entity_type', $filters['type']))
            ->when($filters['status'] !== '', fn ($q) => $q->where('is_active', $filters['status'] === 'active'))
            ->latest('updated_at')->paginate(min(50, max(5, $request->integer('per_page', 10))))
            ->withQueryString()->through(fn (Workflow $workflow) => $this->payload($workflow));

        return Inertia::render('Workflows/Index', [
            'workflows' => $workflows,
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

    public function show(Request $request, string $locale, Workflow $workflow): Response
    {
        $this->authorize('view', $workflow);
        $workflow->load([
            'stages' => fn ($query) => $query->with('responsibleUser')->withCount('instances'),
            'creator',
            'updater',
            'histories.actor',
        ])->loadCount([
            'instances',
            'instances as running_instances_count' => fn ($query) => $query->where('status', 'running'),
            'instances as completed_instances_count' => fn ($query) => $query->where('status', 'completed'),
        ]);

        $subjectClass = $workflow->entity_type === 'ticket' ? Ticket::class : Task::class;
        $instances = $workflow->instances()
            ->where('subject_type', $subjectClass)
            ->whereHasMorph('subject', [$subjectClass])
            ->with([
                'currentStage',
                'subject' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        Ticket::class => ['requester', 'agent', 'assignee', 'assignedUsers'],
                        Task::class => ['requester', 'assignee'],
                    ]);
                },
            ])
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $taskAssignees = User::query()
            ->whereIn('id', $this->taskAssigneeIds($instances->getCollection()))
            ->get()
            ->keyBy('id');
        $instances->through(
            fn (WorkflowInstance $instance) => $this->instancePayload($instance, $locale, $taskAssignees)
        );

        return Inertia::render('Workflows/Show', [
            'workflow' => $this->payload($workflow, true),
            'instances' => $instances,
            'can' => [
                'update' => $request->user()->can('update', $workflow),
                'toggle' => $request->user()->can('toggle', $workflow),
                'delete' => $request->user()->can('delete', $workflow),
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
        DB::transaction(function () use ($data, $request, $workflow) {
            $before = $workflow->only(['name', 'code', 'entity_type', 'description', 'trigger_conditions', 'is_active']);
            $workflow->update([...$this->attributes($data), 'version' => $workflow->version + 1, 'updated_by' => $request->user()->id]);
            $this->replaceStages($workflow, $data['stages']);
            $this->history($workflow, $request, 'updated', ['before' => $before, 'after' => $workflow->fresh()->only(array_keys($before))]);
        });
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
        $workflow->delete();

        return redirect()->route('workflows.index', ['locale' => $locale])->with('success', 'Workflow berhasil dihapus.');
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
            'stages.*.name' => ['required', 'string', 'max:100'],
            'stages.*.status_key' => ['required', 'distinct', Rule::in(WorkflowStatus::all())],
            'stages.*.responsible_role' => ['nullable', 'string', 'max:80', 'exists:roles,name'],
            'stages.*.responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
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
        $workflow->stages()->delete();
        foreach (array_values($stages) as $index => $stage) {
            $workflow->stages()->create([...$stage, 'position' => $index + 1]);
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
            'uuid' => $workflow->uuid, 'name' => $workflow->name, 'code' => $workflow->code, 'entity_type' => $workflow->entity_type,
            'description' => $workflow->description, 'trigger_conditions' => $workflow->trigger_conditions ?? [], 'is_active' => $workflow->is_active,
            'version' => $workflow->version, 'stages_count' => $workflow->stages_count ?? ($workflow->relationLoaded('stages') ? $workflow->stages->count() : 0),
            'instances_count' => $workflow->instances_count ?? 0, 'creator_name' => $display($workflow->creator), 'updated_at' => $workflow->updated_at?->toIso8601String(),
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
                'action_label' => $stage->action_label, 'instructions' => $stage->instructions,
                'instances_count' => (int) ($stage->instances_count ?? 0),
            ])->values();
            $payload['histories'] = $workflow->relationLoaded('histories') ? $workflow->histories->map(fn ($history) => [
                'id' => $history->id, 'event' => $history->event, 'actor_name' => $display($history->actor), 'changes' => $history->changes, 'created_at' => $history->created_at?->toIso8601String(),
            ])->values() : [];
        }

        return $payload;
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

    private function instancePayload(WorkflowInstance $instance, string $locale, $taskAssignees): array
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
        } else {
            $ids = $this->taskAssigneeIds(collect([$instance]));
            $pic = collect($ids)->map(fn ($id) => $taskAssignees->get($id)?->display_name)->filter();
            $number = $subject->task_no ?: 'Task #'.$subject->id;
            $detailUrl = route('tasks.view', ['locale' => $locale, 'task' => $subject]);
        }

        $status = WorkflowStatus::normalize($subject->status);

        return [
            'id' => $instance->id,
            'number' => $number,
            'title' => $subject->title,
            'requester' => $requester,
            'pic' => $pic->unique()->values()->implode(', ') ?: '—',
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'stage_name' => $instance->currentStage?->name ?? WorkflowStatus::label($status),
            'date' => $subject->created_at?->toIso8601String(),
            'detail_url' => $detailUrl,
        ];
    }
}
