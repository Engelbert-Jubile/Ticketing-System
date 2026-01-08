<?php

namespace App\Http\Controllers\Main;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\Status;
use App\Http\Controllers\Controller;
use App\Models\ProjectAction;
use App\Models\ProjectCost;
use App\Models\ProjectDeliverable;
use App\Models\ProjectPic;
use App\Models\ProjectRiskAnalysis;
use App\Models\ProjectSubAction;
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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(
        private AttachmentService $attachments,
        private ReportExportService $reportExport
    ) {}

    /** Semua project */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('projects.report');
    }

    /** Project in_progress */
    public function onProgress(Request $request): Response
    {
        $statusScope = array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS),
            WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION)
        ));

        $perPage = (int) $request->integer('per_page', 12);
        $perPage = min(max($perPage, 6), 200);

        $actor = $request->user();

        $projectsQuery = Project::query()
            ->select(['id', 'title', 'status', 'status_id', 'project_no', 'end_date', 'updated_at'])
            ->with(['ticket:id,ticket_no,status'])
            ->whereIn('status', $statusScope);

        $projectsQuery = UnitVisibility::scopeProjects($projectsQuery, $actor);

        $projects = $projectsQuery
            ->orderByRaw("(SUBSTRING_INDEX(title,' ', -1) REGEXP '^[0-9]+$') DESC")
            ->orderByRaw("CAST(SUBSTRING_INDEX(title,' ', -1) AS UNSIGNED) ASC")
            ->orderBy('title')
            ->paginate($perPage)
            ->appends(['per_page' => $perPage])
            ->through(fn (Project $project) => $this->transformProjectSummary($project));

        return Inertia::render('Projects/OnProgress', [
            'projects' => $projects,
            'filters' => [
                'per_page' => $perPage,
            ],
        ]);
    }

    /** Report - Tampilkan project dengan pembedaan dari ticket vs mandiri */
    public function report(Request $request): Response
    {
        $rawStatus = $request->query('status');
        $normalizedStatus = $rawStatus ? WorkflowStatus::normalize($rawStatus) : null;

        $formFilters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $rawStatus ? (string) $rawStatus : '',
            'from' => $request->query('from', ''),
            'to' => $request->query('to', ''),
        ];

        $fromDate = $this->parseReportDate($formFilters['from'], true);
        $toDate = $this->parseReportDate($formFilters['to'], false);

        $queryFilters = [
            'q' => $formFilters['q'],
            'status' => $normalizedStatus,
            'from' => $fromDate,
            'to' => $toDate,
        ];

        $actor = $request->user();

        $ticketSummary = $this->projectReportSummary($queryFilters, true, $actor);
        $standaloneSummary = $this->projectReportSummary($queryFilters, false, $actor);

        $statusOptions = collect(WorkflowStatus::all())
            ->map(fn (string $status) => [
                'value' => $status,
                'label' => WorkflowStatus::label($status),
                'badge' => WorkflowStatus::badgeClass($status),
            ])->values();

        $projectsWithTicket = $this->projectReportPaginator($queryFilters, true, $request, $actor);
        $projectsStandalone = $this->projectReportPaginator($queryFilters, false, $request, $actor);

        if ($request->wantsJson()) {
            return Inertia::render('Projects/Report', [
                'filters' => [
                    'q' => $formFilters['q'],
                    'status' => $formFilters['status'],
                    'from' => $formFilters['from'],
                    'to' => $formFilters['to'],
                ],
                'statusOptions' => $statusOptions,
                'ticketSummary' => $ticketSummary,
                'standaloneSummary' => $standaloneSummary,
                'ticketProjects' => $projectsWithTicket,
                'standaloneProjects' => $projectsStandalone,
            ]);
        }

        if ($request->inertia()) {
            return Inertia::render('Projects/Report', [
                'filters' => [
                    'q' => $formFilters['q'],
                    'status' => $formFilters['status'],
                    'from' => $formFilters['from'],
                    'to' => $formFilters['to'],
                ],
                'statusOptions' => $statusOptions,
                'ticketSummary' => $ticketSummary,
                'standaloneSummary' => $standaloneSummary,
                'ticketProjects' => $projectsWithTicket,
                'standaloneProjects' => $projectsStandalone,
            ]);
        }

        return Inertia::render('Projects/Report', [
            'filters' => [
                'q' => $formFilters['q'],
                'status' => $formFilters['status'],
                'from' => $formFilters['from'],
                'to' => $formFilters['to'],
            ],
            'statusOptions' => $statusOptions,
            'ticketSummary' => $ticketSummary,
            'standaloneSummary' => $standaloneSummary,
            'ticketProjects' => $projectsWithTicket,
            'standaloneProjects' => $projectsStandalone,
        ]);
    }

    public function downloadReport(Request $request)
    {
        $rawStatus = $request->query('status');
        $normalizedStatus = $rawStatus ? WorkflowStatus::normalize($rawStatus) : null;
        if ($normalizedStatus && ! in_array($normalizedStatus, WorkflowStatus::all(), true)) {
            $normalizedStatus = null;
        }

        $formFilters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $rawStatus ? (string) $rawStatus : '',
            'from' => $request->query('from', ''),
            'to' => $request->query('to', ''),
        ];

        $fromDate = $this->parseReportDate($formFilters['from'], true);
        $toDate = $this->parseReportDate($formFilters['to'], false);

        $queryFilters = [
            'q' => $formFilters['q'],
            'status' => $normalizedStatus,
            'from' => $fromDate,
            'to' => $toDate,
        ];
        $actor = $request->user();

        $ticketProjects = $this->buildProjectReportQuery($queryFilters, true, true, $actor)->get();
        $standaloneProjects = $this->buildProjectReportQuery($queryFilters, false, true, $actor)->get();

        $tz = config('app.timezone');
        $rows = $ticketProjects
            ->map(fn (Project $project) => $this->mapProjectForPdf($project, true, $tz))
            ->merge($standaloneProjects->map(fn (Project $project) => $this->mapProjectForPdf($project, false, $tz)))
            ->values()
            ->toArray();

        $columns = [
            ['label' => 'Project No'],
            ['label' => 'Judul'],
            ['label' => 'Status'],
            ['label' => 'Kategori'],
            ['label' => 'Ticket No'],
            ['label' => 'Mulai'],
            ['label' => 'Selesai'],
            ['label' => 'Diperbarui'],
        ];

        $meta = [
            'filters' => [
                'Pencarian' => $formFilters['q'] !== '' ? $formFilters['q'] : 'Semua',
                'Status' => $normalizedStatus ? WorkflowStatus::label($normalizedStatus) : 'Semua',
                'Rentang' => $this->formatDateRange($fromDate, $toDate),
                'Total Data' => number_format(count($rows)),
            ],
        ];

        $filename = sprintf('projects-report-%s.pdf', now()->format('Ymd-His'));

        return $this->reportExport->downloadPdf('Laporan Project', $columns, $rows, $meta, $filename);
    }

    public function downloadDetail(Request $request, Project $project)
    {
        UnitVisibility::ensureProjectAccess($request->user(), $project);
        $project->load([
            'ticket:id,ticket_no,title,status,due_at,due_date,assignee_id,requester_id',
            'ticket.assignee:id,first_name,last_name,username,email',
            'ticket.requester:id,first_name,last_name,username,email',
            'ticket.assignedUsers:id,first_name,last_name,username,email',
            'ticket.attachments:id,attachable_id,attachable_type,original_name,size',
            'pics.user:id,first_name,last_name,username,email',
            'actions.subactions',
            'costs',
            'risks',
            'deliverables',
            'attachments:id,attachable_id,attachable_type,original_name,size',
        ]);

        $detail = $this->transformProjectDetail($project);

        $description = strip_tags((string) ($detail['description'] ?? ''), '<p><br><strong><em><ul><ol><li><b><i><u>');
        if (trim(strip_tags($description)) === '') {
            $description = '<p>Tidak ada deskripsi.</p>';
        }

        $ticket = $detail['ticket'] ?? null;
        if ($ticket) {
            $status = $ticket['status'] ?? null;
            $ticket['status_label'] = $status ? WorkflowStatus::label($status) : null;
        }

        $actions = collect($detail['actions'] ?? [])->map(function (array $action) {
            return [
                'title' => $action['title'] ?? '—',
                'status_id' => $action['status_id'] ?? null,
                'progress' => $action['progress'] ?? null,
                'start' => $action['start'] ?? '—',
                'end' => $action['end'] ?? '—',
                'subactions' => collect($action['subactions'] ?? [])->map(fn (array $sub) => [
                    'title' => $sub['title'] ?? '—',
                    'status_id' => $sub['status_id'] ?? null,
                    'progress' => $sub['progress'] ?? null,
                    'start' => $sub['start'] ?? '—',
                    'end' => $sub['end'] ?? '—',
                ])->values()->all(),
            ];
        })->values()->all();

        $costs = collect($detail['costs'] ?? [])->map(function (array $cost) {
            return [
                'item' => $cost['item'] ?? '—',
                'category' => $cost['category'] ?? '—',
                'estimated' => $this->formatCurrency($cost['estimated'] ?? null),
                'actual' => $this->formatCurrency($cost['actual'] ?? null),
            ];
        })->values()->all();

        $deliverables = collect($detail['deliverables'] ?? [])->map(function (array $deliverable) {
            return [
                'title' => $deliverable['title'] ?? '—',
                'status_id' => $deliverable['status_id'] ?? null,
                'due' => $deliverable['due'] ?? '—',
                'description' => $deliverable['description'] ?? null,
            ];
        })->values()->all();

        $attachments = collect($detail['attachments'] ?? [])->map(function (array $attachment) {
            return [
                'name' => $attachment['name'] ?? '—',
                'size' => $this->formatFileSize($attachment['size'] ?? null),
            ];
        })->values()->all();

        $filename = sprintf(
            'project-%s-detail.pdf',
            $project->project_no ? Str::slug($project->project_no) : $project->id
        );

        return $this->reportExport->downloadDetailPdf('reports.pdf.project-detail', [
            'title' => $detail['title'] ?? 'Detail Project',
            'project' => $detail,
            'description' => $description,
            'ticket' => $ticket,
            'timeline' => $detail['timeline'] ?? [],
            'pics' => $detail['pics'] ?? [],
            'actions' => $actions,
            'costs' => $costs,
            'risks' => $detail['risks'] ?? [],
            'deliverables' => $deliverables,
            'attachments' => $attachments,
        ], $filename);
    }

    public function show(Request $request, string $locale, Project|string $project): Response
    {
        $project = $project instanceof Project
            ? $project
            : Project::where('public_slug', $project)->orWhere('id', $project)->firstOrFail();

        return $this->renderProjectDetailPage($request, $project);
    }

    public function showLegacy(Request $request, string $locale, Project $project): Response
    {
        return redirect()->route('projects.show', ['project' => $project->public_slug]);
    }

    private function renderProjectDetailPage(Request $request, Project $project): Response
    {
        UnitVisibility::ensureProjectAccess($request->user(), $project);
        $project->load([
            'ticket:id,ticket_no,title,due_at,due_date,status,agent_id,assigned_id',
            'ticket.attachments:id,attachable_id,attachable_type,original_name,size,path',
            'ticket.requester:id,first_name,last_name,username,email',
            'ticket.assignee:id,first_name,last_name,username,email',
            'ticket.assignedUsers:id,first_name,last_name,username,email',
            'pics.user:id,first_name,last_name,username,email',
            'actions.subactions',
            'costs',
            'risks',
            'deliverables',
            'attachments',
        ]);

        $detail = $this->transformProjectDetail($project);

        return Inertia::render('Projects/Show', [
            'project' => $detail,
            'meta' => [
                'backUrl' => $this->resolveBackUrl($request, route('projects.report')),
            ],
        ]);
    }

    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ CREATE & STORE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */

    public function create(Request $request): Response
    {
        $viewer = $request->user();
        $ticketCollection = $this->linkableTicketsForUser($viewer);
        [$ticketOptions, $ticketUnits] = $this->prepareTicketOptions($ticketCollection);

        $users = $this->getUserOptions();
        $unitOptions = $this->unitOptionsForUser($users, $viewer);
        $statuses = $this->orderedStatuses();

        if ($statuses->isEmpty()) {
            Status::ensureDefaults();
            $statuses = $this->orderedStatuses();
        }

        $impactOptions = ['low', 'medium', 'high'];
        $likelihoodOptions = ['rare', 'possible', 'likely', 'almost_certain'];
        $verifiedByOptions = ['identified', 'monitored', 'mitigated', 'occurred'];

        $defaultStatus = WorkflowStatus::normalize(WorkflowStatus::default());
        $defaultStatusId = WorkflowStatus::code($defaultStatus);

        $projectPayload = [
            'title' => '',
            'project_no' => null,
            'status' => $defaultStatus,
            'status_label' => WorkflowStatus::label($defaultStatus),
            'status_id' => $defaultStatusId,
            'ticket_id' => null,
            'description' => null,
            'start_date' => null,
            'end_date' => null,
            'public_slug' => null,
            'agent_id' => null,
            'assigned_id' => null,
            'pics' => [],
            'actions' => [],
            'costs' => [],
            'risks' => [],
            'deliverables' => [],
            'attachments' => [],
        ];

        $allowedStatuses = [$defaultStatus];
        $statusLock = true;
        if (RoleHelpers::userIsSuperAdmin($viewer)) {
            $allowedStatuses = WorkflowStatus::all();
            $statusLock = false;
        }

        return Inertia::render('Projects/Create', [
            'project' => $projectPayload,
            'options' => [
                'tickets' => $ticketOptions,
                'users' => $users->map(fn ($user) => [
                    'id' => $user->id,
                    'label' => $user->label,
                    'unit' => $user->unit ?? null,
                    'email' => $user->email ?? null,
                    'username' => $user->username ?? null,
                ])->values()->all(),
                'statuses' => $statuses->map(fn ($statusModel) => [
                    'id' => $statusModel->id,
                    'name' => $statusModel->name ?? $statusModel->id,
                ])->values()->all(),
                'workflow_statuses' => collect(WorkflowStatus::labels())
                    ->map(fn ($label, $slug) => [
                        'id' => (string) $slug,
                        'name' => $label,
                    ])->values()->all(),
                'impact' => array_values($impactOptions),
                'likelihood' => array_values($likelihoodOptions),
                'verified_by' => array_values($verifiedByOptions),
            ],
            'meta' => [
                'mode' => 'create',
                'backUrl' => $this->resolveBackUrl($request, route('projects.report')),
                'canManageTicket' => $this->userCanManageTicket($viewer),
                'submitUrl' => route('projects.store'),
                'submitMethod' => 'post',
                'statusGuide' => $this->statusGuidance(),
                'lockStatus' => $statusLock,
                'allowedStatuses' => $allowedStatuses,
                'unitOptions' => $unitOptions,
                'ticketUnits' => $ticketUnits,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workflowStatuses = WorkflowStatus::all();
        $statusIds = Status::pluck('id')->all();

        $validated = $request->validate($this->projectFormRules($workflowStatuses, $statusIds));

        $this->ensurePicPresence($validated['project_pics'] ?? []);
        $team = $this->resolveProjectTeam($validated['project_pics'] ?? []);
        $validated['project_pics'] = $team['entries'];

        if (! empty($validated['description'])) {
            $validated['description'] = $this->sanitizeDescription($validated['description']);
        }

        $viewer = $request->user();

        $workflowStatus = WorkflowStatus::normalize($validated['status']);
        if (! RoleHelpers::userIsSuperAdmin($viewer)) {
            $workflowStatus = WorkflowStatus::default();
        }
        $planningRows = [];
        $hasPlanningColumn = Schema::hasColumn((new Project)->getTable(), 'planning');
        if ($hasPlanningColumn) {
            $planningPayload = $request->input('planning', $request->input('planning_json'));
            $planningRows = $this->normalizePlanning($planningPayload);
            if (RoleHelpers::userIsSuperAdmin($viewer) && ! empty($planningRows)) {
                $workflowStatus = $this->statusFromPlanning($planningRows, $workflowStatus);
            }
        }

        $projectStatusId = WorkflowStatus::code($workflowStatus);

        $ticketId = $request->integer('ticket_id') ?: null;

        $projectData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $workflowStatus,
            'status_id' => $projectStatusId,
            'project_no' => $validated['project_no'] ?: $this->generateProjectNo(),
            'ticket_id' => $ticketId,
            'start_date' => $this->parseDate($validated['start_date'] ?? data_get($validated, 'timeline.start')),
            'end_date' => $this->parseDate($validated['end_date'] ?? data_get($validated, 'timeline.end')),
            'created_by' => auth()->id(),
            'agent_id' => $team['agent_id'],
            'assigned_id' => $team['assigned_id'],
        ];

        $canPickRequester = $this->userCanSelectRequester($viewer);
        $projectData['requester_id'] = $canPickRequester
            ? ($validated['requester_id'] ?? $viewer?->id)
            : ($viewer?->id);

        if ($hasPlanningColumn) {
            $projectData['planning'] = ! empty($planningRows) ? $planningRows : null;
        }

        $project = DB::transaction(function () use ($projectData, $validated, $projectStatusId) {
            $project = Project::create($projectData);
            $this->syncProjectRelations($project, $validated, $projectStatusId);

            return $project;
        });

        $this->syncTicketStatusFromProject($project);
        $this->attachments->adoptFromServerIds($request->input('attachments'), $project);

        app(WorkItemNotifier::class)->notifyProjectCreated($project, Auth::user());

        return redirect()->route('projects.create')
            ->with('success', 'Project berhasil dibuat.');
    }

    public function edit(Request $request, string $locale, Project|string $project): Response
    {
        $project = $project instanceof Project ? $project : Project::where('public_slug', $project)->firstOrFail();

        UnitVisibility::ensureProjectAccess($request->user(), $project);
        $project->load([
            'ticket:id,ticket_no,title,status,due_at,due_date,agent_id,assigned_id,requester_id',
            'ticket.agent:id,first_name,last_name,username,email,unit',
            'ticket.assignee:id,first_name,last_name,username,email,unit',
            'ticket.assignedUsers:id,first_name,last_name,username,email,unit',
            'ticket.requester:id,first_name,last_name,username,email,unit',
            'ticket.attachments:id,attachable_id,attachable_type,original_name,size,path',
            'pics.user:id,first_name,last_name,username,email',
            'actions',
            'costs',
            'risks',
            'deliverables',
            'attachments:id,attachable_id,attachable_type,original_name,size,path',
        ]);

        $viewer = $request->user();
        $ticketCollection = $this->linkableTicketsForUser($viewer, $project->ticket);
        [$ticketOptions, $ticketUnits] = $this->prepareTicketOptions($ticketCollection);
        $users = $this->getUserOptions();
        $unitOptions = $this->unitOptionsForUser($users, $viewer);
        $statuses = $this->orderedStatuses();

        if ($statuses->isEmpty()) {
            Status::ensureDefaults();
            $statuses = $this->orderedStatuses();
        }

        $impactOptions = ['low', 'medium', 'high'];
        $likelihoodOptions = ['rare', 'possible', 'likely', 'almost_certain'];
        $verifiedByOptions = ['identified', 'monitored', 'mitigated', 'occurred'];

        $canManageTicket = $this->userCanManageTicket($viewer);
        $allowedStatuses = $this->allowedProjectStatuses($viewer, $project);
        $statusLock = empty($allowedStatuses);
        $tz = config('app.timezone');
        $status = $this->normalizeStatus($project->status ?? null);

        $projectPayload = [
            'id' => $project->id,
            'title' => $project->title,
            'project_no' => $project->project_no,
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'status_id' => $project->status_id,
            'status_badge' => WorkflowStatus::badgeClass($status),
            'ticket_id' => $project->ticket_id,
            'description' => $project->description,
            'start_date' => $project->start_date ? $this->formatDate($project->start_date, 'd/m/Y', $tz) : null,
            'end_date' => $project->end_date ? $this->formatDate($project->end_date, 'd/m/Y', $tz) : null,
            'public_slug' => $project->public_slug,
            'agent_id' => $project->agent_id,
            'assigned_id' => $project->assigned_id,
            'ticket' => $project->ticket ? [
                'id' => $project->ticket->id,
                'ticket_no' => $project->ticket->ticket_no,
                'title' => $project->ticket->title,
                'status' => $this->normalizeStatus($project->ticket->status ?? null),
                'status_label' => WorkflowStatus::label($this->normalizeStatus($project->ticket->status ?? null)),
                'due_at' => $this->formatDate($project->ticket->due_at ?? $project->ticket->due_date, 'd/m/Y H:i', $tz),
                'agent_id' => $project->ticket->agent_id,
                'assigned_id' => $project->ticket->assigned_id,
                'assigned_ids' => $project->ticket->assignedUsers->pluck('id')->all(),
                'requester' => $project->ticket->requester ? [
                    'id' => $project->ticket->requester->id,
                    'name' => $this->userDisplayName($project->ticket->requester),
                    'email' => $project->ticket->requester->email,
                ] : null,
                'attachments' => $this->mapTicketAttachments($project->ticket),
            ] : null,
            'ticket_attachments' => $this->mapTicketAttachments($project->ticket),
            'ticket_attachment_count' => $project->ticket
                ? ($project->ticket->attachments_count ?? $project->ticket->attachments?->count() ?? 0)
                : 0,
            'pics' => $project->pics->map(function (ProjectPic $pic) {
                $fullName = trim(implode(' ', array_filter([
                    $pic->user?->first_name,
                    $pic->user?->last_name,
                ])));

                return [
                    'id' => $pic->id,
                    'user_id' => $pic->user_id,
                    'position' => $pic->position,
                    'user_label' => $pic->user?->name
                        ?? ($fullName !== '' ? $fullName : null)
                        ?? $pic->user?->username
                        ?? $pic->user?->email
                        ?? 'User #'.$pic->user_id,
                    'role_type' => $pic->role_type ?? 'pic',
                    'is_primary' => (bool) $pic->is_primary,
                ];
            })->values()->all(),
            'actions' => $project->actions->map(fn (ProjectAction $action) => [
                'id' => $action->id,
                'title' => $action->title,
                'status_id' => $action->status_id,
                'progress' => $action->progress,
                'start_date' => $action->start_date ? $this->formatDate($action->start_date, 'd/m/Y', $tz) : null,
                'end_date' => $action->end_date ? $this->formatDate($action->end_date, 'd/m/Y', $tz) : null,
                'description' => $action->description,
            ])->values()->all(),
            'costs' => $project->costs->map(fn (ProjectCost $cost) => [
                'id' => $cost->id,
                'cost_item' => $cost->cost_item,
                'category' => $cost->category,
                'estimated_cost' => $cost->estimated_cost,
                'actual_cost' => $cost->actual_cost,
                'notes' => $cost->notes,
            ])->values()->all(),
            'risks' => $project->risks->map(fn (ProjectRiskAnalysis $risk) => [
                'id' => $risk->id,
                'name' => $risk->name,
                'status_id' => $risk->status_id,
                'impact' => $risk->impact,
                'likelihood' => $risk->likelihood,
                'description' => $risk->description,
                'mitigation_plan' => $risk->mitigation_plan,
            ])->values()->all(),
            'deliverables' => $project->deliverables->map(fn (ProjectDeliverable $deliverable) => [
                'id' => $deliverable->id,
                'name' => $deliverable->name,
                'status_id' => $deliverable->status_id,
                'verified_by' => $deliverable->verified_by,
                'completed_at' => $deliverable->completed_at ? $this->formatDate($deliverable->completed_at, 'd/m/Y H:i', $tz) : null,
                'verified_at' => $deliverable->verified_at ? $this->formatDate($deliverable->verified_at, 'd/m/Y H:i', $tz) : null,
                'description' => $deliverable->description,
            ])->values()->all(),
            'attachments' => $project->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
            ])->values()->all(),
        ];

        $backUrl = $this->resolveBackUrl($request, route('projects.report'));

        return Inertia::render('Projects/Edit', [
            'project' => $projectPayload,
            'options' => [
                'tickets' => $ticketOptions,
                'users' => $users->map(fn ($user) => [
                    'id' => $user->id,
                    'label' => $user->label,
                    'unit' => $user->unit ?? null,
                    'email' => $user->email ?? null,
                    'username' => $user->username ?? null,
                ])->values()->all(),
                'statuses' => $statuses->map(fn ($statusModel) => [
                    'id' => $statusModel->id,
                    'name' => $statusModel->name ?? $statusModel->id,
                ])->values()->all(),
                'workflow_statuses' => collect(WorkflowStatus::labels())
                    ->map(fn ($label, $slug) => [
                        'id' => (string) $slug,
                        'name' => $label,
                    ])->values()->all(),
                'impact' => array_values($impactOptions),
                'likelihood' => array_values($likelihoodOptions),
                'verified_by' => array_values($verifiedByOptions),
            ],
            'meta' => [
                'mode' => 'edit',
                'backUrl' => $backUrl,
                'canManageTicket' => $canManageTicket,
                'submitUrl' => route('projects.update', $project->id),
                'submitMethod' => 'put',
                'statusGuide' => $this->statusGuidance(),
                'lockStatus' => $statusLock,
                'allowedStatuses' => $allowedStatuses,
                'unitOptions' => $unitOptions,
                'ticketUnits' => $ticketUnits,
            ],
        ]);
    }

    /** Tickets yang bisa dilink-kan ke project (mirip create task) */
    private function linkableTicketsForUser(?User $viewer, ?Ticket $context = null): Collection
    {
        $requesterColumns = $this->requesterSelectColumns();
        $attachmentColumns = ['id', 'attachable_id', 'attachable_type', 'original_name', 'size', 'path'];

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
        $status = $this->normalizeStatus($ticket->status ?? null);
        $unit = $ticket->requester?->unit ?? null;
        $label = trim(($ticket->ticket_no ?? ('Ticket #'.$ticket->id)).' — '.($ticket->title ?? ''));
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
            'ticket_no' => $ticket->ticket_no,
            'title' => $ticket->title,
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'status_badge' => WorkflowStatus::badgeClass($status),
            'unit' => $unit,
            'requester' => $this->userDisplayName($ticket->requester ?? null),
            'label' => $label,
            'attachments_count' => $attachmentCount,
            'attachments' => $attachments,
        ];
    }

    private function filterTicketsByViewerUnit(Collection $tickets, ?User $viewer): Collection
    {
        // Tampilkan semua tiket agar pilihan unit lengkap seperti di create task
        return $tickets->values();
    }

    private function ticketRelatesToUser(Ticket $ticket, ?User $viewer): bool
    {
        if (! $viewer) {
            return false;
        }
        return (int) $ticket->requester_id === (int) $viewer->id;
    }

    private function viewerCanAccessAllUnits(?User $viewer): bool
    {
        return RoleHelpers::userIsSuperAdmin($viewer);
    }

    private function requesterSelectColumns(): array
    {
        return ['id', 'first_name', 'last_name', 'username', 'email', 'unit'];
    }

    public function update(Request $request, string $locale, Project|string $project): RedirectResponse
    {
        $project = $project instanceof Project
            ? $project
            : Project::where('public_slug', $project)->orWhere('id', $project)->firstOrFail();

        UnitVisibility::ensureProjectAccess($request->user(), $project);
        $workflowStatuses = WorkflowStatus::all();
        $statusIds = Status::pluck('id')->all();

        $validated = $request->validate($this->projectFormRules($workflowStatuses, $statusIds, true));

        $this->ensurePicPresence($validated['project_pics'] ?? []);
        $team = $this->resolveProjectTeam($validated['project_pics'] ?? []);
        $validated['project_pics'] = $team['entries'];

        if (! empty($validated['description'])) {
            $validated['description'] = $this->sanitizeDescription($validated['description']);
        }

        $planningPayload = $request->input('planning', $request->input('planning_json'));
        $planningProvided = $request->has('planning') || $request->has('planning_json');
        $hasPlanningColumn = Schema::hasColumn($project->getTable(), 'planning');
        $planningRows = [];

        $workflowStatus = WorkflowStatus::normalize($validated['status']);

        $ticketId = $request->integer('ticket_id');
        $ticketId = $ticketId > 0 ? $ticketId : null;

        if ($planningProvided && $hasPlanningColumn) {
            $planningRows = $this->normalizePlanning($planningPayload);
            if (! empty($planningRows)) {
                $workflowStatus = $this->statusFromPlanning($planningRows, $workflowStatus);
            }
        }

        $projectStatusId = WorkflowStatus::code($workflowStatus);

        $currentStatus = WorkflowStatus::normalize($project->status ?? null);
        if ($workflowStatus !== $currentStatus && ! $project->canUserSetStatus($request->user(), $workflowStatus)) {
            return back()->withInput()->with('error', 'Anda tidak memiliki izin untuk mengubah status project ini.');
        }

        $startRaw = $validated['start_date'] ?? data_get($validated, 'timeline.start');
        $endRaw = $validated['end_date'] ?? data_get($validated, 'timeline.end');

        $projectData = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $workflowStatus,
            'status_id' => $projectStatusId,
            'project_no' => $validated['project_no'],
            'ticket_id' => $ticketId ?? $project->ticket_id,
            'start_date' => $this->parseDate($startRaw),
            'end_date' => $this->parseDate($endRaw),
            'agent_id' => $team['agent_id'],
            'assigned_id' => $team['assigned_id'],
        ];

        if ($this->userCanSelectRequester($request->user())) {
            $projectData['requester_id'] = $validated['requester_id'] ?? $project->requester_id;
        }

        if ($planningProvided && $hasPlanningColumn) {
            $projectData['planning'] = ! empty($planningRows) ? $planningRows : null;
        }

        DB::transaction(function () use ($project, $projectData, $validated, $projectStatusId) {
            $project->update($projectData);
            $this->syncProjectRelations($project, $validated, $projectStatusId);
        });

        $project->refresh();
        $this->syncTicketStatusFromProject($project);
        $this->attachments->adoptFromServerIds($request->input('attachments'), $project);

        if ($currentStatus !== WorkflowStatus::CANCELLED && $workflowStatus === WorkflowStatus::CANCELLED) {
            app(WorkItemNotifier::class)->notifyProjectCancelled($project, $request->user());
        }

        return $this->redirectBackTo($request, 'projects.on-progress')
            ->with('success', 'Project berhasil diperbarui.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        UnitVisibility::ensureProjectAccess($request->user(), $project);
        $ticketId = $project->ticket_id;

        DB::transaction(function () use ($project) {
            $project->delete();
            // (opsional) di sini bisa panggil logika recompute status ticket kalau diinginkan
        });

        return $this->redirectBackTo($request, 'projects.on-progress')
            ->with('success', 'Project berhasil dihapus.');
    }

    /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */

    private function orderedStatuses()
    {
        $codeOrder = array_values(WorkflowStatus::codes());
        if (empty($codeOrder)) {
            return Status::query()->orderBy('name')->get(['id', 'name']);
        }

        $placeholders = implode(', ', array_fill(0, count($codeOrder), '?'));

        return Status::query()
            ->select(['id', 'name'])
            ->orderByRaw('FIELD(id, '.$placeholders.')', $codeOrder)
            ->orderBy('name')
            ->get();
    }

    private function projectFormRules(array $workflowStatuses, array $statusIds, bool $requireProjectNo = false): array
    {
        $projectNoRule = $requireProjectNo
            ? ['required', 'string', 'max:20']
            : ['nullable', 'string', 'max:20'];

        $impactOptions = ['low', 'medium', 'high'];
        $likelihoodOptions = ['rare', 'possible', 'likely', 'almost_certain'];
        $verifiedByOptions = ['identified', 'monitored', 'mitigated', 'occurred'];

        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in($workflowStatuses)],
            'project_no' => $projectNoRule,
            'ticket_id' => ['nullable', 'integer', 'exists:tickets,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'requester_id' => ['nullable', 'integer', 'exists:users,id'],
            'planning' => ['nullable'],
            'planning_json' => ['nullable', 'string'],
            'timeline' => ['nullable', 'array'],
            'timeline.start' => ['nullable', 'string'],
            'timeline.end' => ['nullable', 'string'],

            'project_pics' => ['array', 'min:1'],
            'project_pics.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'project_pics.*.position' => ['required', 'string', 'max:30'],
            'project_pics.*.role_type' => ['nullable', 'string', Rule::in(['agent', 'pic'])],
            'project_pics.*.is_primary' => ['nullable', 'boolean'],

            'project_actions' => ['nullable', 'array'],
            'project_actions.*.title' => ['required_with:project_actions', 'string', 'max:60'],
            'project_actions.*.description' => ['nullable', 'string'],
            'project_actions.*.status_id' => ['nullable', 'string', Rule::in($statusIds)],
            'project_actions.*.progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'project_actions.*.pic_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'project_actions.*.start_date' => ['nullable', 'date'],
            'project_actions.*.end_date' => ['nullable', 'date'],
            'project_actions.*.subactions' => ['nullable', 'array'],
            'project_actions.*.subactions.*.title' => ['required_with:project_actions.*.subactions', 'string', 'max:60'],
            'project_actions.*.subactions.*.description' => ['nullable', 'string'],
            'project_actions.*.subactions.*.status_id' => ['nullable', 'string', Rule::in($statusIds)],
            'project_actions.*.subactions.*.progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'project_actions.*.subactions.*.pic_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'project_actions.*.subactions.*.start_date' => ['nullable', 'date'],
            'project_actions.*.subactions.*.end_date' => ['nullable', 'date'],

            'project_costs' => ['nullable', 'array'],
            'project_costs.*.cost_item' => ['required_with:project_costs', 'string', 'max:60'],
            'project_costs.*.category' => ['required_with:project_costs', 'string', 'max:60'],
            'project_costs.*.estimated_cost' => ['required_with:project_costs', 'numeric', 'min:0', 'max:999999999999.99'],
            'project_costs.*.actual_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'project_costs.*.notes' => ['nullable', 'string'],

            'project_risks' => ['nullable', 'array'],
            'project_risks.*.name' => ['required_with:project_risks', 'string', 'max:60'],
            'project_risks.*.description' => ['nullable', 'string'],
            'project_risks.*.impact' => ['required_with:project_risks', Rule::in($impactOptions)],
            'project_risks.*.likelihood' => ['required_with:project_risks', Rule::in($likelihoodOptions)],
            'project_risks.*.mitigation_plan' => ['nullable', 'string'],
            'project_risks.*.status_id' => ['required_with:project_risks', 'string', Rule::in($statusIds)],

            'project_deliverables' => ['nullable', 'array'],
            'project_deliverables.*.name' => ['required_with:project_deliverables', 'string', 'max:60'],
            'project_deliverables.*.description' => ['nullable', 'string'],
            'project_deliverables.*.status_id' => ['required_with:project_deliverables', 'string', Rule::in($statusIds)],
            'project_deliverables.*.completed_at' => ['nullable', 'date'],
            'project_deliverables.*.verified_at' => ['nullable', 'date'],
            'project_deliverables.*.verified_by' => ['nullable', Rule::in($verifiedByOptions)],

            'attachments' => ['nullable', 'array'],
        ];
    }

    private function syncProjectRelations(Project $project, array $validated, string $projectStatusId): void
    {
        $picMap = $project->pics()
            ->where('role_type', 'pic')
            ->pluck('id', 'user_id')
            ->mapWithKeys(fn ($id, $userId) => [(string) $userId => $id])
            ->all();

        if (array_key_exists('project_pics', $validated)) {
            $project->pics()->delete();
            $picMap = [];

            foreach ($validated['project_pics'] ?? [] as $picData) {
                if (empty($picData['user_id']) || empty($picData['position'])) {
                    continue;
                }

                $pic = ProjectPic::create([
                    'project_id' => $project->id,
                    'user_id' => $picData['user_id'],
                    'position' => $picData['position'],
                    'role_type' => $picData['role_type'] ?? 'pic',
                    'is_primary' => (bool) ($picData['is_primary'] ?? false),
                ]);

                if (($picData['role_type'] ?? 'pic') === 'pic') {
                    $picMap[(string) $picData['user_id']] = $pic->id;
                }
            }
        }

        if (array_key_exists('project_actions', $validated)) {
            $actionIds = $project->actions()->pluck('id');

            if ($actionIds->isNotEmpty()) {
                ProjectSubAction::whereIn('action_id', $actionIds)->delete();
                ProjectAction::whereIn('id', $actionIds)->delete();
            }

            foreach ($validated['project_actions'] ?? [] as $actionData) {
                if (empty($actionData['title'])) {
                    continue;
                }

                $picId = null;
                if (! empty($actionData['pic_user_id']) && isset($picMap[(string) $actionData['pic_user_id']])) {
                    $picId = $picMap[(string) $actionData['pic_user_id']];
                }

                $actionStatus = $actionData['status_id'] ?? $projectStatusId;

                $action = ProjectAction::create([
                    'project_id' => $project->id,
                    'title' => $actionData['title'],
                    'description' => $actionData['description'] ?? null,
                    'status_id' => $actionStatus,
                    'progress' => $actionData['progress'] ?? 0,
                    'pic_id' => $picId,
                    'start_date' => $this->parseDate($actionData['start_date'] ?? null),
                    'end_date' => $this->parseDate($actionData['end_date'] ?? null),
                ]);

                foreach ($actionData['subactions'] ?? [] as $subData) {
                    if (empty($subData['title'])) {
                        continue;
                    }

                    $subPicId = null;
                    if (! empty($subData['pic_user_id']) && isset($picMap[(string) $subData['pic_user_id']])) {
                        $subPicId = $picMap[(string) $subData['pic_user_id']];
                    }

                    $subStatus = $subData['status_id'] ?? $actionStatus;

                    ProjectSubAction::create([
                        'action_id' => $action->id,
                        'title' => $subData['title'],
                        'description' => $subData['description'] ?? null,
                        'status_id' => $subStatus,
                        'progress' => $subData['progress'] ?? 0,
                        'pic_id' => $subPicId,
                        'start_date' => $this->parseDate($subData['start_date'] ?? null),
                        'end_date' => $this->parseDate($subData['end_date'] ?? null),
                    ]);
                }
            }
        }

        if (array_key_exists('project_costs', $validated)) {
            $project->costs()->delete();

            foreach ($validated['project_costs'] ?? [] as $costData) {
                if (empty($costData['cost_item'])) {
                    continue;
                }

                ProjectCost::create([
                    'project_id' => $project->id,
                    'cost_item' => $costData['cost_item'],
                    'category' => $costData['category'],
                    'estimated_cost' => isset($costData['estimated_cost']) ? (float) $costData['estimated_cost'] : 0,
                    'actual_cost' => isset($costData['actual_cost']) ? (float) $costData['actual_cost'] : null,
                    'notes' => $costData['notes'] ?? null,
                ]);
            }
        }

        if (array_key_exists('project_risks', $validated)) {
            $project->risks()->delete();

            foreach ($validated['project_risks'] ?? [] as $riskData) {
                if (empty($riskData['name'])) {
                    continue;
                }

                ProjectRiskAnalysis::create([
                    'project_id' => $project->id,
                    'name' => $riskData['name'],
                    'description' => $riskData['description'] ?? null,
                    'impact' => $riskData['impact'] ?? 'medium',
                    'likelihood' => $riskData['likelihood'] ?? 'possible',
                    'mitigation_plan' => $riskData['mitigation_plan'] ?? null,
                    'status_id' => $riskData['status_id'] ?? $projectStatusId,
                ]);
            }
        }

        if (array_key_exists('project_deliverables', $validated)) {
            $project->deliverables()->delete();

            foreach ($validated['project_deliverables'] ?? [] as $deliverableData) {
                if (empty($deliverableData['name'])) {
                    continue;
                }

                ProjectDeliverable::create([
                    'project_id' => $project->id,
                    'name' => $deliverableData['name'],
                    'description' => $deliverableData['description'] ?? null,
                    'status_id' => $deliverableData['status_id'] ?? $projectStatusId,
                    'completed_at' => $this->parseDate($deliverableData['completed_at'] ?? null),
                    'verified_at' => $this->parseDate($deliverableData['verified_at'] ?? null, true),
                    'verified_by' => $deliverableData['verified_by'] ?? null,
                ]);
            }
        }
    }

    /** Hitung status dari planning (semua selesai -> Done, lainnya In Progress). */
    private function statusFromPlanning(array $planning, ?string $fallback): string
    {
        if (empty($planning)) {
            return WorkflowStatus::normalize($fallback ?? WorkflowStatus::IN_PROGRESS);
        }

        $doneWords = ['done', 'selesai', 'complete', 'completed', 'finish', 'finished', 'ok', 'success', 'selasai'];
        foreach ($planning as $row) {
            $statusValue = strtolower(trim((string) ($row['status'] ?? '')));
            if ($statusValue === '' || ! in_array($statusValue, $doneWords, true)) {
                return WorkflowStatus::IN_PROGRESS;
            }
        }

        return WorkflowStatus::DONE;
    }

    /** Sinkronkan status Ticket dari Project yang terkait. */
    private function syncTicketStatusFromProject(Project $project): void
    {
        if (! $project->ticket_id) {
            return;
        }

        $status = WorkflowStatus::normalize($project->status);
        if (! in_array($status, WorkflowStatus::all(), true)) {
            $status = WorkflowStatus::default();
        }

        Ticket::where('id', $project->ticket_id)->update([
            'status' => $status,
            'status_id' => WorkflowStatus::code($status),
        ]);
    }

    private function ensurePicPresence(array $entries): void
    {
        if ($this->countPicMembers($entries) === 0) {
            throw ValidationException::withMessages([
                'project_pics' => 'Minimal satu PIC wajib dipilih untuk project.',
            ]);
        }
    }

    private function countPicMembers(array $entries): int
    {
        $count = 0;
        foreach ($entries as $entry) {
            $role = strtolower($entry['role_type'] ?? 'pic');
            if ($role === 'pic') {
                $count++;
            }
        }

        return $count;
    }

    private function resolveProjectTeam(array $entries): array
    {
        $normalized = collect($entries)
            ->filter(fn ($entry) => ! empty($entry['user_id']) && ! empty($entry['position']))
            ->map(function ($entry) {
                $role = strtolower($entry['role_type'] ?? 'pic');

                return [
                    'user_id' => (int) $entry['user_id'],
                    'position' => trim((string) $entry['position']),
                    'role_type' => $role === 'agent' ? 'agent' : 'pic',
                    'is_primary' => (bool) ($entry['is_primary'] ?? false),
                ];
            })->values();

        $agents = $normalized->where('role_type', 'agent')->values();
        $pics = $normalized->where('role_type', 'pic')->values();

        $primaryAgent = $agents->firstWhere('is_primary', true)['user_id'] ?? ($agents->first()['user_id'] ?? null);
        $primaryPic = $pics->firstWhere('is_primary', true)['user_id'] ?? ($pics->first()['user_id'] ?? null);

        return [
            'entries' => $normalized->all(),
            'agent_id' => $primaryAgent,
            'assigned_id' => $primaryPic,
        ];
    }

    private function parseDate(?string $value, bool $withTime = false): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if ($withTime) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
                    return Carbon::createFromFormat('Y-m-d\TH:i', $value)->format('Y-m-d H:i:s');
                }
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return Carbon::createFromFormat('Y-m-d', $value)->startOfDay()->format('Y-m-d H:i:s');
                }
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                    return Carbon::createFromFormat('d/m/Y', $value)->startOfDay()->format('Y-m-d H:i:s');
                }

                return Carbon::parse($value)->format('Y-m-d H:i:s');
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value;
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
                return Carbon::createFromFormat('Y-m-d\TH:i', $value)->format('Y-m-d');
            }
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function transformProjectSummary(Project $project): array
    {
        $status = $this->normalizeStatus($project->status ?? null);
        $tz = config('app.timezone');

        return [
            'id' => $project->id,
            'slug' => $project->public_slug,
            'title' => $project->title,
            'project_no' => $project->project_no,
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'status_id' => $project->status_id,
            'due_display' => $this->formatDate($project->end_date, 'd/m/Y', $tz),
            'updated_diff' => $project->updated_at?->diffForHumans() ?? '—',
            'links' => $this->buildProjectLinks($project),
            'ticket' => $project->relationLoaded('ticket') && $project->ticket
                ? [
                    'ticket_no' => $project->ticket->ticket_no,
                    'status' => $this->normalizeStatus($project->ticket->status ?? null),
                ]
                : null,
        ];
    }

    private function buildProjectLinks(Project $project): array
    {
        return [
            'show' => route('projects.show', ['project' => $project->public_slug]),
            'edit' => route('projects.edit', ['project' => $project->public_slug]),
            'delete' => route('projects.destroy', ['project' => $project->id]),
        ];
    }

    private function transformProjectDetail(Project $project): array
    {
        $tz = config('app.timezone');
        $status = $this->normalizeStatus($project->status ?? null);

        $ticket = null;
        if ($project->relationLoaded('ticket') && $project->ticket) {
            $ticket = [
                'id' => $project->ticket->id,
                'title' => $project->ticket->title,
                'ticket_no' => $project->ticket->ticket_no,
                'status' => $this->normalizeStatus($project->ticket->status ?? null),
                'due_display' => $this->formatDate($project->ticket->due_at ?? $project->ticket->due_date, 'd/m/Y', $tz),
                'assignee' => $project->ticket->assignee ? [
                    'id' => $project->ticket->assignee->id,
                    'name' => $this->userDisplayName($project->ticket->assignee),
                ] : null,
                'requester' => $project->ticket->requester ? [
                    'id' => $project->ticket->requester->id,
                    'name' => $this->userDisplayName($project->ticket->requester),
                    'email' => $project->ticket->requester->email,
                ] : null,
                'assigned' => $project->ticket->relationLoaded('assignedUsers')
                    ? $project->ticket->assignedUsers->map(fn ($user) => [
                        'id' => $user->id,
                        'name' => $this->userDisplayName($user),
                    ])->values()->all()
                    : [],
                'attachments' => $this->mapTicketAttachments($project->ticket),
            ];
        }

        $teamMembers = $project->relationLoaded('pics')
            ? $project->pics->map(function (ProjectPic $pic) {
                $name = $pic->relationLoaded('user') && $pic->user
                    ? $this->userDisplayName($pic->user)
                    : 'User #' . $pic->user_id;

                return [
                    'id' => $pic->id,
                    'user_id' => $pic->user_id,
                    'name' => $name,
                    'position' => $pic->position,
                    'role_type' => $pic->role_type ?? 'pic',
                    'is_primary' => (bool) $pic->is_primary,
                ];
            })
            : collect();

        $actions = $project->relationLoaded('actions')
            ? $project->actions->map(fn ($action) => [
                'id' => $action->id,
                'title' => $action->title,
                'status_id' => $action->status_id,
                'progress' => $action->progress,
                'start' => $this->formatDate($action->start_date, 'd/m/Y', $tz),
                'end' => $this->formatDate($action->end_date, 'd/m/Y', $tz),
                'description' => $action->description,
                'subactions' => $action->relationLoaded('subactions')
                    ? $action->subactions->map(fn ($sub) => [
                        'id' => $sub->id,
                        'title' => $sub->title,
                        'status_id' => $sub->status_id,
                        'progress' => $sub->progress,
                        'start' => $this->formatDate($sub->start_date, 'd/m/Y', $tz),
                        'end' => $this->formatDate($sub->end_date, 'd/m/Y', $tz),
                        'description' => $sub->description,
                    ])->values()->all()
                    : [],
            ])->values()->all()
            : [];

        $costs = $project->relationLoaded('costs')
            ? $project->costs->map(fn (ProjectCost $cost) => [
                'id' => $cost->id,
                'item' => $cost->cost_item,
                'category' => $cost->category,
                'estimated' => $cost->estimated_cost,
                'actual' => $cost->actual_cost,
            ])->values()->all()
            : [];

        $risks = $project->relationLoaded('risks')
            ? $project->risks->map(fn (ProjectRiskAnalysis $risk) => [
                'id' => $risk->id,
                'name' => $risk->name,
                'status_id' => $risk->status_id,
                'impact' => $risk->impact,
                'likelihood' => $risk->likelihood,
                'description' => $risk->description,
                'mitigation' => $risk->mitigation_plan,
            ])->values()->all()
            : [];

        $deliverables = $project->relationLoaded('deliverables')
            ? $project->deliverables->map(fn (ProjectDeliverable $deliverable) => [
                'id' => $deliverable->id,
                'name' => $deliverable->name,
                'status_id' => $deliverable->status_id,
                'verified_by' => $deliverable->verified_by,
                'verified_by_label' => $deliverable->verified_by
                    ? Str::of($deliverable->verified_by)->replace('_', ' ')->title()->value()
                    : null,
                'due' => $this->formatDate($deliverable->completed_at ?? $deliverable->verified_at, 'd/m/Y H:i', $tz),
                'completed' => $this->formatDate($deliverable->completed_at, 'd/m/Y H:i', $tz),
                'verified' => $this->formatDate($deliverable->verified_at, 'd/m/Y H:i', $tz),
                'description' => $deliverable->description,
            ])->values()->all()
            : [];

        $attachments = $project->relationLoaded('attachments')
            ? $project->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
            ])->values()->all()
            : [];
        $ticketAttachments = $this->mapTicketAttachments($project->ticket);

        $relatedProjects = [];
        if ($project->ticket_id) {
            $viewer = Auth::user();
            $currentProjectId = $project->id;
            $relatedQuery = Project::query()
                ->select([
                    'id',
                    'public_slug',
                    'title',
                    'project_no',
                    'status',
                    'status_id',
                    'start_date',
                    'end_date',
                    'created_at',
                    'updated_at',
                    'ticket_id',
                ])
                ->where('ticket_id', $project->ticket_id)
                ->orderBy('created_at')
                ->orderBy('id');

            $relatedQuery = UnitVisibility::scopeProjects($relatedQuery, $viewer);

            $relatedProjects = $relatedQuery->get()
                ->map(function (Project $related) use ($tz, $currentProjectId) {
                    $relatedStatus = $this->normalizeStatus($related->status ?? null);

                    return [
                        'id' => $related->id,
                        'title' => $related->title,
                        'project_no' => $related->project_no,
                        'is_current' => (int) $related->id === (int) $currentProjectId,
                        'status' => $relatedStatus,
                        'status_label' => WorkflowStatus::label($relatedStatus),
                        'status_badge' => WorkflowStatus::badgeClass($relatedStatus),
                        'due_display' => $this->formatDate($related->end_date, 'd/m/Y', $tz),
                        'updated_display' => $this->formatDate($related->updated_at, 'd M Y H:i', $tz),
                        'links' => [
                            'show' => route('projects.show', ['project' => $related->public_slug ?? $related->id]),
                            'edit' => route('projects.edit', ['project' => $related->public_slug ?? $related->id]),
                        ],
                    ];
                })
                ->values()
                ->all();
        }

        return [
            'id' => $project->id,
            'slug' => $project->public_slug,
            'title' => $project->title,
            'project_no' => $project->project_no,
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'status_id' => $project->status_id,
            'description' => $project->description,
            'timeline' => [
                'start' => $this->formatDate($project->start_date, 'd/m/Y', $tz),
                'end' => $this->formatDate($project->end_date, 'd/m/Y', $tz),
                'created' => $this->formatDate($project->created_at, 'd M Y H:i', $tz),
                'updated' => $this->formatDate($project->updated_at, 'd M Y H:i', $tz),
            ],
            'ticket' => $ticket,
            'team' => [
                'agents' => $teamMembers->where('role_type', 'agent')->values()->all(),
                'pics' => $teamMembers->where('role_type', 'pic')->values()->all(),
            ],
            'pics' => $teamMembers->values()->all(),
            'actions' => $actions,
            'costs' => $costs,
            'risks' => $risks,
            'deliverables' => $deliverables,
            'attachments' => $attachments,
            'ticket_attachments' => $ticketAttachments,
            'related_projects' => $relatedProjects,
            'links' => array_merge(
                $this->buildProjectLinks($project),
                ['pdf' => route('projects.report.detail', ['project' => $project->id])]
            ),
        ];
    }

    private function projectReportSummary(array $filters, bool $withTicket, ?User $viewer = null): array
    {
        $query = $this->baseProjectReportQuery($filters, $withTicket, $viewer);

        if ($withTicket) {
            $total = $this->countProjectTicketGroups(clone $query);
        } else {
            $total = (clone $query)->count();
        }

        $inProgress = $this->countProjectSummaryByStatus(
            clone $query,
            WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS),
            $withTicket
        );

        $done = $this->countProjectSummaryByStatus(
            clone $query,
            WorkflowStatus::equivalents(WorkflowStatus::DONE),
            $withTicket
        );

        return [
            'total' => $total,
            'in_progress' => $inProgress,
            'done' => $done,
        ];
    }

    private function mapTicketAttachments(?Ticket $ticket): array
    {
        if (! $ticket || ! $ticket->relationLoaded('attachments')) {
            return [];
        }

        return $ticket->attachments
            ->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
            ])
            ->values()
            ->all();
    }

    private function projectReportPaginator(array $filters, bool $withTicket, Request $request, ?User $viewer = null)
    {
        $perPage = (int) $request->integer($withTicket ? 'ticket_per_page' : 'standalone_per_page', 15);
        $perPage = min(max($perPage, 5), 50);

        $pageName = $withTicket ? 'ticket_page' : 'standalone_page';
        if ($withTicket) {
            $baseQuery = $this->baseProjectReportQuery($filters, true, $viewer);
            $detailedQuery = $this->buildProjectReportQuery($filters, true, true, $viewer);

            return $this->paginateTicketProjectGroups(
                $baseQuery,
                $detailedQuery,
                $perPage,
                $pageName,
                $filters
            );
        }

        $query = $this->buildProjectReportQuery($filters, false, true, $viewer);

        return $query
            ->paginate($perPage, ['*'], $pageName)
            ->appends($this->reportQueryParams($filters, $withTicket))
            ->through(fn (Project $project) => $this->transformProjectReportRow($project));
    }

    private function baseProjectReportQuery(array $filters, bool $withTicket, ?User $viewer = null): Builder
    {
        $query = Project::query();
        $query = UnitVisibility::scopeProjects($query, $viewer);

        $query->when($withTicket, fn ($q) => $q->whereNotNull('ticket_id'), fn ($q) => $q->whereNull('ticket_id'));

        if (! empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('project_no', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            if ($withTicket) {
                $query->whereHas('ticket', fn ($t) => $t->where('status', $filters['status']));
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query;
    }

    private function buildProjectReportQuery(array $filters, bool $withTicket, bool $withRelations = false, ?User $viewer = null): Builder
    {
        $query = $this->baseProjectReportQuery($filters, $withTicket, $viewer)
            ->select([
                'id',
                'title',
                'project_no',
                'status',
                'status_id',
                'ticket_id',
                'description',
                'planning',
                'start_date',
                'end_date',
                'created_by',
                'created_at',
                'updated_at',
            ]);

        if ($withRelations) {
            $userMeta = $this->resolveUserColumnInfo();
            $userColumnList = implode(',', $userMeta['selectable']);

            $query->with([
                'ticket:id,ticket_no,status,due_at,due_date,requester_id,agent_id,assigned_id',
                'ticket.requester:'.$userColumnList,
                'ticket.agent:'.$userColumnList,
                'ticket.assignee:'.$userColumnList,
                'ticket.assignedUsers:'.$userColumnList,
                'ticket.attachments:id,attachable_id,attachable_type,original_name,size,path',
                'attachments:id,attachable_id,attachable_type,original_name,size,path',
                'user' => fn ($userQuery) => $userQuery->select($userMeta['selectable']),
            ]);
        }

        return $query
            ->orderByRaw("(SUBSTRING_INDEX(title,' ', -1) REGEXP '^[0-9]+$') DESC")
            ->orderByRaw("CAST(SUBSTRING_INDEX(title,' ', -1) AS UNSIGNED) ASC")
            ->orderBy('title');
    }

    private function paginateTicketProjectGroups(
        Builder $baseQuery,
        Builder $detailedQuery,
        int $perPage,
        string $pageName,
        array $filters
    ) {
        $groupQuery = (clone $baseQuery)
            ->selectRaw('ticket_id, MAX(updated_at) as latest_updated_at')
            ->groupBy('ticket_id')
            ->orderByDesc('latest_updated_at');

        $paginator = $groupQuery
            ->paginate($perPage, ['ticket_id', 'latest_updated_at'], $pageName)
            ->appends($this->reportQueryParams($filters, true));

        $ticketIds = collect($paginator->items())
            ->pluck('ticket_id')
            ->filter()
            ->all();

        if (empty($ticketIds)) {
            $paginator->setCollection(collect());

            return $paginator;
        }

        $projects = (clone $detailedQuery)
            ->whereIn('ticket_id', $ticketIds)
            ->orderBy('ticket_id')
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('ticket_id')
            ->map(function (Collection $rows) {
                $items = $rows
                    ->map(fn (Project $project) => $this->transformProjectReportRow($project))
                    ->values();

                $primary = $items->first();
                $children = $items->slice(1)->values()->all();

                return [
                    'ticket' => $primary['ticket'] ?? null,
                    'primary' => $primary,
                    'children' => $children,
                ];
            });

        $ordered = collect($ticketIds)
            ->map(fn ($ticketId) => $projects->get($ticketId))
            ->filter()
            ->values();

        return tap($paginator, fn ($p) => $p->setCollection($ordered));
    }

    private function countProjectTicketGroups(Builder $query): int
    {
        return (int) (clone $query)
            ->whereNotNull('ticket_id')
            ->distinct('ticket_id')
            ->count('ticket_id');
    }

    private function countProjectSummaryByStatus(Builder $query, array $statuses, bool $withTicket): int
    {
        if (empty($statuses)) {
            return 0;
        }

        if ($withTicket) {
            return (clone $query)
                ->whereNotNull('ticket_id')
                ->whereHas('ticket', fn ($ticket) => $ticket->whereIn('status', $statuses))
                ->distinct('ticket_id')
                ->count('ticket_id');
        }

        return (clone $query)
            ->whereNull('ticket_id')
            ->whereIn('status', $statuses)
            ->count();
    }

    private function transformProjectReportRow(Project $project): array
    {
        $status = $this->normalizeStatus($project->status ?? null);
        $tz = config('app.timezone');

        $ticketInfo = null;
        $ticketStatus = null;
        if ($project->relationLoaded('ticket') && $project->ticket) {
            $ticketStatus = $this->normalizeStatus($project->ticket->status ?? null);
            $displayStatus = $ticketStatus ?? $status;

            $ticketInfo = [
                'id' => $project->ticket->id,
                'ticket_no' => $project->ticket->ticket_no,
                'status' => $ticketStatus,
                'status_label' => WorkflowStatus::label($ticketStatus),
                'status_badge' => WorkflowStatus::badgeClass($ticketStatus),
                'requester' => $project->ticket->relationLoaded('requester') && $project->ticket->requester
                    ? [
                        'id' => $project->ticket->requester->id,
                        'name' => $this->userDisplayName($project->ticket->requester),
                        'email' => $project->ticket->requester->email,
                    ]
                    : null,
                'attachments' => $project->ticket->relationLoaded('attachments')
                    ? $project->ticket->attachments->map(fn ($attachment) => [
                        'id' => $attachment->id,
                        'name' => $attachment->original_name,
                        'size' => $attachment->size,
                        'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                        'download_url' => $this->attachmentRoute('attachments.download', $attachment),
                    ])->values()->all()
                    : [],
            ];
        }

        return [
            'id' => $project->id,
            'title' => $project->title,
            'project_no' => $project->project_no,
            'status' => $ticketStatus ?? $status,
            'status_label' => WorkflowStatus::label($ticketStatus ?? $status),
            'status_badge' => WorkflowStatus::badgeClass($ticketStatus ?? $status),
            'status_ticket' => $ticketStatus,
            'status_ticket_label' => $ticketStatus ? WorkflowStatus::label($ticketStatus) : null,
            'status_ticket_badge' => $ticketStatus ? WorkflowStatus::badgeClass($ticketStatus) : null,
            'status_project' => $status,
            'status_project_label' => WorkflowStatus::label($status),
            'status_project_badge' => WorkflowStatus::badgeClass($status),
            'status_id' => $project->status_id,
            'due_display' => $this->formatDate($project->end_date ?? optional($project->ticket)->due_at, 'd/m/Y', $tz),
            'created_display' => $this->formatDate($project->created_at, 'd M Y H:i', $tz),
            'updated_display' => $this->formatDate($project->updated_at, 'd M Y H:i', $tz),
            'start_display' => $this->formatDate($project->start_date, 'd/m/Y', $tz),
            'type_label' => $project->ticket_id ? 'Berbasis Ticket' : 'Mandiri',
            'ticket' => $ticketInfo,
            'description' => $project->description,
            'planning' => $project->planning,
            'attachments' => $project->relationLoaded('attachments')
                ? $project->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'name' => $attachment->original_name,
                    'size' => $attachment->size,
                    'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                    'download_url' => $this->attachmentRoute('attachments.download', $attachment),
                ])->values()->all()
                : [],
            'creator' => $project->relationLoaded('user') && $project->user
                ? [
                    'id' => $project->user->id,
                    'name' => $this->userDisplayName($project->user),
                ]
                : null,
            'links' => array_merge(
                $this->buildProjectLinks($project),
                [
                    'ticket' => $project->ticket
                        ? route('tickets.show', ['ticket' => $project->ticket->id])
                        : null,
                ]
            ),
        ];
    }

    private function reportQueryParams(array $filters, bool $withTicket): array
    {
        return array_filter([
            'q' => $filters['q'] ?? null,
            'status' => $filters['status'] ?? null,
            'from' => isset($filters['from']) && $filters['from'] instanceof Carbon
                ? $filters['from']->format('d/m/Y')
                : null,
            'to' => isset($filters['to']) && $filters['to'] instanceof Carbon
                ? $filters['to']->format('d/m/Y')
                : null,
        ], fn ($value) => filled($value));
    }

    private function parseReportDate(?string $value, bool $startOfDay = true): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['d/m/Y', 'Y-m-d'];
        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $value);

                return $startOfDay ? $dt->startOfDay() : $dt->endOfDay();
            } catch (\Throwable) {
            }
        }

        try {
            $dt = Carbon::parse($value);

            return $startOfDay ? $dt->startOfDay() : $dt->endOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeStatus($status): string
    {
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        return WorkflowStatus::normalize($status ?? WorkflowStatus::NEW);
    }

    private function mapProjectForPdf(Project $project, bool $withTicket, string $tz): array
    {
        $status = $this->normalizeStatus($project->status ?? null);

        $startDate = $this->formatDate($project->start_date, 'd M Y', $tz);
        $endSource = $project->end_date ?? optional($project->ticket)->due_at ?? optional($project->ticket)->due_date;

        return [
            $project->project_no ?? '—',
            $project->title ?? '—',
            WorkflowStatus::label($status),
            $withTicket ? 'Berbasis Ticket' : 'Mandiri',
            $withTicket && $project->ticket ? ($project->ticket->ticket_no ?? '—') : '—',
            $startDate,
            $this->formatDate($endSource, 'd M Y', $tz),
            $this->formatDate($project->updated_at, 'd M Y H:i', $tz),
        ];
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
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function formatDateRange(?Carbon $from, ?Carbon $to): string
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

    private function formatCurrency($value): string
    {
        if (! is_numeric($value)) {
            return '—';
        }

        $number = (float) $value;

        return 'Rp '.number_format($number, 0, ',', '.');
    }

    private function generateProjectNo(): string
    {
        return Project::nextNumber();
    }

    private function userCanManageTicket($user): bool
    {
        if (! $user) {
            return false;
        }

        $allowed = ['super-admin', 'superadmin', 'super admin', 'admin'];

        try {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($allowed)) {
                return true;
            }

            if (! method_exists($user, 'getRoleNames')) {
                return false;
            }

            $roles = collect($user->getRoleNames() ?? [])->map(fn ($name) => Str::lower($name))->all();
        } catch (\Throwable $e) {
            return false;
        }

        foreach ($roles as $role) {
            if (in_array(Str::lower($role), $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    private function resolveBackUrl(Request $request, string $fallback): string
    {
        $from = $request->query('from', $request->input('from'));
        if ($from && $this->isSafeRedirect($from)) {
            return $this->normalizeInternalUrl($from);
        }

        return $this->normalizeInternalUrl($fallback);
    }

    private function redirectBackTo(Request $request, string $fallbackRoute): RedirectResponse
    {
        $from = $request->input('from') ?: $request->query('from');
        if ($from && $this->isSafeRedirect($from)) {
            return redirect()->to($this->normalizeInternalUrl($from));
        }

        return redirect()->route($fallbackRoute);
    }

    private function isSafeRedirect(string $url): bool
    {
        try {
            $app = rtrim((string) config('app.url'), '/');

            return str_starts_with($url, '/')
                || ($app !== '' && str_starts_with($url, $app));
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function userHasStatusOverride(?User $user): bool
    {
        return RoleHelpers::userIsSuperAdmin($user);
    }

    private function statusGuidance(): array
    {
        return [
            'default' => 'Status awal project selalu New.',
            'agent' => 'Status In Progress dan Confirmation hanya dapat diubah oleh agent/assigned agent/PIC.',
            'requester' => 'Status Revision, Done, Cancelled, dan On Hold hanya dapat diubah oleh Requester.',
            'admin' => 'Hanya Super Admin yang dapat mengubah seluruh status.',
        ];
    }

    private function allowedProjectStatuses(?User $viewer, ?Project $project = null): array
    {
        if (! $viewer) {
            return [];
        }

        if (RoleHelpers::userIsSuperAdmin($viewer)) {
            return WorkflowStatus::all();
        }

        if (! $project) {
            return [WorkflowStatus::NEW];
        }
        $allowed = array_filter(
            WorkflowStatus::all(),
            fn (string $status) => $project->canUserSetStatus($viewer, $status)
        );

        return array_values($allowed);
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
        } catch (\Throwable $e) {
        }

        return $url;
    }

    /** Ambil informasi kolom tabel user yang tersedia & aman dipilih */
    private function resolveUserColumnInfo(): array
    {
        $default = [
            'table' => null,
            'columns' => [],
            'selectable' => ['id'],
        ];

        try {
            $userModel = config('auth.providers.users.model') ?? \App\Models\User::class;
            $instance = new $userModel;
            $table = $instance->getTable();
            $columns = Schema::getColumnListing($table);

            $preferred = collect(['id', 'first_name', 'last_name', 'name', 'full_name', 'username', 'email']);
            $selectable = $preferred
                ->filter(fn (string $column) => in_array($column, $columns, true))
                ->values()
                ->all();

            if (! in_array('id', $selectable, true)) {
                array_unshift($selectable, 'id');
            }

            $selectable = array_values(array_unique($selectable));

            return [
                'table' => $table,
                'columns' => $columns,
                'selectable' => $selectable,
            ];
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /** Ambil opsi user secara generik (ikuti auth provider) */
    private function getUserOptions()
    {
        try {
            $userMeta = $this->resolveUserColumnInfo();
            $table = $userMeta['table'];

            if (! $table) {
                return collect();
            }

            $selectable = $userMeta['selectable'];
            $columns = $userMeta['columns'];

            $orderColumn = collect(['name', 'full_name', 'username', 'email', 'first_name', 'last_name'])
                ->first(fn (string $column) => in_array($column, $columns, true)) ?? 'id';

            if (! in_array($orderColumn, $selectable, true)) {
                $selectable[] = $orderColumn;
            }

            foreach (['unit', 'email', 'username'] as $column) {
                if (in_array($column, $columns, true) && ! in_array($column, $selectable, true)) {
                    $selectable[] = $column;
                }
            }

            $selectable = array_values(array_unique($selectable));

            $rows = DB::table($table)
                ->select($selectable)
                ->orderBy($orderColumn)
                ->get();

            return $rows->map(function ($row) {
                $label = $this->userDisplayName($row);

                return (object) [
                    'id' => $row->id,
                    'label' => $label,
                    'unit' => data_get($row, 'unit'),
                    'email' => data_get($row, 'email'),
                    'username' => data_get($row, 'username'),
                ];
            });
        } catch (\Throwable $e) {
            return collect();
        }
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

    private function userDisplayName($user): string
    {
        if (! $user) {
            return 'User';
        }

        $first = data_get($user, 'first_name');
        $last = data_get($user, 'last_name');
        $full = trim(implode(' ', array_filter([$first, $last])));
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

    private function userCanSelectRequester($user): bool
    {
        return RoleHelpers::userIsSuperAdmin($user);
    }

    /** Rapikan planning: terima ARRAY atau JSON string */
    private function normalizePlanning($rows): array
    {
        if (is_string($rows) && $rows !== '') {
            $decoded = json_decode($rows, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $rows = $decoded;
            }
        }
        if (! is_array($rows)) {
            return [];
        }

        $clean = [];
        foreach ($rows as $r) {
            $title = trim($r['title'] ?? '');
            $week = trim($r['week'] ?? '');
            $status = trim($r['status'] ?? '');
            $note = trim($r['note'] ?? '');
            if ($title === '' && $week === '' && $status === '' && $note === '') {
                continue;
            }
            $clean[] = compact('title', 'week', 'status', 'note');
        }

        return array_values($clean);
    }

    /** Sanitasi HTML dari editor */
    private function sanitizeDescription(string $html): string
    {
        $allowed = '<p><br><div><span><strong><b><em><i><u><s><mark>'
            .'<ul><ol><li><blockquote><code><pre><hr>'
            .'<h1><h2><h3><h4>'
            .'<a><table><thead><tbody><tr><th><td><figure><figcaption>';

        $clean = strip_tags($html, $allowed);

        // Hapus event handler JS
        $clean = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);

        // Batasi href ke http(s), mailto, atau #
        $clean = preg_replace_callback(
            '/<a\b[^>]*href\s*=\s*("|\')(.*?)\1[^>]*>/i',
            function ($m) {
                $url = trim($m[2]);
                if (preg_match('#^(https?://|mailto:|#)#i', $url)) {
                    return $m[0];
                }

                return preg_replace('/href\s*=\s*("|\')(.*?)\1/i', 'href="#"', $m[0]);
            },
            $clean
        );

        return $clean;
    }
}


