<?php

namespace App\Http\Controllers\Main;

use App\Domains\Project\Models\Project;
use App\Domains\Task\DTO\TaskData;
use App\Domains\Task\Enums\TaskStatus;
use App\Domains\Task\UseCases\CreateTask;
use App\Domains\Task\UseCases\DeleteTask;
use App\Domains\Task\UseCases\GetAllTasks;
use App\Domains\Task\UseCases\UpdateTask;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\ReportExportService;
use App\Services\WorkItemNotifier;
use App\Support\RoleHelpers;
use App\Support\UnitVisibility;
use App\Support\UserUnitOptions;
use App\Support\WorkflowStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class TaskController extends Controller
{
    public function __construct(
        private readonly GetAllTasks $getAllTasks,
        private readonly CreateTask $createTask,
        private readonly UpdateTask $updateTask,
        private readonly DeleteTask $deleteTask,
        private readonly AttachmentService $attachments,
        private readonly ReportExportService $reportExport
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        return redirect()->route('tasks.report');
    }

    public function onProgress(Request $request): Response
    {
        $statusScope = array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS),
            WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION)
        ));

        $origin = $this->pageOrigin($request, 'tasks.on-progress');
        $actor = $request->user();

        $taskQuery = Task::query()
            ->select(['id', 'title', 'description', 'status', 'updated_at'])
            ->whereIn('status', $statusScope);

        $taskQuery = UnitVisibility::scopeTasks($taskQuery, $actor);

        $tasks = $taskQuery
            ->latest('updated_at')
            ->limit(200)
            ->get()
            ->map(fn (Task $task) => $this->transformTaskInProgress($task, $origin))
            ->values();

        return Inertia::render('Tasks/OnProgress', [
            'tasks' => $tasks,
        ]);
    }

    public function report(Request $request): Response
    {
        $filters = $this->resolveTaskReportFilters($request);
        $hasDueDateColumn = Schema::hasColumn('tasks', 'due_date');
        $actor = $request->user();

        $ticketSummary = $this->taskReportSummary($filters, true, $hasDueDateColumn, $actor);
        $standaloneSummary = $this->taskReportSummary($filters, false, $hasDueDateColumn, $actor);

        $ticketTasks = $this->taskReportPaginator($filters, true, $hasDueDateColumn, $request, $actor);
        $standaloneTasks = $this->taskReportPaginator($filters, false, $hasDueDateColumn, $request, $actor);

        $statusOptions = collect(TaskStatus::values())
            ->map(fn (string $value) => [
                'value' => WorkflowStatus::normalize($value),
                'label' => WorkflowStatus::label($value),
            ])
            ->unique('value')
            ->values()
            ->all();

        return Inertia::render('Tasks/Report', [
            'filters' => [
                'q' => $filters['q'],
                'status' => $filters['status'],
                'from' => $filters['from'],
                'to' => $filters['to'],
            ],
            'statusOptions' => $statusOptions,
            'ticketSummary' => $ticketSummary,
            'standaloneSummary' => $standaloneSummary,
            'ticketTasks' => $ticketTasks,
            'standaloneTasks' => $standaloneTasks,
        ]);
    }

    public function downloadReport(Request $request)
    {
        $filters = $this->resolveTaskReportFilters($request);
        $hasDueDateColumn = Schema::hasColumn('tasks', 'due_date');
        $tasks = $this->buildTaskReportQuery($filters, $hasDueDateColumn, $request->user(), true)
            ->orderByDesc('created_at')
            ->get();

        $tz = config('app.timezone');
        $rows = $tasks->map(function (Task $task) use ($tz) {
            $statusPayload = $this->ticketAwareStatusPayload($task, $task->ticket_id !== null);
            $displayStatus = $statusPayload['display'];

            return [
                $task->task_no ?? '—',
                $task->title ?? '—',
                $displayStatus['label'],
                ucfirst($task->priority ?? '—'),
                $this->formatDate($task->due_at ?? ($task->due_date ?? null), 'd M Y H:i', $tz),
                $this->formatDate($task->updated_at, 'd M Y H:i', $tz),
            ];
        })->toArray();

        $columns = [
            ['label' => 'Task No'],
            ['label' => 'Judul'],
            ['label' => 'Status'],
            ['label' => 'Prioritas'],
            ['label' => 'Batas Waktu'],
            ['label' => 'Diperbarui'],
        ];

        $rangeLabel = $this->formatRange($filters['from_date'], $filters['to_date']);
        $meta = [
            'filters' => [
                'Pencarian' => $filters['q'] !== '' ? $filters['q'] : 'Semua',
                'Status' => $filters['normalized_status'] ? WorkflowStatus::label($filters['normalized_status']) : 'Semua',
                'Rentang' => $rangeLabel,
                'Total Data' => number_format($tasks->count()),
            ],
        ];

        $filename = sprintf('tasks-report-%s.pdf', now()->format('Ymd-His'));

        return $this->reportExport->downloadPdf('Laporan Task', $columns, $rows, $meta, $filename);
    }

    public function reportTicketDetail(Request $request, string $locale, Ticket|string $ticket): Response
    {
        // Locale is captured by the route prefix; we only need the ticket value.
        $ticket = $ticket instanceof Ticket
            ? $ticket
            : Ticket::where('ticket_no', $ticket)->firstOrFail();

        UnitVisibility::ensureTicketAccess($request->user(), $ticket);

        $userColumns = $this->userProfileSelectColumns();
        $userSelect = implode(',', $userColumns);

        $ticket->load([
            'requester:'.$userSelect,
            'agent:'.$userSelect,
            'assignedUsers:'.$userSelect,
            'attachments:id,attachable_id,attachable_type,original_name,size',
        ]);

        $hasDueDateColumn = Schema::hasColumn('tasks', 'due_date');
        $viewer = $request->user();
        $origin = route('tasks.report');

        $taskRows = $this->buildTaskReportQuery($this->emptyTaskReportFilters(), $hasDueDateColumn, $viewer, true)
            ->where('ticket_id', $ticket->id)
            ->orderByDesc('created_at')
            ->get();

        $tasks = $taskRows
            ->map(fn (Task $task) => $this->transformTaskReportRow($task, true, $origin, false))
            ->values();

        $summary = $this->summarizeTaskRows($tasks);

        return Inertia::render('Tasks/ReportTicketDetail', [
            'ticket' => $this->buildTicketDetailPayload($ticket, $summary),
            'tasks' => $tasks,
            'summary' => $summary,
            'meta' => [
                'backUrl' => $this->resolveBackUrl($request, route('tasks.report')),
            ],
        ]);
    }

    private function taskReportSummary(array $filters, bool $withTicket, bool $hasDueDateColumn, ?User $viewer = null): array
    {
        $baseQuery = $this->baseTaskReportQuery($filters, $hasDueDateColumn, $viewer);

        if ($withTicket) {
            $total = $this->countDistinctTicketIds((clone $baseQuery)->whereNotNull('ticket_id'));
        } else {
            $total = (clone $baseQuery)->whereNull('ticket_id')->count();
        }

        $inProgress = $this->countTaskSummaryByStatus(
            clone $baseQuery,
            WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS),
            $withTicket
        );

        $done = $this->countTaskSummaryByStatus(
            clone $baseQuery,
            WorkflowStatus::equivalents(WorkflowStatus::DONE),
            $withTicket
        );

        return [
            'total' => $total,
            'in_progress' => $inProgress,
            'done' => $done,
        ];
    }

    private function countTaskSummaryByStatus(Builder $query, array $statuses, bool $withTicket): int
    {
        if (empty($statuses)) {
            return 0;
        }

        if ($withTicket) {
            return (clone $query)
                ->whereNotNull('ticket_id')
                ->whereHas('ticket', function (Builder $ticket) use ($statuses) {
                    $ticket->whereIn('status', $statuses);
                })
                ->distinct('ticket_id')
                ->count('ticket_id');
        }

        return (clone $query)
            ->whereNull('ticket_id')
            ->whereIn('status', $statuses)
            ->count();
    }

    private function countDistinctTicketIds(Builder $query): int
    {
        return (int) (clone $query)
            ->distinct('ticket_id')
            ->count('ticket_id');
    }

    private function taskReportPaginator(array $filters, bool $withTicket, bool $hasDueDateColumn, Request $request, ?User $viewer = null)
    {
        $perPageParam = $withTicket ? 'ticket_per_page' : 'standalone_per_page';
        $pageName = $withTicket ? 'ticket_page' : 'standalone_page';

        $perPage = (int) $request->integer($perPageParam, 15);
        $perPage = min(max($perPage, 5), 50);

        $origin = $this->reportOrigin($request);

        if ($withTicket) {
            $baseQuery = $this->baseTaskReportQuery($filters, $hasDueDateColumn, $viewer);
            $detailedQuery = $this->buildTaskReportQuery($filters, $hasDueDateColumn, $viewer, true);

            return $this->paginateTicketTaskGroups(
                $baseQuery,
                $detailedQuery,
                $perPage,
                $pageName,
                $filters,
                $origin
            );
        }

        $query = $this->buildTaskReportQuery($filters, $hasDueDateColumn, $viewer, true)
            ->whereNull('ticket_id')
            ->orderByDesc('created_at');

        return $query
            ->paginate($perPage, ['*'], $pageName)
            ->appends($this->taskReportQueryParams($filters))
            ->through(fn (Task $task) => $this->transformTaskReportRow($task, false, $origin));
    }

    private function paginateTicketTaskGroups(
        Builder $baseQuery,
        Builder $detailedQuery,
        int $perPage,
        string $pageName,
        array $filters,
        string $origin
    ) {
        $groupQuery = (clone $baseQuery)
            ->whereNotNull('ticket_id')
            ->selectRaw('ticket_id, MAX(created_at) as latest_created_at')
            ->groupBy('ticket_id')
            ->orderByDesc('latest_created_at');

        $paginator = $groupQuery
            ->paginate($perPage, ['ticket_id', 'latest_created_at'], $pageName)
            ->appends($this->taskReportQueryParams($filters));

        $ticketIds = collect($paginator->items())
            ->pluck('ticket_id')
            ->filter()
            ->all();

        if (empty($ticketIds)) {
            $paginator->setCollection(collect());

            return $paginator;
        }

        $tasks = (clone $detailedQuery)
            ->whereNotNull('ticket_id')
            ->whereIn('ticket_id', $ticketIds)
            ->orderBy('ticket_id')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('ticket_id')
            ->map(fn (Collection $taskRows) => $this->formatTicketTaskGroup($taskRows, $origin));

        $ordered = collect($ticketIds)
            ->map(fn ($ticketId) => $tasks->get($ticketId))
            ->filter()
            ->values();

        return tap($paginator, fn ($p) => $p->setCollection($ordered));
    }

    private function summarizeTaskRows(Collection $rows): array
    {
        $total = $rows->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'in_progress' => 0,
                'done' => 0,
            ];
        }

        $inProgressStatuses = array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS),
            WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION),
            WorkflowStatus::equivalents(WorkflowStatus::REVISION)
        ));

        $doneStatuses = WorkflowStatus::equivalents(WorkflowStatus::DONE);

        $statusResolver = static function (array $row): string {
            $status = $row['status_task'] ?? $row['status'] ?? WorkflowStatus::NEW;

            return strtolower((string) $status);
        };

        $inProgress = $rows->filter(fn (array $row) => in_array($statusResolver($row), $inProgressStatuses, true))->count();
        $done = $rows->filter(fn (array $row) => in_array($statusResolver($row), $doneStatuses, true))->count();

        return [
            'total' => $total,
            'in_progress' => $inProgress,
            'done' => $done,
        ];
    }

    private function formatTicketTaskGroup(Collection $taskRows, string $origin, ?Ticket $fallbackTicket = null): array
    {
        $rows = $taskRows
            ->map(fn (Task $task) => $this->transformTaskReportRow($task, true, $origin))
            ->values();

        $ticketInfo = $rows->first()['ticket'] ?? ($fallbackTicket ? $this->buildTicketReportPayload($fallbackTicket) : null);
        $detailLink = $this->ticketDetailLink($ticketInfo['id'] ?? null, $ticketInfo['ticket_no'] ?? null);

        if ($ticketInfo) {
            $ticketInfo['links']['detail'] = $detailLink;
        }

        return [
            'ticket' => $ticketInfo,
            'primary' => $rows->first(),
            'children' => $rows->slice(1)->values()->all(),
            'tasks' => $rows->all(),
            'summary' => $this->summarizeTaskRows($rows),
            'links' => [
                'ticket' => $ticketInfo['links']['show'] ?? null,
                'detail' => $detailLink,
            ],
        ];
    }

    private function ticketDetailLink(?int $ticketId, ?string $ticketNo = null): ?string
    {
        if (! $ticketId && ! $ticketNo) {
            return null;
        }

        $identifier = $ticketNo;

        if (! $identifier && $ticketId) {
            $identifier = Ticket::query()->whereKey($ticketId)->value('ticket_no');
        }

        if (! $identifier) {
            return null;
        }

        return route('tasks.report.ticket', [
            'ticket' => $identifier,
        ]);
    }

    private function ticketReportDetailUrl(Ticket $ticket): string
    {
        $ticketNo = is_string($ticket->ticket_no ?? null) ? trim((string) $ticket->ticket_no) : '';
        if ($ticketNo !== '') {
            return route('tickets.report.detail.view', ['ticket' => $ticketNo]);
        }

        return route('tickets.edit', ['ticket' => $ticket->id]);
    }

    private function buildTicketReportPayload(Ticket $ticket): array
    {
        $tz = config('app.timezone');
        $ticketStatus = $this->normalizeStatus($ticket->status ?? null);

        return [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'title' => $ticket->title,
            'status' => $ticketStatus,
            'status_label' => WorkflowStatus::label($ticketStatus),
            'status_badge' => WorkflowStatus::badgeClass($ticketStatus),
            'due_display' => $this->formatDate($ticket->due_at ?? $ticket->due_date, 'd M Y H:i', $tz),
            'timeline' => [
                'start' => $this->formatDate($ticket->start_date ?? $ticket->created_at, 'd M Y H:i', $tz),
                'due' => $this->formatDate($ticket->due_at ?? $ticket->due_date, 'd M Y H:i', $tz),
                'end' => $this->formatDate($ticket->end_date ?? $ticket->updated_at, 'd M Y H:i', $tz),
            ],
            'links' => [
                'show' => $this->ticketReportDetailUrl($ticket),
            ],
        ];
    }

    private function buildTicketDetailPayload(Ticket $ticket, array $summary): array
    {
        $tz = config('app.timezone');
        $status = $this->normalizeStatus($ticket->status ?? null);

        $userSelect = implode(',', $this->userProfileSelectColumns());

        $ticket->loadMissing([
            'assignedUsers:'.$userSelect,
            'requester:'.$userSelect,
            'agent:'.$userSelect,
        ]);

        return [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'title' => $ticket->title,
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'status_badge' => WorkflowStatus::badgeClass($status),
            'priority' => ucfirst($ticket->priority ?? 'normal'),
            'type' => ucfirst($ticket->type ?? 'general'),
            'description' => $ticket->description ? $this->sanitizeDescription($ticket->description) : null,
            'timeline' => [
                'start' => $this->formatDate($ticket->start_date ?? $ticket->created_at, 'd M Y H:i', $tz),
                'due' => $this->formatDate($ticket->due_at ?? $ticket->due_date, 'd M Y H:i', $tz),
                'end' => $this->formatDate($ticket->finish_at ?? $ticket->updated_at, 'd M Y H:i', $tz),
            ],
            'requester' => $this->formatUserProfile($ticket->requester),
            'agent' => $this->formatUserProfile($ticket->agent),
            'assigned' => $ticket->assignedUsers->map(fn ($user) => $this->formatUserProfile($user))->filter()->values()->all(),
            'attachments' => $ticket->relationLoaded('attachments')
                ? $ticket->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'name' => $attachment->original_name,
                    'size' => $attachment->size,
                    'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                    'download_url' => $this->attachmentRoute('attachments.download', $attachment),
                ])->values()->all()
                : [],
            'summary' => $summary,
            'links' => [
                'ticket' => $this->ticketReportDetailUrl($ticket),
                'detail' => $this->ticketDetailLink($ticket->id, $ticket->ticket_no),
            ],
        ];
    }

    private function formatUserProfile(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $this->formatUserName($user) ?? ($user->email ?? null),
            'email' => $user->email,
            'unit' => $user->unit ?? null,
        ];
    }

    private function emptyTaskReportFilters(): array
    {
        return [
            'q' => '',
            'status' => '',
            'normalized_status' => null,
            'from' => '',
            'to' => '',
            'from_date' => null,
            'to_date' => null,
        ];
    }

    private function transformTaskReportRow(Task $task, bool $withTicket, string $origin, ?bool $preferTicketStatus = null): array
    {
        $preferTicketStatus ??= $withTicket;
        $tz = config('app.timezone');
        $statusPayload = $this->ticketAwareStatusPayload($task, $preferTicketStatus);
        $displayStatus = $statusPayload['display'];
        $taskStatus = $statusPayload['task'];
        $ticketStatusPayload = $statusPayload['ticket'];

        $ticketModel = ($task->relationLoaded('ticket') && $task->ticket) ? $task->ticket : null;
        $ticketInfo = null;
        if ($ticketModel) {
            $ticketInfo = $this->buildTicketReportPayload($ticketModel);
        }

        $projectInfo = null;
        if ($task->relationLoaded('project') && $task->project) {
            $projectStatus = $this->normalizeStatus($task->project->status ?? null);
            $projectInfo = [
                'id' => $task->project->id,
                'title' => $task->project->title,
                'project_no' => $task->project->project_no,
                'status' => $projectStatus,
                'status_label' => WorkflowStatus::label($projectStatus),
                'status_badge' => WorkflowStatus::badgeClass($projectStatus),
                'start_display' => $this->formatDate($task->project->start_date, 'd/m/Y', $tz),
                'end_display' => $this->formatDate($task->project->end_date, 'd/m/Y', $tz),
                'links' => [
                    'show' => route('projects.show', [
                        'project' => $task->project->public_slug,
                    ]),
                ],
            ];
        }

        $attachments = $task->relationLoaded('attachments')
            ? $task->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
            ])->values()->all()
            : [];

        $dueSource = $ticketModel ? ($ticketModel->due_at ?? $ticketModel->due_date) : ($task->due_at ?? $task->due_date);

        $taskTimeline = [
            'start' => $this->formatDate($task->start_date ?? null, 'd M Y', $tz),
            'due' => $this->formatDate($task->due_at ?? $task->due_date, 'd M Y H:i', $tz),
            'end' => $this->formatDate($task->end_date ?? null, 'd M Y', $tz),
        ];
        $ticketTimeline = $ticketInfo['timeline'] ?? null;

        $displayStatusValue = $displayStatus['value'];
        $displayStatusLabel = $displayStatus['label'];
        $displayStatusBadge = $displayStatus['badge'];

        return [
            'id' => $task->id,
            'task_no' => $task->task_no,
            'title' => $task->title,
            'slug' => $task->public_slug,
            'status' => $displayStatusValue,
            'status_label' => $displayStatusLabel,
            'status_badge' => $displayStatusBadge,
            'display_status' => [
                'label' => $displayStatusLabel,
                'badge' => $displayStatusBadge,
            ],
            'status_task' => $taskStatus['value'],
            'status_task_label' => $taskStatus['label'],
            'status_task_badge' => $taskStatus['badge'],
            'status_ticket' => $ticketStatusPayload['value'] ?? null,
            'status_ticket_label' => $ticketStatusPayload['label'] ?? null,
            'status_ticket_badge' => $ticketStatusPayload['badge'] ?? null,
            'priority' => $task->priority ?? 'normal',
            'priority_label' => ucfirst($task->priority ?? 'normal'),
            'due_display' => $this->formatDate($dueSource, 'd M Y H:i', $tz),
            'created_display' => $this->formatDate($task->created_at, 'd M Y H:i', $tz),
            'updated_display' => $this->formatDate($task->updated_at, 'd M Y H:i', $tz),
            'description' => $task->description ? $this->sanitizeDescription($task->description) : null,
            'ticket' => $ticketInfo,
            'project' => $projectInfo,
            'attachments' => $attachments,
            'timeline' => [
                'task' => $taskTimeline,
                'ticket' => $ticketTimeline,
            ],
            'requester' => $task->relationLoaded('requester') && $task->requester
                ? ['name' => $this->formatUserName($task->requester)]
                : null,
            'assignee' => $task->relationLoaded('assignee') && $task->assignee
                ? ['name' => $this->formatUserName($task->assignee)]
                : null,
            'links' => $this->buildTaskLinks($task, $origin),
            'type_label' => $withTicket ? 'Dari Ticket' : 'Mandiri',
        ];
    }

    private function ticketAwareStatusPayload(Task $task, bool $preferTicket): array
    {
        $taskStatus = $this->normalizeStatus($task->status ?? null);
        $ticketStatus = null;

        if ($task->relationLoaded('ticket') && $task->ticket) {
            $ticketStatus = $this->normalizeStatus($task->ticket->status ?? null);
        }

        $displayStatus = $preferTicket && $ticketStatus ? $ticketStatus : $taskStatus;

        return [
            'display' => [
                'value' => $displayStatus,
                'label' => WorkflowStatus::label($displayStatus),
                'badge' => WorkflowStatus::badgeClass($displayStatus),
            ],
            'task' => [
                'value' => $taskStatus,
                'label' => WorkflowStatus::label($taskStatus),
                'badge' => WorkflowStatus::badgeClass($taskStatus),
            ],
            'ticket' => $ticketStatus ? [
                'value' => $ticketStatus,
                'label' => WorkflowStatus::label($ticketStatus),
                'badge' => WorkflowStatus::badgeClass($ticketStatus),
            ] : null,
        ];
    }

    private function taskReportQueryParams(array $filters): array
    {
        return array_filter([
            'q' => $filters['q'] ?? null,
            'status' => $filters['status'] ?? null,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
        ], fn ($value) => filled($value));
    }

    public function downloadDetail(Request $request, string $locale, Task $task)
    {
        UnitVisibility::ensureTaskAccess($request->user(), $task);
        $task->load(['attachments', 'assignee', 'requester', 'ticket', 'ticket.assignedUsers', 'ticket.agent', 'project']);

        $payload = $this->transformTaskDetail($task);

        $description = strip_tags((string) ($payload['description'] ?? ''), '<p><br><strong><em><ul><ol><li><b><i><u>');
        if (trim(strip_tags($description)) === '') {
            $description = '<p>Tidak ada deskripsi.</p>';
        }

        $timeline = [
            'start' => $payload['start_display'] ?? '—',
            'due' => $payload['due_display'] ?? '—',
            'end' => $payload['end_display'] ?? '—',
        ];

        $attachments = collect($payload['attachments'] ?? [])->map(function (array $attachment) {
            return [
                'name' => $attachment['name'] ?? '—',
                'size' => $this->formatFileSize($attachment['size'] ?? null),
            ];
        })->values()->all();

        $assigned = $payload['assigned'] ?? [];

        $filename = sprintf(
            'task-%s-detail.pdf',
            $task->task_no ? Str::slug($task->task_no) : $task->id
        );

        return $this->reportExport->downloadDetailPdf('reports.pdf.task-detail', [
            'title' => $payload['title'] ?? 'Detail Task',
            'task' => $payload,
            'description' => $description,
            'timeline' => $timeline,
            'assigned' => $assigned,
            'attachments' => $attachments,
        ], $filename);
    }

    public function create(Request $request): Response
    {
        $statusOptions = collect(TaskStatus::values())
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => WorkflowStatus::label($value),
            ])
            ->values();

        $priorityOptions = collect([
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'critical' => 'Critical',
        ])->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values();

        $viewer = $request->user();
        $users = $this->getUsersList();
        $userOptions = $this->mapUserOptions($users);
        $canSelectRequester = $this->userCanSelectRequester($viewer);
        $allowedStatuses = $this->allowedTaskStatuses($viewer);
        $lockStatus = empty($allowedStatuses);
        $unitOptions = $this->unitOptionsForUser($users, $viewer);
        $ticketCollection = $this->linkableTicketsForUser($viewer);
        [$ticketOptions, $ticketUnits] = $this->prepareTicketOptions($ticketCollection);

        $defaults = [
            'status' => WorkflowStatus::normalize(WorkflowStatus::default()),
            'priority' => 'normal',
            'requester_id' => $request->user()?->id,
            'output_type' => 'task',
        ];

        return Inertia::render('Tasks/Create', [
            'statusOptions' => $statusOptions->all(),
            'priorityOptions' => $priorityOptions->all(),
            'ticketOptions' => $ticketOptions,
            'userOptions' => $userOptions,
            'defaults' => $defaults,
            'meta' => [
                'statusGuide' => $this->statusGuidance(),
                'statusDefault' => WorkflowStatus::label(WorkflowStatus::default()),
                'lockStatus' => $lockStatus,
                'canSelectRequester' => $canSelectRequester,
                'requesterLabel' => $this->userDisplayName($viewer),
                'allowedStatuses' => $allowedStatuses,
                'unitOptions' => $unitOptions,
                'ticketUnits' => $ticketUnits,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('due_at')) {
            try {
                $due = Carbon::parse($request->input('due_at'));
                $request->merge([
                    'due_date' => $due->format('Y-m-d'),
                    'due_time' => $due->format('H:i'),
                ]);
            } catch (\Throwable) {
                // validation below will handle invalid date
            }
        }

        $this->normalizeAssigneeInput($request);

        // Gabungkan due_date + due_time => due_at (agar tersimpan & bisa tampil saat edit)
        $dueAt = $this->combineDueAt($request->input('due_date'), $request->input('due_time'));
        if ($dueAt) {
            $request->merge([
                'due_at' => $dueAt->format('Y-m-d H:i:s'),
                'due_date' => $dueAt->format('Y-m-d'),
                'due_time' => $dueAt->format('H:i'),
            ]);
        } elseif ($request->filled('due_date')) {
            $normalized = $this->normalizeDueDate($request->input('due_date'));
            if ($normalized) {
                $request->merge(['due_date' => $normalized]);
            }
        }

        $this->normalizeTimelineInputs($request);

        $viewer = $request->user();
        if (! $this->userCanSelectRequester($viewer)) {
            $request->merge(['requester_id' => $viewer?->id]);
        } elseif (! $request->filled('requester_id') && $viewer) {
            $request->merge(['requester_id' => $viewer->id]);
        }

        // Validasi
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'ticket_id' => ['nullable', 'integer', 'exists:tickets,id'],
            'priority' => ['nullable', 'in:low,normal,high,critical'],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['integer', 'exists:users,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_to' => ['nullable'],
            'requester_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'string'],
            'due_time' => ['nullable', 'string'],
            'due_at' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        if ($request->filled('description')) {
            $request->merge(['description' => $this->sanitizeDescription($request->input('description'))]);
        }

        $data = TaskData::fromRequest($request);
        if ($this->userHasStatusOverride($viewer)) {
            $data->status = WorkflowStatus::normalize($request->input('status'));
        } else {
            $data->status = WorkflowStatus::default();
        }

        $task = DB::transaction(function () use ($request, $data) {
            /** @var \App\Models\Task $task */
            $task = $this->createTask->execute($data);

            // Apply META (priority/assignee/requester/due_at)
            $meta = $this->extractMeta($request);
            $timeline = $this->extractTimelineMeta($request);
            $payload = array_merge($meta, $timeline);

            if (! empty($payload)) {
                $task->fill($payload)->save();
            }

            // Lampiran
            $this->attachments->adoptFromServerIds($request->input('attachments', []), $task->refresh());

            // Opsi project
            if ($request->input('output_type') === 'task_project') {
                $project = Project::create([
                    'title' => $request->input('project_title') ?: $task->title,
                    'description' => $task->description,
                    'status' => WorkflowStatus::IN_PROGRESS,
                    'status_id' => WorkflowStatus::code(WorkflowStatus::IN_PROGRESS),
                    'ticket_id' => $task->ticket_id,
                    'created_by' => auth()->id(),
                    'start_date' => $request->input('project_start') ?: now(),
                    'end_date' => $request->input('project_end'),
                    'project_no' => $this->generateProjectNo(),
                ]);

                if (Schema::hasColumn($task->getTable(), 'project_id')) {
                    $task->project_id = $project->id;
                    $task->save();
                }
            }

            return $task;
        });

        // adopt lampiran (jika ada)
        $this->attachments->adoptFromServerIds($request->input('attachments', []), $task);

        $assigneeIds = $request->input('assignees', []);
        if (! is_array($assigneeIds)) {
            $assigneeIds = [];
        }
        $assigneeIds = array_values(array_unique(array_filter(array_map(function ($value) {
            if (is_int($value)) {
                return $value > 0 ? $value : null;
            }
            if (is_numeric($value)) {
                $intVal = (int) $value;

                return $intVal > 0 ? $intVal : null;
            }

            return null;
        }, $assigneeIds), fn ($v) => $v !== null)));

        if (empty($assigneeIds) && $task->assignee_id) {
            $assigneeIds = [$task->assignee_id];
        }

        app(WorkItemNotifier::class)->notifyTaskCreated($task, $assigneeIds, Auth::user());

        $locale = $request->route('locale') ?? app()->getLocale();

        return redirect()
            ->route('tasks.create', ['locale' => $locale])
            ->with('success', 'Task created successfully.');
    }

    public function showBySlug(Request $request, string $locale, string $taskSlug): Response
    {
        $task = Task::findByPublicSlug($taskSlug);
        abort_if(! $task, 404);

        return $this->show($locale, $task, $request);
    }

    public function show(string $locale, Task $task, Request $request): Response
    {
        UnitVisibility::ensureTaskAccess($request->user(), $task);
        $task->load(['attachments', 'assignee', 'requester', 'ticket', 'ticket.assignedUsers', 'ticket.agent', 'project']);

        $backUrl = $this->resolveBackUrl($request, route('tasks.report'));

        $taskPayload = $this->transformTaskDetail($task);
        $taskPayload['links']['edit'] = route('tasks.edit', [
            'task' => $task->public_slug,
        ]);
        $taskPayload['links']['view'] = route('tasks.view', [
            'task' => $task->id,
        ]);
        $taskPayload['related_tasks'] = $this->relatedTasksForTicket($task, $request);

        return Inertia::render('Tasks/Show', [
            'task' => $taskPayload,
            'meta' => [
                'backUrl' => $backUrl,
            ],
        ]);
    }

    public function view(string $locale, Task $task, Request $request): Response
    {
        UnitVisibility::ensureTaskAccess($request->user(), $task);
        $task->load(['attachments', 'assignee', 'requester', 'ticket', 'ticket.assignedUsers', 'ticket.agent', 'project']);
        $task->load(['attachments', 'assignee', 'requester', 'ticket', 'ticket.assignedUsers', 'ticket.agent', 'project']);
        $taskPayload = $this->transformTaskDetail($task);
        $taskPayload['related_tasks'] = $this->relatedTasksForTicket($task, $request);

        return Inertia::render('Tasks/View', [
            'task' => $taskPayload,
        ]);
    }

    public function edit(string $locale, Task $task, Request $request): Response
    {
        $viewer = $request->user();
        UnitVisibility::ensureTaskAccess($viewer, $task);
        $task->load([
            'attachments',
            'assignee',
            'requester',
            'ticket',
            'ticket.assignedUsers',
            'ticket.agent',
            'ticket.attachments' => function ($query) {
                $query->select(['id', 'attachable_id', 'attachable_type', 'original_name', 'size', 'created_at']);
            },
            'project',
        ]);

        $statusOptions = collect(TaskStatus::values())
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => WorkflowStatus::label($value),
            ])
            ->values();

        $priorityOptions = collect([
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'critical' => 'Critical',
        ])->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values();

        $users = $this->getUsersList();
        $userOptions = $this->mapUserOptions($users);
        $canSelectRequester = $this->userCanSelectRequester($viewer);
        $allowedStatuses = $this->allowedTaskStatuses($viewer, $task);
        $lockStatus = empty($allowedStatuses);
        $unitOptions = $this->unitOptionsForUser($users, $viewer);
        $ticketCollection = $this->linkableTicketsForUser($viewer, $task->ticket);
        [$ticketOptions, $ticketUnits] = $this->prepareTicketOptions($ticketCollection);

        $backUrl = $this->resolveBackUrl($request, route('tasks.report'));

        return Inertia::render('Tasks/Edit', [
            'task' => $this->transformTaskForm($task),
            'statusOptions' => $statusOptions->all(),
            'priorityOptions' => $priorityOptions->all(),
            'ticketOptions' => $ticketOptions,
            'userOptions' => $userOptions,
            'meta' => [
                'backUrl' => $backUrl,
                'mode' => 'edit',
                'submitUrl' => route('tasks.update', ['task' => $task->id]),
                'statusGuide' => $this->statusGuidance(),
                'statusDefault' => WorkflowStatus::label(WorkflowStatus::default()),
                'lockStatus' => $lockStatus,
                'canSelectRequester' => $canSelectRequester,
                'requesterLabel' => $this->userDisplayName($task->requester) ?? $this->userDisplayName($viewer),
                'allowedStatuses' => $allowedStatuses,
                'unitOptions' => $unitOptions,
                'ticketUnits' => $ticketUnits,
            ],
        ]);
    }

    public function update(Request $request, string $locale, Task $task): RedirectResponse
    {
        $viewer = $request->user();
        UnitVisibility::ensureTaskAccess($viewer, $task);
        $previousAssignees = $this->collectTaskAssigneeIds($task);

        try {
            \Log::debug('tasks.update.payload_keys', array_keys($request->all()));
        } catch (\Throwable) {
        }

        if (! $request->filled('title')) {
            $request->merge(['title' => $task->title]);
        }
        if (! $request->filled('status')) {
            $request->merge(['status' => $task->status ?? WorkflowStatus::default()]);
        }
        try {
            \Log::debug('tasks.update.input_snapshot', [
                'task_id' => $task->id,
                'before_status' => $task->status,
                'before_title' => $task->title,
                'input_title' => $request->input('title'),
                'input_status' => $request->input('status'),
            ]);
        } catch (\Throwable) {
        }

        if ($request->filled('due_at')) {
            try {
                $due = Carbon::parse($request->input('due_at'));
                $request->merge([
                    'due_date' => $due->format('Y-m-d'),
                    'due_time' => $due->format('H:i'),
                ]);
            } catch (\Throwable) {
                // let validation handle invalid inputs
            }
        }

        // Gabungkan due_date + due_time -> due_at agar konsisten
        $dueAt = $this->combineDueAt($request->input('due_date'), $request->input('due_time'));
        if ($dueAt) {
            $request->merge([
                'due_at' => $dueAt->format('Y-m-d H:i:s'),
                'due_date' => $dueAt->format('Y-m-d'),
                'due_time' => $dueAt->format('H:i'),
            ]);
        } elseif ($request->filled('due_date')) {
            $normalized = $this->normalizeDueDate($request->input('due_date'));
            if ($normalized) {
                $request->merge(['due_date' => $normalized]);
            }
        }
        $this->normalizeAssigneeInput($request);
        $this->normalizeTimelineInputs($request);

        if (! $this->userCanSelectRequester($viewer)) {
            $request->merge(['requester_id' => $task->requester_id ?? $viewer?->id]);
        } elseif (! $request->filled('requester_id') && $viewer) {
            $request->merge(['requester_id' => $viewer->id]);
        }

        // Validasi meta & field penting
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string'],
            'ticket_id' => ['nullable', 'integer', 'exists:tickets,id'],
            'priority' => ['nullable', 'in:low,normal,high,critical'],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['integer', 'exists:users,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_to' => ['nullable'],
            'requester_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'string'],
            'due_time' => ['nullable', 'string'],
            'due_at' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        if ($request->filled('description')) {
            $request->merge(['description' => $this->sanitizeDescription($request->input('description'))]);
        }

        $data = TaskData::fromRequest($request);

        $currentStatus = WorkflowStatus::normalize($task->status);
        $requestedStatus = WorkflowStatus::normalize($data->status ?? $currentStatus);

        if ($requestedStatus !== $currentStatus) {
            if (! $task->canUserSetStatus($request->user(), $requestedStatus)) {
                return back()->with('error', 'Anda tidak memiliki izin untuk mengubah status task ini.');
            }
        }
        $data->status = $requestedStatus;

        $this->updateTask->execute($task, $data);
        try {
            \Log::debug('tasks.update.after_execute', [
                'task_id' => $task->id,
                'status' => $task->status,
                'title' => $task->title,
            ]);
        } catch (\Throwable) {
        }

        // Apply META pasca update
        $meta = $this->extractMeta($request);
        $timeline = $this->extractTimelineMeta($request);
        $payload = array_merge($meta, $timeline);

        if (! empty($payload)) {
            $task->fill($payload)->save();
        }

        // Lampiran baru (jika ada)
        $task->refresh();
        $this->attachments->adoptFromServerIds($request->input('attachments', []), $task);

        $assigneeIds = $request->input('assignees', []);
        if (! is_array($assigneeIds)) {
            $assigneeIds = [];
        }
        if ($request->filled('assignee_id')) {
            $assigneeIds[] = (int) $request->input('assignee_id');
        }
        $assigneeIds = array_values(array_unique(array_filter(array_map(fn ($value) => (int) $value, $assigneeIds), fn ($v) => $v > 0)));

        $currentAssignees = $this->collectTaskAssigneeIds($task);
        $assignedDiff = array_values(array_diff($currentAssignees ?: $assigneeIds, $previousAssignees));
        if (! empty($assignedDiff)) {
            app(WorkItemNotifier::class)->notifyTaskAssigned($task, $assignedDiff, Auth::user());
        }

        if ($currentStatus !== WorkflowStatus::CANCELLED && $requestedStatus === WorkflowStatus::CANCELLED) {
            app(WorkItemNotifier::class)->notifyTaskCancelled($task, $viewer);
        }

        $locale = $request->route('locale') ?? app()->getLocale();
        $backTo = route('tasks.show', [
            'locale' => $locale,
            'taskSlug' => $task->public_slug,
        ]);

        return redirect()->to($backTo)->with('success', 'Task updated successfully.');
    }

    public function destroy(Request $request, string $locale, Task $task): RedirectResponse
    {
        UnitVisibility::ensureTaskAccess($request->user(), $task);
        $ticketId = $task->ticket_id;
        $statusValue = $task->status instanceof \BackedEnum ? $task->status->value : $task->status;

        DB::transaction(function () use ($task, $ticketId) {
            $this->deleteTask->execute($task);
        });

        $backTo = $request->input('from', url()->previous() ?: route('tasks.report', ['status' => $statusValue]));

        return redirect()->to($backTo)->with('success', 'Task deleted.');
    }

    /** Promote Task -> Project */
    public function promoteToProject(Request $request, string $locale, Task $task): RedirectResponse
    {
        UnitVisibility::ensureTaskAccess($request->user(), $task);
        if ($task->project_id) {
            return back()->with('error', 'Task sudah terhubung ke Project.');
        }

        DB::transaction(function () use ($task) {
            $project = Project::create([
                'title' => $task->title,
                'description' => $task->description,
                'status' => WorkflowStatus::IN_PROGRESS,
                'status_id' => WorkflowStatus::code(WorkflowStatus::IN_PROGRESS),
                'ticket_id' => $task->ticket_id,
                'created_by' => auth()->id(),
                'start_date' => now(),
                'end_date' => null,
                'project_no' => $this->generateProjectNo(),
            ]);

            $task->update(['project_id' => $project->id]);

            if ($project->ticket_id) {
                $this->updateLinkedTicketStatus($project->ticket_id, WorkflowStatus::IN_PROGRESS);
            }
        });

        $task->load('project');

        try {
            $creator = Auth::user();
            if ($creator) {
                $projectLink = $task->project
                    ? route('projects.show', ['project' => $task->project->public_slug])
                    : route('projects.report');

                $creator->notify(new ActivityNotification([
                    'title' => 'Project Baru',
                    'message' => 'Task dipromosikan menjadi project: '.$task->title,
                    'url' => $projectLink,
                    'icon' => 'work',
                    'by' => $creator->display_name,
                    'subject_type' => 'project',
                    'subject_id' => $task->project?->id,
                ]));
            }
        } catch (\Throwable $e) {
        }

        return back()->with('success', 'Berhasil mempromosikan Task menjadi Project.');
    }

    /** Helper: generate nomor project unik */
    private function generateProjectNo(): string
    {
        do {
            $no = 'PRJ'.date('Y').str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Project::where('project_no', $no)->exists());

        return $no;
    }

    private function transformTaskInProgress(Task $task, ?string $origin = null): array
    {
        $status = $this->normalizeStatus($task->status ?? null);

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description ? strip_tags((string) $task->description) : null,
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'updated_diff' => $task->updated_at?->diffForHumans(),
            'links' => $this->buildTaskLinks($task, $origin),
        ];
    }

    private function transformTaskSummary(Task $task, ?string $origin = null): array
    {
        $status = $this->normalizeStatus($task->status ?? null);
        $statusLabel = WorkflowStatus::label($status);
        $priority = $task->priority ?? 'normal';
        $tz = config('app.timezone');
        $ticketDue = ($task->relationLoaded('ticket') && $task->ticket)
            ? ($task->ticket->due_at ?? $task->ticket->due_date)
            : null;
        $dueSource = $ticketDue ?? $task->due_at ?? $task->due_date;

        $statusCodes = [
            WorkflowStatus::NEW => 'NEW',
            WorkflowStatus::IN_PROGRESS => 'INPR',
            WorkflowStatus::CONFIRMATION => 'CONF',
            WorkflowStatus::REVISION => 'REVS',
            WorkflowStatus::DONE => 'DONE',
            WorkflowStatus::CANCELLED => 'CANC',
            WorkflowStatus::ON_HOLD => 'HOLD',
        ];

        return [
            'id' => $task->id,
            'task_no' => $task->task_no,
            'title' => $task->title,
            'slug' => $task->public_slug,
            'priority' => $priority,
            'status' => $status,
            'status_label' => $statusLabel,
            'status_id' => $statusCodes[$status] ?? strtoupper(substr(str_replace([' ', '_'], '', $status), 0, 4)),
            'due_display' => $this->formatDate($dueSource, 'Y-m-d H:i', $tz),
            'attachments' => $task->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
            ])->values()->all(),
            'links' => array_merge(
                $this->buildTaskLinks($task, $origin),
                ['promote' => route('tasks.promote', $task)]
            ),
        ];
    }

    private function taskShowLink(Task $task, ?string $origin = null): string
    {
        return route('tasks.show', ['taskSlug' => $task->public_slug]);
    }

    private function buildTaskLinks(Task $task, ?string $origin = null): array
    {
        return [
            'show' => $this->taskShowLink($task, $origin),
            'edit' => route('tasks.edit', ['task' => $task->public_slug]),
            'delete' => route('tasks.destroy', ['task' => $task->id]),
        ];
    }

    private function transformTaskDetail(Task $task): array
    {
        $tz = config('app.timezone');
        $statusPayload = $this->ticketAwareStatusPayload($task, false);
        $displayStatus = $statusPayload['display'];
        $taskStatus = $statusPayload['task'];
        $ticketStatus = $statusPayload['ticket'];
        $priority = $task->priority ?? null;

        $ticketModel = ($task->relationLoaded('ticket') && $task->ticket) ? $task->ticket : null;
        $ticket = null;
        if ($ticketModel) {
            $ticket = [
                'id' => $ticketModel->id,
                'title' => $ticketModel->title,
                'ticket_no' => $ticketModel->ticket_no,
                'status' => $ticketStatus['value'] ?? null,
                'status_label' => $ticketStatus['label'] ?? null,
                'status_badge' => $ticketStatus['badge'] ?? null,
                'timeline' => [
                    'start' => $this->formatDate($ticketModel->start_date ?? $ticketModel->created_at, 'd M Y', $tz),
                    'due' => $this->formatDate($ticketModel->due_at ?? $ticketModel->due_date, 'd M Y H:i', $tz),
                    'end' => $this->formatDate($ticketModel->end_date ?? $ticketModel->updated_at, 'd M Y', $tz),
                ],
                'link' => $this->ticketReportDetailUrl($ticketModel),
            ];
        }

        $project = null;
        if ($task->relationLoaded('project') && $task->project) {
            $project = [
                'id' => $task->project->id,
                'title' => $task->project->title,
                'link' => route('projects.show', ['project' => $task->project->public_slug]),
            ];
        }

        $assignee = null;
        if ($task->relationLoaded('assignee') && $task->assignee) {
            $assignee = [
                'id' => $task->assignee->id,
                'name' => $task->assignee->display_name ?? $task->assignee->email,
                'email' => $task->assignee->email,
            ];
        }

        $requester = null;
        if ($task->relationLoaded('requester') && $task->requester) {
            $requester = [
                'id' => $task->requester->id,
                'name' => $task->requester->display_name ?? $task->requester->email,
                'email' => $task->requester->email,
            ];
        }

        $taskTimeline = [
            'start' => $this->formatDate($task->start_date, 'd M Y', $tz),
            'due' => $this->formatDate($task->due_at ?? $task->due_date, 'd M Y H:i', $tz),
            'end' => $this->formatDate($task->end_date, 'd M Y', $tz),
        ];
        $ticketTimeline = $ticket['timeline'] ?? null;

        $dueSource = $ticketModel
            ? ($ticketModel->due_at ?? $ticketModel->due_date)
            : ($task->due_at ?? $task->due_date);

        $assignmentPayload = $this->ticketAssignmentPayload($ticketModel);
        if (empty($assignmentPayload['entries'])) {
            $fallbackAssigned = $this->resolveAssigned($task);
            $assignmentPayload = [
                'source' => 'task',
                'agent' => null,
                'pics' => $fallbackAssigned,
                'entries' => $fallbackAssigned,
            ];
        }

        return [
            'id' => $task->id,
            'task_no' => $task->task_no,
            'title' => $task->title,
            'slug' => $task->public_slug,
            'description' => $task->description,
            'status' => $displayStatus['value'],
            'status_label' => $displayStatus['label'],
            'status_badge' => $displayStatus['badge'],
            'status_task' => $taskStatus['value'],
            'status_task_label' => $taskStatus['label'],
            'status_task_badge' => $taskStatus['badge'],
            'status_ticket' => $ticketStatus['value'] ?? null,
            'status_ticket_label' => $ticketStatus['label'] ?? null,
            'status_ticket_badge' => $ticketStatus['badge'] ?? null,
            'priority' => $priority,
            'priority_label' => $priority ? ucfirst($priority) : null,
            'due_display' => $this->formatDate($dueSource, 'd M Y H:i', $tz),
            'due_at' => $task->due_at?->timezone($tz)->format('Y-m-d H:i'),
            'start_display' => $this->formatDate($task->start_date, 'd M Y', $tz),
            'end_display' => $this->formatDate($task->end_date, 'd M Y', $tz),
            'ticket' => $ticket,
            'project' => $project,
            'assignee' => $assignee,
            'requester' => $requester,
            'assigned' => $assignmentPayload['entries'],
            'assignment' => $assignmentPayload,
            'timeline' => [
                'task' => $taskTimeline,
                'ticket' => $ticketTimeline,
            ],
            'attachments' => $task->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
            ])->values()->all(),
            'links' => [
                'show' => $this->taskShowLink($task),
                'edit' => route('tasks.edit', ['task' => $task->public_slug]),
                'view' => route('tasks.view', $task),
                'pdf' => route('tasks.report.detail', $task),
                'promote' => route('tasks.promote', $task),
            ],
        ];
    }

    private function relatedTasksForTicket(Task $task, Request $request): array
    {
        if (! $task->ticket_id) {
            return [];
        }

        $viewer = $request->user();
        $tz = config('app.timezone');

        $query = Task::query()
            ->where('ticket_id', $task->ticket_id)
            ->orderByDesc('created_at');

        $query = UnitVisibility::scopeTasks($query, $viewer);

        $ticketColumns = implode(',', array_unique(array_filter([
            'id',
            'status',
            Schema::hasColumn('tickets', 'due_at') ? 'due_at' : null,
            Schema::hasColumn('tickets', 'due_date') ? 'due_date' : null,
        ])));

        $query->with(['ticket:'.$ticketColumns]);

        return $query->get()->map(function (Task $related) use ($task, $tz) {
            $statusPayload = $this->ticketAwareStatusPayload($related, false);
            $displayStatus = $statusPayload['display'];
            $ticketDue = $related->relationLoaded('ticket') && $related->ticket
                ? ($related->ticket->due_at ?? $related->ticket->due_date)
                : null;
            $dueSource = $ticketDue ?? ($related->due_at ?? ($related->due_date ?? null));

            return [
                'id' => $related->id,
                'task_no' => $related->task_no,
                'title' => $related->title,
                'slug' => $related->public_slug,
                'is_current' => $related->id === $task->id,
                'display_status' => $displayStatus,
                'priority_label' => $related->priority ? ucfirst($related->priority) : null,
                'due_display' => $this->formatDate($dueSource, 'd M Y H:i', $tz),
                'links' => [
                    'show' => $this->taskShowLink($related),
                    'edit' => route('tasks.edit', ['task' => $related->public_slug]),
                    'delete' => $related->id === $task->id ? null : route('tasks.destroy', ['task' => $related->id]),
                ],
            ];
        })->values()->all();
    }

    private function transformTaskForm(Task $task, ?string $timezone = null): array
    {
        $timezone ??= config('app.timezone');

        $status = $this->normalizeStatus($task->status ?? null);
        $priority = $task->priority ?? 'normal';
        $assignedIds = $this->extractAssignedIds($task);
        $ticket = $task->relationLoaded('ticket') ? $task->ticket : null;

        if (empty($assignedIds) && $ticket && $ticket->relationLoaded('assignedUsers')) {
            $assignedIds = $ticket->assignedUsers->pluck('id')->all();
        }

        $assigneeId = $task->assignee_id;
        if (! $assigneeId && $ticket) {
            $assigneeId = $ticket->agent_id ?? $ticket->assignee_id;
        }

        $ticketAttachments = [];
        if ($ticket && $ticket->relationLoaded('attachments')) {
            $ticketAttachments = $ticket->attachments
                ->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'name' => $attachment->original_name ?? 'Lampiran ticket',
                    'size' => $attachment->size,
                    'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                    'download_url' => $this->attachmentRoute('attachments.download', $attachment),
                ])
                ->values()
                ->all();
        }

        $dueAt = $task->due_at
            ?: ($ticket?->due_at ?? $ticket?->due_date);

        $projectTitle = $task->project_title
            ?? ($task->relationLoaded('project') && $task->project ? $task->project->title : null);

        $projectStart = $task->project_start ?? $task->start_date;
        $projectEnd = $task->project_end ?? $task->end_date;

        return [
            'id' => $task->id,
            'slug' => $task->public_slug,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $status,
            'priority' => $priority,
            'ticket_id' => $task->ticket_id,
            'assignees' => array_map('intval', array_filter($assignedIds, fn ($id) => is_numeric($id))),
            'assigned_to' => array_map('intval', array_filter($assignedIds, fn ($id) => is_numeric($id))),
            'assignee_id' => $assigneeId,
            'requester_id' => $task->requester_id,
            'due_at' => $dueAt ? Carbon::parse($dueAt)->copy()->timezone($timezone)->format('Y-m-d H:i') : null,
            'start_date' => $task->start_date ? Carbon::parse($task->start_date)->format('Y-m-d') : null,
            'end_date' => $task->end_date ? Carbon::parse($task->end_date)->format('Y-m-d') : null,
            'output_type' => $task->output_type ?? 'task',
            'project_title' => $projectTitle,
            'project_start' => $projectStart ? Carbon::parse($projectStart)->format('Y-m-d') : null,
            'project_end' => $projectEnd ? Carbon::parse($projectEnd)->format('Y-m-d') : null,
            'attachments' => $task->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
                'delete_url' => $this->attachmentRoute('attachments.destroy', $attachment),
            ])->values()->all(),
            'ticket_attachments' => $ticketAttachments,
            'ticket_attachment_count' => (int) ($ticket?->attachments_count ?? count($ticketAttachments)),
        ];
    }

    private function resolveAssigned(Task $task): array
    {
        $entries = collect();

        if ($task->relationLoaded('assignee') && $task->assignee) {
            $entries->push([
                'id' => $task->assignee->id,
                'name' => $task->assignee->display_name ?? $task->assignee->email,
                'email' => $task->assignee->email,
            ]);
        }

        $rawAssigned = $task->assigned_to;
        $assignedIds = [];
        $fallbackNames = [];

        if (is_string($rawAssigned)) {
            $decoded = json_decode($rawAssigned, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $assignedIds = array_values(array_unique(array_filter(array_map(function ($value) {
                    if (is_numeric($value)) {
                        $int = (int) $value;

                        return $int > 0 ? $int : null;
                    }

                    return null;
                }, $decoded), fn ($v) => $v !== null)));
            } else {
                $fallbackNames = array_map('trim', array_filter(explode(',', $rawAssigned), fn ($name) => trim($name) !== ''));
            }
        } elseif (is_array($rawAssigned)) {
            $assignedIds = array_values(array_unique(array_filter(array_map(function ($value) {
                if (is_numeric($value)) {
                    $int = (int) $value;

                    return $int > 0 ? $int : null;
                }

                return null;
            }, $rawAssigned), fn ($v) => $v !== null)));
        }

        if (! empty($assignedIds)) {
            $users = User::whereIn('id', $assignedIds)->get()->keyBy('id');
            foreach ($assignedIds as $id) {
                $user = $users->get($id);
                if ($user) {
                    $entries->push([
                        'id' => $user->id,
                        'name' => $user->display_name ?? $user->email,
                        'email' => $user->email,
                    ]);
                }
            }
        }

        foreach ($fallbackNames as $name) {
            $entries->push([
                'id' => null,
                'name' => $name,
                'email' => null,
            ]);
        }

        return $entries
            ->filter(fn ($entry) => ! empty($entry['name']))
            ->unique(fn ($entry) => strtolower($entry['name']))
            ->values()
            ->all();
    }

    private function ticketAssignmentPayload(?Ticket $ticket): array
    {
        $payload = [
            'source' => 'task',
            'agent' => null,
            'pics' => [],
            'entries' => [],
        ];

        if (! $ticket) {
            return $payload;
        }

        $payload['source'] = 'ticket';

        if ($ticket->relationLoaded('agent') && $ticket->agent) {
            $agentEntry = $this->formatAssignmentEntry($ticket->agent, 'Agent');
            $payload['agent'] = $agentEntry;
            $payload['entries'][] = $agentEntry;
        }

        if ($ticket->relationLoaded('assignedUsers') && $ticket->assignedUsers) {
            foreach ($ticket->assignedUsers as $user) {
                $entry = $this->formatAssignmentEntry($user, 'PIC');
                $payload['pics'][] = $entry;
                $payload['entries'][] = $entry;
            }
        }

        $payload['pics'] = array_values($payload['pics']);
        $payload['entries'] = array_values($payload['entries']);

        return $payload;
    }

    private function formatAssignmentEntry(User $user, string $role): array
    {
        return [
            'id' => $user->id,
            'name' => $this->formatUserName($user) ?? ($user->email ?? ('User #'.$user->id)),
            'email' => $user->email,
            'role' => $role,
        ];
    }

    private function extractAssignedIds(Task $task): array
    {
        $rawAssigned = $task->assigned_to;
        $assignedIds = [];

        if (is_string($rawAssigned)) {
            $decoded = json_decode($rawAssigned, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $assignedIds = $decoded;
            }
        } elseif (is_array($rawAssigned)) {
            $assignedIds = $rawAssigned;
        }

        $assignedIds = array_values(array_unique(array_filter(array_map(function ($value) {
            if (is_numeric($value)) {
                $int = (int) $value;

                return $int > 0 ? $int : null;
            }

            return null;
        }, $assignedIds), fn ($v) => $v !== null)));

        if (empty($assignedIds) && $task->assignee_id) {
            $assignedIds[] = (int) $task->assignee_id;
        }

        return $assignedIds;
    }

    private function normalizeStatus($status): string
    {
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        return WorkflowStatus::normalize($status ?? WorkflowStatus::NEW);
    }

    private function formatUserName(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $parts = array_filter([
            $user->first_name ?? null,
            $user->last_name ?? null,
        ], fn ($value) => filled($value));

        if (! empty($parts)) {
            return trim(implode(' ', $parts));
        }

        foreach (['name', 'full_name', 'username', 'email'] as $attribute) {
            if (! empty($user->{$attribute})) {
                return (string) $user->{$attribute};
            }
        }

        return 'User #'.$user->id;
    }

    private function resolveTaskReportFilters(Request $request): array
    {
        $statusInput = $request->query('status');
        $normalizedStatus = $statusInput ? WorkflowStatus::normalize($statusInput) : null;
        if ($normalizedStatus && ! in_array($normalizedStatus, WorkflowStatus::all(), true)) {
            $normalizedStatus = null;
        }

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) ($statusInput ?? ''),
            'normalized_status' => $normalizedStatus,
            'from' => (string) ($request->query('from') ?? ''),
            'to' => (string) ($request->query('to') ?? ''),
        ];

        $filters['from_date'] = $this->parseDateParam($filters['from'], false);
        $filters['to_date'] = $this->parseDateParam($filters['to'], true);

        return $filters;
    }

    private function baseTaskReportQuery(array $filters, bool $hasDueDateColumn, ?User $viewer = null): Builder
    {
        $query = Task::query();
        $query = UnitVisibility::scopeTasks($query, $viewer);

        if ($filters['q'] !== '') {
            $query->where(function ($inner) use ($filters) {
                $inner->where('title', 'like', '%'.$filters['q'].'%')
                    ->orWhere('description', 'like', '%'.$filters['q'].'%');
            });
        }

        if ($filters['normalized_status']) {
            $query->where(function (Builder $inner) use ($filters) {
                $inner->where('status', $filters['normalized_status'])
                    ->orWhereHas('ticket', function (Builder $ticket) use ($filters) {
                        $ticket->where('status', $filters['normalized_status']);
                    });
            });
        }

        if ($filters['from_date']) {
            $fromDate = $filters['from_date'];
            $query->where(function ($inner) use ($fromDate, $hasDueDateColumn) {
                $inner->whereDate('due_at', '>=', $fromDate);
                if ($hasDueDateColumn) {
                    $inner->orWhereDate('due_date', '>=', $fromDate);
                }
            });
        }

        if ($filters['to_date']) {
            $toDate = $filters['to_date'];
            $query->where(function ($inner) use ($toDate, $hasDueDateColumn) {
                $inner->whereDate('due_at', '<=', $toDate);
                if ($hasDueDateColumn) {
                    $inner->orWhereDate('due_date', '<=', $toDate);
                }
            });
        }

        return $query;
    }

    private function buildTaskReportQuery(array $filters, bool $hasDueDateColumn, ?User $viewer = null, bool $withRelations = false): Builder
    {
        $selectColumns = [
            'id',
            'title',
            'status',
            'created_at',
            'updated_at',
        ];

        foreach (['task_no', 'priority', 'description', 'due_at', 'ticket_id', 'project_id', 'assignee_id', 'requester_id', 'status_id', 'start_date', 'end_date'] as $column) {
            if (Schema::hasColumn('tasks', $column)) {
                $selectColumns[] = $column;
            }
        }

        if ($hasDueDateColumn) {
            $selectColumns[] = 'due_date';
        }

        $query = $this->baseTaskReportQuery($filters, $hasDueDateColumn, $viewer)
            ->select($selectColumns);

        if ($withRelations) {
            $userColumns = $this->userRelationColumns();
            $query->with([
                'attachments:id,attachable_id,attachable_type,original_name,size',
                'ticket:'.$this->ticketRelationColumns(),
                'project:'.$this->projectRelationColumns(),
                'assignee:'.$userColumns,
                'requester:'.$userColumns,
            ]);
        } else {
            $query->with(['attachments:id,attachable_id,attachable_type,original_name,size']);
        }

        return $query;
    }

    private function formatRange(?Carbon $from, ?Carbon $to): string
    {
        if (! $from && ! $to) {
            return 'Semua';
        }

        $format = 'd M Y';
        $tz = config('app.timezone');

        if ($from && $to) {
            return $from->timezone($tz)->format($format).' - '.$to->timezone($tz)->format($format);
        }

        if ($from) {
            return 'Mulai '.$from->timezone($tz)->format($format);
        }

        return 'Sampai '.$to->timezone($tz)->format($format);
    }

    private function parseDateParam(?string $value, bool $endOfDay = false): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $value);

                return $endOfDay ? $dt->endOfDay() : $dt->startOfDay();
            } catch (\Throwable) {
            }
        }

        try {
            $dt = Carbon::parse($value);

            return $endOfDay ? $dt->endOfDay() : $dt->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function userRelationColumns(): string
    {
        $columns = ['id'];
        foreach (['first_name', 'last_name', 'name', 'full_name', 'username', 'email'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        return implode(',', array_unique($columns));
    }

    private function userProfileSelectColumns(): array
    {
        $columns = ['id'];
        foreach (['first_name', 'last_name', 'name', 'full_name', 'username', 'email', 'unit'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        return array_unique($columns);
    }

    private function ticketRelationColumns(): string
    {
        $columns = ['id', 'title', 'status'];

        foreach (['ticket_no', 'due_at', 'due_date', 'start_date', 'end_date', 'created_at', 'updated_at'] as $column) {
            if (Schema::hasColumn('tickets', $column)) {
                $columns[] = $column;
            }
        }

        return implode(',', array_unique($columns));
    }

    private function projectRelationColumns(): string
    {
        $columns = ['id', 'title', 'status'];

        foreach (['project_no', 'start_date', 'end_date', 'status_id'] as $column) {
            if (Schema::hasColumn('projects', $column)) {
                $columns[] = $column;
            }
        }

        return implode(',', array_unique($columns));
    }

    private function formatDate($value, string $format, ?string $tz = null): string
    {
        if (empty($value)) {
            return '—';
        }

        try {
            $dt = $value instanceof Carbon ? $value : Carbon::parse($value);
            if ($tz) {
                $dt = $dt->timezone($tz);
            }

            return $dt->format($format);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function formatFileSize($bytes): string
    {
        if (! is_numeric($bytes)) {
            return '—';
        }

        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $exp = (int) floor(log($bytes, 1024));
        $exp = min($exp, count($units) - 1);

        $value = $bytes / (1024 ** $exp);
        $precision = $value >= 100 ? 0 : 1;

        return number_format($value, $precision).' '.$units[$exp];
    }

    private function resolveBackUrl(Request $request, string $fallback): string
    {
        $from = $request->query('from', $request->input('from'));
        if (is_string($from) && $from !== '' && $this->isSafeRedirect($from)) {
            return $this->normalizeInternalUrl($from);
        }

        $referer = $request->headers->get('referer');
        if (is_string($referer) && $referer !== '' && $this->isSafeRedirect($referer)) {
            return $this->normalizeInternalUrl($referer);
        }

        return $this->normalizeInternalUrl($fallback);
    }

    private function pageOrigin(Request $request, string $fallbackRoute): string
    {
        $uri = $request->getRequestUri();
        if (is_string($uri) && $uri !== '') {
            return $uri;
        }

        return $this->normalizeInternalUrl(route($fallbackRoute));
    }

    private function reportOrigin(Request $request): string
    {
        $uri = $request->getRequestUri();
        if (is_string($uri) && $uri !== '') {
            return $uri;
        }

        return $this->normalizeInternalUrl(route('tasks.report'));
    }

    private function isSafeRedirect(string $url): bool
    {
        try {
            $app = rtrim((string) config('app.url'), '/');

            return str_starts_with($url, '/')
                || ($app !== '' && str_starts_with($url, $app));
        } catch (\Throwable) {
            return false;
        }
    }

    private function normalizeInternalUrl(string $url): string
    {
        if (str_starts_with($url, '/')) {
            return $url;
        }

        try {
            $app = rtrim((string) config('app.url'), '/');
            if ($app !== '' && str_starts_with($url, $app)) {
                $relative = substr($url, strlen($app));

                return $relative !== '' ? $relative : '/';
            }
        } catch (\Throwable) {
        }

        return $url;
    }

    // ---- Helpers untuk meta ----

    /** Ambil & normalize meta dari request */
    private function extractMeta(Request $request): array
    {
        $meta = [];

        // PRIORITY
        $priority = $request->input('priority');
        if ($priority !== null && $priority !== '') {
            $meta['priority'] = $priority;
        }

        // Ticket terkait (task berbasis ticket)
        $ticketId = $request->input('ticket_id');
        if ($ticketId !== null && $ticketId !== '') {
            $meta['ticket_id'] = (int) $ticketId;
        }

        // ASSIGNEE (gunakan assignee_id bila ada)
        $assigneeId = $request->input('assignee_id');
        if ($assigneeId !== null && $assigneeId !== '') {
            $meta['assignee_id'] = (int) $assigneeId;
        } else {
            $meta['assignee_id'] = null;
        }

        $assignedRaw = $request->input('assigned_to');
        if (is_array($assignedRaw)) {
            $assignedRaw = json_encode(array_values($assignedRaw));
        } elseif (is_string($assignedRaw)) {
            $assignedRaw = trim($assignedRaw);
            $assignedRaw = $assignedRaw !== '' ? $assignedRaw : null;
        } else {
            $assignedRaw = null;
        }
        $meta['assigned_to'] = $assignedRaw;

        // REQUESTER (update created_by, super admin only)
        $acting = $request->user();
        $requesterId = $request->input('requester_id');
        if ($requesterId !== null && $requesterId !== '') {
            $meta['created_by'] = (int) $requesterId;
        } elseif ($acting) {
            $meta['created_by'] = (int) $acting->id;
        }

        // DUE AT: sudah dikombinasikan sebelumnya di store/update, ambil dari due_at
        $dueAtValue = $request->input('due_at');
        if ($dueAtValue) {
            $meta['due_at'] = $this->parseDueAt($dueAtValue);
        }

        return array_filter($meta, fn ($v) => $v !== null);
    }

    private function extractTimelineMeta(Request $request): array
    {
        $timeline = [];

        if ($request->exists('start_date')) {
            $timeline['start_date'] = $request->input('start_date') ?: null;
        }

        if ($request->exists('end_date')) {
            $timeline['end_date'] = $request->input('end_date') ?: null;
        }

        return $timeline;
    }

    private function normalizeTimelineInputs(Request $request): void
    {
        foreach (['start_date', 'end_date'] as $field) {
            if (! $request->exists($field)) {
                continue;
            }

            $value = $request->input($field);
            if ($value === null || $value === '') {
                $request->merge([$field => null]);

                continue;
            }

            $normalized = $this->normalizeDueDate($value);
            $request->merge([$field => $normalized ?? null]);
        }
    }

    private function normalizeAssigneeInput(Request $request): void
    {
        $raw = $request->input('assignees');
        $ids = [];

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $ids = $decoded;
            }
        } elseif (is_array($raw)) {
            $ids = $raw;
        }

        $ids = array_values(array_unique(array_filter(array_map(function ($value) {
            if (is_int($value)) {
                return $value > 0 ? $value : null;
            }
            if (is_string($value) && trim($value) !== '' && is_numeric($value)) {
                $intVal = (int) $value;

                return $intVal > 0 ? $intVal : null;
            }

            return null;
        }, $ids), fn ($v) => $v !== null)));

        if (! empty($ids)) {
            $request->merge([
                'assignees' => $ids,
                'assignee_id' => $ids[0],
                'assigned_to' => json_encode($ids),
            ]);
        } else {
            $request->merge([
                'assignees' => [],
                'assigned_to' => null,
            ]);
            if (! $request->has('assignee_id')) {
                $request->merge(['assignee_id' => null]);
            }
        }
    }

    /**
     * @return array<int>
     */
    private function collectTaskAssigneeIds(Task $task): array
    {
        $ids = [];

        if ($task->assignee_id) {
            $ids[] = (int) $task->assignee_id;
        }

        $assignedRaw = $task->assigned_to;
        if (is_string($assignedRaw) && $assignedRaw !== '') {
            $decoded = json_decode($assignedRaw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $value) {
                    $intVal = (int) $value;
                    if ($intVal > 0) {
                        $ids[] = $intVal;
                    }
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /** Parse due input menjadi Carbon|null */
    private function parseDueAt(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $formats = ['Y-m-d H:i:s', 'd/m/Y H:i', 'd/m/Y'];
        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value);
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeDueDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        $date = trim($date);
        if ($date === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Combine due_date (dd/mm/YYYY) + due_time (HH:mm) menjadi Carbon|null */
    private function combineDueAt(?string $date, ?string $time): ?Carbon
    {
        if (! $date && ! $time) {
            return null;
        }

        $date = $date ? trim($date) : '';
        $time = $time ? trim($time) : '';

        // Lengkapi time default bila hanya date
        $candidate = trim($date.($time ? (' '.$time) : ' 00:00'));

        // Coba format spesifik
        foreach (['d/m/Y H:i', 'd/m/Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $candidate);
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($candidate);
        } catch (\Throwable) {
            return null;
        }
    }

    private function updateLinkedTicketStatus(?int $ticketId, string $status): void
    {
        if (! $ticketId) {
            return;
        }

        $normalized = WorkflowStatus::normalize($status);

        Ticket::where('id', $ticketId)->update([
            'status' => $normalized,
            'status_id' => WorkflowStatus::code($normalized),
        ]);
    }

    private function statusGuidance(): array
    {
        return [
            'default' => 'Status awal task selalu New.',
            'agent' => 'Status In Progress dan Confirmation hanya dapat diubah oleh assignee atau assigned user terkait.',
            'requester' => 'Status Revision, Done, Cancelled, dan On Hold hanya dapat diubah oleh Requester.',
            'admin' => 'Hanya Super Admin yang dapat mengubah seluruh status.',
        ];
    }

    private function userHasStatusOverride(?User $user): bool
    {
        return RoleHelpers::userIsSuperAdmin($user);
    }

    // Sanitasi HTML dari editor
    private function sanitizeDescription(string $html): string
    {
        $allowed = '<p><br><div><span><strong><b><em><i><u><s><mark>'
            .'<ul><ol><li><blockquote><code><pre><hr>'
            .'<h1><h2><h3><h4>'
            .'<a><table><thead><tbody><tr><th><td><figure><figcaption>';

        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
        $clean = preg_replace_callback('/<a\b[^>]*href\s*=\s*("|\')(.*?)\1[^>]*>/i', function ($m) {
            $url = trim($m[2]);
            if (preg_match('#^(https?://|mailto:|#)#i', $url)) {
                return $m[0];
            }

            return preg_replace('/href\s*=\s*("|\')(.*?)\1/i', 'href="#"', $m[0]);
        }, $clean);

        return $clean;
    }

    /** Daftar users dengan label defensif */
    private function getUsersList()
    {
        $cols = ['id'];
        foreach (['first_name', 'last_name', 'name', 'full_name', 'username', 'email'] as $c) {
            if (Schema::hasColumn('users', $c)) {
                $cols[] = $c;
            }
        }
        foreach (['unit', 'role'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                $cols[] = $col;
            }
        }

        $orderCol = collect(['name', 'full_name', 'first_name', 'username', 'email'])
            ->first(fn ($c) => Schema::hasColumn('users', $c)) ?? 'id';

        return User::query()
            ->select($cols)->orderBy($orderCol)->get()
            ->map(function ($u) {
                $full = trim(implode(' ', array_filter([$u->first_name ?? null, $u->last_name ?? null])));
                $displayName = $full ?: ($u->name ?? $u->full_name ?? $u->username ?? $u->email ?? ('User #'.$u->id));
                $u->label = $displayName;
                $u->display_name = $displayName; // For compatibility with views

                return $u;
            });
    }

    private function filterUsersByUnit($users, ?User $viewer)
    {
        if (! UnitVisibility::requiresRestriction($viewer)) {
            return $users;
        }

        $viewerUnitKey = $this->normalizedUnitKey($viewer?->unit ?? null);
        if (! $viewerUnitKey) {
            return $users;
        }

        return $users
            ->filter(function ($user) use ($viewerUnitKey) {
                $candidateKey = $this->normalizedUnitKey($user->unit ?? null);

                return $candidateKey && $candidateKey === $viewerUnitKey;
            })
            ->values();
    }

    private function mapUserOptions($users): array
    {
        return $users->map(function ($user) {
            $unit = isset($user->unit) ? trim((string) $user->unit) : null;

            return [
                'id' => $user->id,
                'name' => $user->label,
                'label' => $user->label,
                'email' => $user->email ?? null,
                'unit' => $unit !== '' ? $unit : null,
            ];
        })->values()->all();
    }

    private function unitOptionsForUser($users, ?User $viewer): array
    {
        $units = [];
        $seen = [];

        $collector = function (?string $label) use (&$seen, &$units) {
            $value = is_string($label) ? trim($label) : '';
            if ($value === '') {
                return;
            }

            $key = $this->normalizedUnitKey($value);
            if (! $key || isset($seen[$key])) {
                return;
            }

            $seen[$key] = true;
            $units[] = $value;
        };

        foreach (UserUnitOptions::values() as $predefined) {
            $collector($predefined);
        }

        foreach ($users as $user) {
            $collector($user->unit ?? null);
        }

        $collector($viewer?->unit ?? null);

        if (empty($units)) {
            return UserUnitOptions::values();
        }

        natcasesort($units);

        return array_values($units);
    }

    private function linkableTicketsForUser(?User $viewer, ?Ticket $context = null): Collection
    {
        $requesterColumns = $this->requesterSelectColumns();
        $attachmentColumns = ['id', 'attachable_id', 'attachable_type', 'original_name', 'size'];

        $query = Ticket::query()
            ->with([
                'requester:'.implode(',', $requesterColumns),
                'attachments:'.implode(',', $attachmentColumns),
            ])
            ->withCount('attachments')
            ->select(['id', 'ticket_no', 'title', 'status', 'requester_id', 'created_at'])
            ->whereIn('status', $this->linkableTicketStatuses())
            ->latest('id')
            ->limit(200);

        $tickets = $this->filterTicketsByViewerUnit($query->get(), $viewer);

        if ($context) {
            $context->loadMissing([
                'requester:'.implode(',', $requesterColumns),
                'attachments:'.implode(',', $attachmentColumns),
            ]);
            if ($tickets->where('id', $context->id)->isEmpty()) {
                $tickets->push($context);
            }
        }

        return $tickets->values();
    }

    private function prepareTicketOptions(Collection $tickets): array
    {
        $options = $tickets
            ->map(fn (Ticket $ticket) => $this->ticketOptionPayload($ticket))
            ->values();

        $units = $options
            ->pluck('unit')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();

        return [$options->all(), $units];
    }

    private function ticketOptionPayload(Ticket $ticket): array
    {
        $number = $ticket->ticket_no ?? ('Ticket #'.$ticket->id);
        $title = $ticket->title ? Str::limit($ticket->title, 70) : 'Tanpa judul';
        $unit = $ticket->requester?->unit ?? null;
        $status = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::default());
        $attachmentCount = (int) ($ticket->attachments_count
            ?? ($ticket->relationLoaded('attachments') ? $ticket->attachments->count() : 0));
        $attachments = [];

        if ($ticket->relationLoaded('attachments')) {
            $attachments = $ticket->attachments
                ->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'name' => $attachment->original_name ?? 'Lampiran ticket',
                        'size' => $attachment->size,
                        'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                        'download_url' => $this->attachmentRoute('attachments.download', $attachment),
                    ];
                })
                ->values()
                ->all();
        }

        return [
            'id' => $ticket->id,
            'label' => trim($number.' — '.$title),
            'ticket_no' => $number,
            'title' => $ticket->title ?? '',
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'unit' => $unit !== '' ? $unit : null,
            'attachments_count' => $attachmentCount,
            'attachments' => $attachments,
            'requester' => $this->userDisplayName($ticket->requester),
        ];
    }

    private function linkableTicketStatuses(): array
    {
        return [
            WorkflowStatus::NEW,
            WorkflowStatus::IN_PROGRESS,
            WorkflowStatus::CONFIRMATION,
            WorkflowStatus::REVISION,
            WorkflowStatus::ON_HOLD,
        ];
    }

    private function filterTicketsByViewerUnit(Collection $tickets, ?User $viewer): Collection
    {
        if ($this->viewerCanAccessAllUnits($viewer)) {
            return $tickets->values();
        }

        $viewerUnitKey = $this->normalizedUnitKey($viewer?->unit ?? null);
        return $tickets
            ->filter(function (Ticket $ticket) use ($viewerUnitKey, $viewer) {
                $requesterUnit = $ticket->requester?->unit ?? null;
                $unitMatches = $viewerUnitKey && $this->normalizedUnitKey($requesterUnit) === $viewerUnitKey;
                $userInvolved = $this->ticketRelatesToUser($ticket, $viewer);

                return $unitMatches || $userInvolved;
            })
            ->values();
    }

    private function viewerCanAccessAllUnits(?User $viewer): bool
    {
        return RoleHelpers::userIsSuperAdmin($viewer);
    }

    private function requesterSelectColumns(): array
    {
        static $columns;

        if ($columns !== null) {
            return $columns;
        }

        $columns = ['id'];
        foreach (['unit', 'first_name', 'last_name', 'name', 'full_name', 'username', 'email'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    private function userCanSelectRequester(?User $user): bool
    {
        return RoleHelpers::userIsSuperAdmin($user);
    }

    private function ticketRelatesToUser(Ticket $ticket, ?User $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        $userId = (int) ($viewer->id ?? 0);
        if ($userId <= 0) {
            return false;
        }

        if ((int) ($ticket->requester_id ?? 0) === $userId) {
            return true;
        }
        if ((int) ($ticket->agent_id ?? 0) === $userId) {
            return true;
        }
        if ((int) ($ticket->assigned_id ?? 0) === $userId) {
            return true;
        }

        if ($ticket->relationLoaded('assignedUsers')) {
            return $ticket->assignedUsers->contains(fn ($assigned) => (int) $assigned->id === $userId);
        }

        return $ticket->assignedUsers()->where('users.id', $userId)->exists();
    }

    private function userDisplayName($user): string
    {
        if (! $user) {
            return 'User';
        }

        $full = trim(implode(' ', array_filter([$user->first_name ?? null, $user->last_name ?? null])));
        if ($full !== '') {
            return $full;
        }

        foreach (['name', 'full_name', 'username', 'email'] as $attribute) {
            $value = data_get($user, $attribute);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $id = data_get($user, 'id');

        return $id ? 'User #'.$id : 'User';
    }

    private function normalizedUnitKey(?string $unit): ?string
    {
        if (! is_string($unit)) {
            return null;
        }

        $value = trim(Str::ascii($unit));
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\([^)]*\)/', ' ', $value);
        $value = preg_replace('/^UNIT[\s_\-]*/i', '', $value);
        $value = preg_replace('/[^A-Z0-9]+/i', '', $value);
        $value = mb_strtoupper($value, 'UTF-8');

        return $value !== '' ? $value : null;
    }

    private function allowedTaskStatuses(?User $viewer, ?Task $task = null): array
    {
        if (! $viewer) {
            return [];
        }

        if ($this->userHasStatusOverride($viewer)) {
            return WorkflowStatus::all();
        }

        if (! $task) {
            return [WorkflowStatus::NEW];
        }
        $allowed = array_filter(
            WorkflowStatus::all(),
            fn (string $status) => $task->canUserSetStatus($viewer, $status)
        );

        return array_values($allowed);
    }
}
