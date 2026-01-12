<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Project;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(
        private AttachmentService $attachments,
        private ReportExportService $reportExport
    ) {}

    private function statuses(): array
    {
        return WorkflowStatus::all();
    }

    private function ticketReportDetailUrl(Ticket $ticket): string
    {
        $ticketNo = is_string($ticket->ticket_no ?? null) ? trim((string) $ticket->ticket_no) : '';
        if ($ticketNo !== '') {
            return route('tickets.report.detail.view', ['ticket' => $ticketNo]);
        }

        return route('tickets.edit', ['ticket' => $ticket->id]);
    }

    private function priorities(): array
    {
        return ['high', 'medium', 'low'];
    }

    private function types(): array
    {
        return ['task', 'project'];
    }

    private function slas(): array
    {
        return ['ontime', 'late'];
    }

    private function hasUserCode(): bool
    {
        return Schema::hasColumn('users', 'code');
    }

    /** Gabungkan dd/mm/YYYY + HH:MM menjadi Y-m-d H:i:s (nullable-safe) */
    private function composeDateTime(?string $dateDmy, ?string $timeHm): ?string
    {
        if (! $dateDmy) {
            return null;
        }
        $dateDmy = trim($dateDmy);
        $timeHm = trim($timeHm ?? '00:00');

        try {
            if (! preg_match('/^\d{2}:\d{2}$/', $timeHm)) {
                $timeHm = '00:00';
            }
            $dt = Carbon::createFromFormat('d/m/Y H:i', "{$dateDmy} {$timeHm}");

            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            try {
                $dt = Carbon::parse("{$dateDmy} {$timeHm}");

                return $dt->format('Y-m-d H:i:s');
            } catch (\Throwable) {
            }
        }

        return null;
    }

    private function normalizeDateTimeInput($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $dt = $value instanceof Carbon ? $value : Carbon::parse($value);

            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function toInputDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $dt = $value instanceof Carbon ? $value : Carbon::parse($value);

            return $dt->format('Y-m-d H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Daftar users dengan label defensif (selalu terisi) */
    private function usersList(?User $viewer = null)
    {
        $cols = ['id'];
        foreach (['first_name', 'last_name', 'name', 'full_name', 'username', 'email'] as $c) {
            if (Schema::hasColumn('users', $c)) {
                $cols[] = $c;
            }
        }

        $hasCodeColumn = $this->hasUserCode();
        if ($hasCodeColumn) {
            $cols[] = 'code';
        }

        $hasUnitColumn = Schema::hasColumn('users', 'unit');
        if ($hasUnitColumn) {
            $cols[] = 'unit';
        }

        $hasRoleColumn = Schema::hasColumn('users', 'role');
        if ($hasRoleColumn) {
            $cols[] = 'role';
        }

        $orderCol = collect(['name', 'full_name', 'first_name', 'username', 'email'])
            ->first(fn ($c) => Schema::hasColumn('users', $c)) ?? 'id';

        $query = User::query()
            ->select(array_values(array_unique($cols)))
            ->orderBy($orderCol)
            ->with('roles:id,name');

        return $query->get()
            ->map(function ($u) {
                $full = trim(implode(' ', array_filter([$u->first_name ?? null, $u->last_name ?? null])));
                $u->label = $full ?: ($u->name ?? $u->full_name ?? $u->username ?? $u->email ?? ('User #'.$u->id));
                $u->primary_role = $this->primaryRoleFromLoadedUser($u);

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

    private function unitOptionsForUser($users, ?User $viewer): array
    {
        $units = [];
        $seen = [];

        $collector = function (?string $label) use (&$seen, &$units) {
            $label = is_string($label) ? trim($label) : '';
            if ($label === '') {
                return;
            }

            $key = $this->normalizedUnitKey($label);
            if (! $key || isset($seen[$key])) {
                return;
            }

            $seen[$key] = true;
            $units[] = $label;
        };

        foreach (UserUnitOptions::values() as $predefinedUnit) {
            $collector($predefinedUnit);
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

        $value = preg_replace('/\([^)]*\)/', ' ', $value); // remove text inside parentheses
        $value = preg_replace('/^UNIT[\s_\-]*/i', '', $value);
        $value = preg_replace('/[^A-Z0-9]+/i', '', $value);
        $value = mb_strtoupper($value, 'UTF-8');

        return $value !== '' ? $value : null;
    }

    private function allowedTicketStatuses(?User $viewer, ?Ticket $ticket = null): array
    {
        if (! $viewer) {
            return [];
        }

        if ($this->userHasStatusOverride($viewer)) {
            return WorkflowStatus::all();
        }

        if (! $ticket) {
            return [];
        }

        $ticket->loadMissing('assignedUsers', 'requester', 'agent', 'assignee');

        $isAgent = $ticket->isAgent($viewer);
        $isRequester = $ticket->isRequester($viewer);

        $allowed = [];
        if ($isAgent) {
            $allowed = array_merge($allowed, WorkflowStatus::agentAllowed());
        }
        if ($isRequester) {
            $allowed = array_merge($allowed, WorkflowStatus::requesterAllowed());
        }

        return array_values(array_unique(array_map([WorkflowStatus::class, 'normalize'], $allowed)));
    }

    private function formatUserOptions($users): array
    {
        return $users
            ->map(function ($user) {
                $roleName = $user->primary_role ?? $this->primaryRoleFromLoadedUser($user);

                return [
                    'id' => $user->id,
                    'label' => $user->label,
                    'email' => $user->email ?? null,
                    'unit' => $user->unit ?? null,
                    'agent_label' => RoleHelpers::displayLabel($roleName),
                    'agent_code' => RoleHelpers::canonical($roleName),
                ];
            })
            ->values()
            ->all();
    }

    private function primaryRoleFromLoadedUser(User $user): ?string
    {
        if ($user->relationLoaded('roles') && $user->roles->isNotEmpty()) {
            return $user->roles->first()?->name;
        }

        return $user->role ?? null;
    }

    private function userCanSelectRequester(?User $user): bool
    {
        return RoleHelpers::userIsSuperAdmin($user);
    }

    private function userHasStatusOverride(?User $user): bool
    {
        return RoleHelpers::userIsSuperAdmin($user);
    }

    private function statusGuidance(): array
    {
        return [
            'default' => 'Status awal tiket selalu New.',
            'agent' => 'Status In Progress dan Confirmation hanya dapat diubah oleh agent atau assigned user.',
            'requester' => 'Status Revision, Done, Cancelled, dan On Hold hanya dapat diubah oleh Requester.',
            'admin' => 'Hanya Super Admin yang dapat mengubah seluruh status di luar aturan di atas.',
        ];
    }

    private function userSelectColumns(): array
    {
        $fallback = ['id'];

        try {
            $available = Schema::getColumnListing('users');
        } catch (\Throwable) {
            return $fallback;
        }

        $preferred = ['id', 'first_name', 'last_name', 'name', 'full_name', 'username', 'email'];

        $columns = collect($preferred)
            ->filter(fn (string $column) => in_array($column, $available, true))
            ->values()
            ->all();

        return $columns ?: $fallback;
    }

    /** Map status_id -> label dari tabel statuses bila ada */
    private function statusMap(): array
    {
        if (! Schema::hasTable('statuses')) {
            $labels = WorkflowStatus::labels();

            return array_combine(array_keys($labels), array_values($labels));
        }

        $codeCol = collect(['code', 'id', 'status_id'])->first(fn ($c) => Schema::hasColumn('statuses', $c));
        if (! $codeCol) {
            return [];
        }

        $labelCol = collect(['name', 'label', 'title', 'status', 'status_name'])
            ->first(fn ($c) => Schema::hasColumn('statuses', $c));

        $rows = $labelCol
            ? DB::table('statuses')->select([$codeCol.' as code', $labelCol.' as label'])->orderBy($labelCol)->get()
            : DB::table('statuses')->select([$codeCol.' as code'])->orderBy($codeCol)->get();

        $map = [];
        foreach ($rows as $r) {
            $map[$r->code] = $labelCol ? $r->label : $r->code;
        }

        return $map;
    }

    private function parseDateOrNull(?string $val): ?string
    {
        if (! $val) {
            return null;
        }
        $val = trim($val);
        try {
            return Carbon::createFromFormat('d/m/Y', $val)->format('Y-m-d');
        } catch (\Throwable) {
        }
        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable) {
        }

        return null;
    }

    /** ───────────────────── Helper mapping Task ───────────────────── */

    /** Ticket.status → Task.status (sesuaikan enum Task) */
    private function mapTaskStatus(?string $s): string
    {
        $s = strtolower((string) $s);

        return in_array($s, WorkflowStatus::all(), true)
            ? $s
            : WorkflowStatus::default();
    }

    /** Bangun payload Task dari Ticket dan hanya isi kolom yang memang ada di DB */
    private function taskPayloadFromTicket(Ticket $t, bool $includeStatus = false): array
    {
        $payload = [
            'title' => $t->title,
        ];
        if ($includeStatus) {
            $payload['status'] = WorkflowStatus::default();
        }

        if (Schema::hasColumn('tasks', 'start_date')) {
            $payload['start_date'] = $t->due_date;
        }
        if (Schema::hasColumn('tasks', 'end_date')) {
            $payload['end_date'] = $t->finish_date;
        }
        if (Schema::hasColumn('tasks', 'created_by')) {
            $payload['created_by'] = Auth::id();
        }

        return $payload;
    }

    /** ───────────────────── End Helper ───────────────────── */
    public function index(Request $request): RedirectResponse
    {
        $query = $request->getQueryString();
        $target = route('tickets.report');

        if ($query) {
            $target .= '?'.$query;
        }

        return redirect()->to($target);
    }

    public function create(Request $request): Response
    {
        $statusOptions = collect($this->statuses())
            ->map(fn (string $status) => [
                'value' => $status,
                'label' => WorkflowStatus::label($status),
            ])
            ->values();

        $priorityOptions = collect($this->priorities())
            ->map(fn (string $priority) => [
                'value' => $priority,
                'label' => ucfirst($priority),
            ])
            ->values();

        $typeOptions = collect($this->types())
            ->map(fn (string $type) => [
                'value' => $type,
                'label' => ucfirst($type),
            ])
            ->values();

        $viewer = $request->user();
        $users = $this->usersList($viewer);
        $userOptions = $this->formatUserOptions($users);
        $canSelectRequester = $this->userCanSelectRequester($viewer);
        $unitOptions = $this->unitOptionsForUser($users, $viewer);
        $allowedStatuses = $this->allowedTicketStatuses($viewer);
        $lockStatus = empty($allowedStatuses);

        $defaultPriorityOption = $priorityOptions->first();
        $defaultTypeOption = $typeOptions->first();

        $defaults = [
            'priority' => is_array($defaultPriorityOption) ? ($defaultPriorityOption['value'] ?? 'medium') : 'medium',
            'type' => is_array($defaultTypeOption) ? ($defaultTypeOption['value'] ?? 'task') : 'task',
            'status' => WorkflowStatus::normalize(WorkflowStatus::default()),
            'due_at' => null,
            'finish_at' => null,
            'requester_id' => $viewer?->id,
        ];

        return Inertia::render('Tickets/Create', [
            'statusOptions' => $statusOptions,
            'priorityOptions' => $priorityOptions,
            'typeOptions' => $typeOptions,
            'slaOptions' => $this->slas(),
            'defaults' => $defaults,
            'userOptions' => $userOptions,
            'meta' => [
                'canSelectRequester' => $canSelectRequester,
                'requesterLabel' => $this->userDisplayName($viewer),
                'statusGuide' => $this->statusGuidance(),
                'statusDefault' => WorkflowStatus::label(WorkflowStatus::default()),
                'lockStatus' => $lockStatus,
                'unitOptions' => $unitOptions,
                'allowedStatuses' => $allowedStatuses,
            ],
        ]);
    }

    public function store(Request $request)
    {
        if ($request->filled('due_at')) {
            try {
                $due = Carbon::parse($request->input('due_at'));
                $request->merge([
                    'due_date' => $due->format('d/m/Y'),
                    'due_time' => $due->format('H:i'),
                ]);
            } catch (\Throwable $e) {
                // ignore parse error; validation will catch
            }
        }

        $currentStatus = WorkflowStatus::default();
        $statusProvided = $request->has('status');
        $requestedStatus = WorkflowStatus::normalize($request->input('status', $currentStatus));
        $finalStatus = $statusProvided ? $requestedStatus : $currentStatus;

        $request->merge(['status' => $currentStatus]);

        $missingValue = '__input_missing__';
        $statusInputRaw = $request->input('status', $missingValue);
        $dueAtInputRaw = $request->input('due_at', $missingValue);
        $finishAtInputRaw = $request->input('finish_at', $missingValue);
        $dueDateInputRaw = $request->input('due_date', $missingValue);
        $finishDateInputRaw = $request->input('finish_date', $missingValue);

        $statusProvided = $statusInputRaw !== $missingValue;
        $dueAtProvided = $dueAtInputRaw !== $missingValue;
        $finishAtProvided = $finishAtInputRaw !== $missingValue;
        $dueDateProvided = $dueDateInputRaw !== $missingValue;
        $finishDateProvided = $finishDateInputRaw !== $missingValue;

        $missingValue = '__input_missing__';
        $statusInputRaw = $request->input('status', $missingValue);
        $dueAtInputRaw = $request->input('due_at', $missingValue);
        $finishAtInputRaw = $request->input('finish_at', $missingValue);
        $dueDateInputRaw = $request->input('due_date', $missingValue);
        $finishDateInputRaw = $request->input('finish_date', $missingValue);

        $statusProvided = $statusInputRaw !== $missingValue;
        $dueAtProvided = $dueAtInputRaw !== $missingValue;
        $finishAtProvided = $finishAtInputRaw !== $missingValue;
        $dueDateProvided = $dueDateInputRaw !== $missingValue;
        $finishDateProvided = $finishDateInputRaw !== $missingValue;

        $assignedRule = $this->hasUserCode()
            ? ['nullable', 'string', 'max:64', Rule::exists('users', 'code')]
            : ['nullable', 'integer', 'min:1', Rule::exists('users', 'id')];

        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'letter_no' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', Rule::in($this->priorities())],
            'type' => ['required', Rule::in($this->types())],
            'status' => ['nullable', Rule::in($this->statuses())],
            'requester_id' => ['nullable', 'integer', 'min:1', Rule::exists('users', 'id')],
            'agent_id' => ['nullable', 'integer', 'min:1', Rule::exists('users', 'id')],
            'assigned_id' => $assignedRule,
            'assigned_user_ids' => ['nullable', 'array'],
            'assigned_user_ids.*' => ['integer', 'min:1', 'exists:users,id'],
            'due_date' => ['nullable', 'string'],
            'finish_date' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'finish_at' => ['nullable', 'date'],
            'sla' => ['nullable', Rule::in($this->slas())],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
        ]);

        $actingUser = $request->user();
        $canPickRequester = $this->userCanSelectRequester($actingUser);

        if ($canPickRequester) {
            $data['requester_id'] = $data['requester_id'] ?? ($actingUser?->id);
        } else {
            $data['requester_id'] = $actingUser?->id;
        }

        $data['requester_id'] = $data['requester_id'] ? (int) $data['requester_id'] : null;
        if (! empty($data['description'])) {
            $data['description'] = $this->sanitizeDescription($data['description']);
            $data['description'] = mb_substr($data['description'], 0, 255);
        }

        $data['due_date'] = $this->parseDateOrNull($data['due_date'] ?? null);
        $data['finish_date'] = $this->parseDateOrNull($data['finish_date'] ?? null);

        $data['due_at'] = $this->normalizeDateTimeInput($data['due_at'] ?? $this->composeDateTime($request->input('due_date'), $request->input('due_time')));
        $data['finish_at'] = $this->normalizeDateTimeInput($data['finish_at'] ?? $this->composeDateTime($request->input('finish_date'), $request->input('finish_time')));

        if (empty($data['due_date']) && $data['due_at']) {
            $data['due_date'] = Carbon::parse($data['due_at'])->toDateString();
        }

        if (empty($data['finish_date']) && $data['finish_at']) {
            $data['finish_date'] = Carbon::parse($data['finish_at'])->toDateString();
        }

        $data['status'] = $currentStatus;
        $data['status_id'] = WorkflowStatus::code($currentStatus);

        $assigneesInput = $request->input('assigned_user_ids', []);
        if (! is_array($assigneesInput)) {
            $assigneesInput = [$assigneesInput];
        }
        $assigneesInput = array_values(array_unique(array_filter(array_map('intval', $assigneesInput), fn ($v) => $v > 0)));
        $validIds = User::whereIn('id', $assigneesInput)->pluck('id')->all();

        $data['assigned_id'] = $validIds[0]
            ?? ((isset($data['assigned_id']) && (int) $data['assigned_id'] > 0) ? (int) $data['assigned_id'] : null);

        $projectCreated = null;
        $taskCreated = null;

        $ticket = DB::transaction(function () use ($data, $validIds, &$projectCreated, &$taskCreated) {
            $ticket = Ticket::create($data);
            $ticket->assignedUsers()->sync($validIds);

            // === AUTO CREATE PROJECT
            if (($ticket->type ?? null) === 'project') {
                $projectStatus = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);

                $project = Project::firstOrCreate(
                    ['ticket_id' => $ticket->id],
                    [
                        'title' => $ticket->title,
                        'description' => $ticket->description,
                        'status' => $projectStatus,
                        'status_id' => WorkflowStatus::code($projectStatus),
                        'start_date' => $ticket->created_at?->toDateString() ?? now()->toDateString(),
                        'end_date' => $ticket->finish_date ?? $ticket->due_date,
                        'created_by' => Auth::id(),
                        'requester_id' => $ticket->requester_id ?: Auth::id(),
                    ]
                );

                if ($project->wasRecentlyCreated) {
                    $projectCreated = $project;
                }

                $this->syncProjectFromTicket($project, $ticket);
            }

            // === AUTO CREATE TASK
            if (($ticket->type ?? null) === 'task') {
                $task = Task::firstOrCreate(
                    ['ticket_id' => $ticket->id],
                    $this->taskPayloadFromTicket($ticket, true)
                );

                if ($task->wasRecentlyCreated) {
                    $taskCreated = $task;
                }
            }

            return $ticket;
        });

        // (opsional) apply requested status setelah create jika diizinkan
        // ...

        if ($finalStatus !== $currentStatus && $ticket->canUserSetStatus($request->user(), $finalStatus)) {
            $ticket->update([
                'status' => $finalStatus,
                'status_id' => WorkflowStatus::code($finalStatus),
            ]);
            $ticket->refresh();

            if (($ticket->type ?? null) === 'task') {
                $task = Task::firstOrCreate(
                    ['ticket_id' => $ticket->id],
                    $this->taskPayloadFromTicket($ticket, true)
                );

                $task->fill($this->taskPayloadFromTicket($ticket, false))->save();
            }
        }

        $attachIds = $request->input('attachments');
        if (empty($attachIds)) {
            $attachIds = $request->input('attachment_ids');
        }
        try {
            Log::info('tickets.store.attachments_input', ['attachments' => $attachIds]);
        } catch (\Throwable) {
        }
        $this->attachments->adoptFromServerIds($attachIds, $ticket);
        try {
            Log::info('tickets.store.attachments_adopted', ['ticket_id' => $ticket->id]);
        } catch (\Throwable) {
        }

        $notifier = app(WorkItemNotifier::class);
        $actor = Auth::user();

        $projectCreated = $projectCreated?->fresh();
        $taskCreated = $taskCreated?->fresh();

        if ($projectCreated || $taskCreated) {
            if ($projectCreated && ($ticket->type ?? null) !== 'project') {
                $notifier->notifyProjectCreated($projectCreated, $actor, true);
            }

            if ($taskCreated && ($ticket->type ?? null) !== 'task') {
                $notifier->notifyTaskCreated($taskCreated, [], $actor, true);
            }

            $notifier->notifyTicketWorkItemRouted($ticket, $taskCreated, $projectCreated, $actor);
        } else {
            $notifier->notifyTicketCreated($ticket, $actor);
        }

        return redirect()->route('tickets.create')
            ->with('success', 'Ticket created successfully.');
    }

    public function show(Request $request, string $locale, Ticket|string $ticket): RedirectResponse
    {
        $ticket = $ticket instanceof Ticket
            ? $ticket
            : Ticket::where('ticket_no', $ticket)->orWhere('id', $ticket)->firstOrFail();

        return redirect()->to($this->ticketReportDetailUrl($ticket));
    }

    public function reportDetailView(Request $request, string $locale, Ticket $ticket): Response
    {
        return $this->renderTicketDetailPage($request, $ticket);
    }

    private function renderTicketDetailPage(Request $request, Ticket $ticket): Response
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        $ticket->load([
            'attachments' => fn ($query) => $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'size'),
            'requester',
            'agent',
            'assignedUsers',
            'projects:id,title,project_no,public_slug,ticket_id,status,status_id,end_date,created_at,updated_at',
        ]);

        $backUrl = $this->resolveBackUrl($request, route('tickets.index'));
        $detail = $this->transformTicketDetail($ticket, $backUrl);

        return Inertia::render('Tickets/Show', [
            'ticket' => $detail,
            'meta' => [
                'backUrl' => $backUrl,
            ],
        ]);
    }

    public function edit(Request $request, string $locale, Ticket $ticket): Response
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        $ticket->load([
            'attachments',
            'assignedUsers',
            'requester',
            'agent',
            'assignee',
        ]);

        $statusOptions = collect($this->statuses())
            ->map(fn (string $status) => [
                'value' => $status,
                'label' => WorkflowStatus::label($status),
            ])
            ->values();

        $priorityOptions = collect($this->priorities())
            ->map(fn (string $priority) => [
                'value' => $priority,
                'label' => ucfirst($priority),
            ])
            ->values();

        $typeOptions = collect($this->types())
            ->map(fn (string $type) => [
                'value' => $type,
                'label' => ucfirst($type),
            ])
            ->values();

        $viewer = $request->user();
        $lockCoreFields = $viewer
            && ! RoleHelpers::userIsSuperAdmin($viewer)
            && ($ticket->isRequester($viewer) || $ticket->isAgent($viewer));
        $statusLabelMap = $this->statusMap();
        $normalizedStatus = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);
        $users = $this->usersList($viewer);
        $userOptions = $this->formatUserOptions($users);
        $canSelectRequester = $this->userCanSelectRequester($viewer);
        $unitOptions = $this->unitOptionsForUser($users, $viewer);
        $allowedStatuses = $this->allowedTicketStatuses($viewer, $ticket);

        $ticketPayload = [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'reason' => $ticket->reason,
            'letter_no' => $ticket->letter_no,
            'priority' => $ticket->priority,
            'type' => $ticket->type,
            'status' => $normalizedStatus,
            'status_id' => $ticket->status_id,
            'status_id_label' => $ticket->status_id ? ($statusLabelMap[$ticket->status_id] ?? null) : null,
            'due_at' => $this->toInputDateTime($ticket->due_at ?: $ticket->due_date),
            'finish_at' => $this->toInputDateTime($ticket->finish_at ?: $ticket->finish_date),
            'sla' => $ticket->sla,
            'requester_id' => $ticket->requester_id,
            'agent_id' => $ticket->agent_id,
            'assigned_id' => $ticket->assigned_id,
            'assigned_user_ids' => $ticket->assignedUsers->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'attachments' => $ticket->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
                'delete_url' => $this->attachmentRoute('attachments.destroy', $attachment),
            ])->values()->all(),
        ];

        return Inertia::render('Tickets/Edit', [
            'ticket' => $ticketPayload,
            'statusOptions' => $statusOptions,
            'priorityOptions' => $priorityOptions,
            'typeOptions' => $typeOptions,
            'slaOptions' => $this->slas(),
            'userOptions' => $userOptions,
            'meta' => [
                'canSelectRequester' => $canSelectRequester,
                'requesterLabel' => $this->userDisplayName($ticket->requester) ?? $this->userDisplayName($viewer),
                'from' => $request->query('from'),
                'statusGuide' => $this->statusGuidance(),
                'statusDefault' => WorkflowStatus::label(WorkflowStatus::default()),
                'lockStatus' => empty($allowedStatuses),
                'lockCoreFields' => $lockCoreFields,
                'allowedStatuses' => $allowedStatuses,
                'unitOptions' => $unitOptions,
            ],
        ]);
    }

    public function manageAttachments(Request $request, string $locale, Ticket $ticket): Response
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        $ticket->load('attachments');

        $ticketPayload = [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'title' => $ticket->title,
            'attachments' => $ticket->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
                'delete_url' => $this->attachmentRoute('attachments.destroy', $attachment),
            ])->values()->all(),
        ];

        return Inertia::render('Tickets/Attachments/Manage', [
            'ticket' => $ticketPayload,
        ]);
    }

    public function updateAttachments(Request $request, string $locale, Ticket $ticket)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        $attachIds = $request->input('attachments');
        if (empty($attachIds)) {
            $attachIds = $request->input('attachment_ids');
        }
        $this->attachments->adoptFromServerIds($attachIds, $ticket);

        return redirect()->route('tickets.attachments.manage', $ticket)->with('success', 'Lampiran diperbarui.');
    }

    public function update(Request $request, string $locale, Ticket $ticket)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        $backTo = $this->resolveBackUrl($request, route('tickets.index'));
        $ticket->loadMissing('assignedUsers');
        $previousAssigned = $this->collectTicketAssignedIds($ticket);

        if ($request->filled('due_at')) {
            try {
                $due = Carbon::parse($request->input('due_at'));
                $request->merge([
                    'due_date' => $due->format('d/m/Y'),
                    'due_time' => $due->format('H:i'),
                ]);
            } catch (\Throwable $e) {
                // ignore parsing errors
            }
        }

        // If the request only intends to update attachments, skip full validation
        $attachIdsInput = $request->input('attachments');
        if (empty($attachIdsInput)) {
            $attachIdsInput = $request->input('attachment_ids');
        }
        $updateKeys = ['title', 'description', 'reason', 'letter_no', 'priority', 'type', 'status', 'requester_id', 'agent_id', 'assigned_id', 'assigned_user_ids', 'due_date', 'finish_date', 'due_at', 'finish_at', 'sla'];
        $hasNonAttachmentPayload = false;
        foreach ($updateKeys as $k) {
            if ($request->has($k)) {
                $hasNonAttachmentPayload = true;
                break;
            }
        }
        if (! $hasNonAttachmentPayload && ! empty($attachIdsInput)) {
            try {
                Log::info('tickets.update.attachments_only', ['attachments' => $attachIdsInput, 'ticket_id' => $ticket->id]);
            } catch (\Throwable) {
            }
            $this->attachments->adoptFromServerIds($attachIdsInput, $ticket);
            try {
                Log::info('tickets.update.attachments_adopted', ['ticket_id' => $ticket->id]);
            } catch (\Throwable) {
            }

            return redirect()->to($backTo)->with('success', 'Lampiran diperbarui.');
        }

        $missingValue = '__input_missing__';
        $statusInputRaw = $request->input('status', $missingValue);
        $dueAtInputRaw = $request->input('due_at', $missingValue);
        $finishAtInputRaw = $request->input('finish_at', $missingValue);
        $dueDateInputRaw = $request->input('due_date', $missingValue);
        $finishDateInputRaw = $request->input('finish_date', $missingValue);

        $statusProvided = $statusInputRaw !== $missingValue;
        $dueAtProvided = $dueAtInputRaw !== $missingValue;
        $finishAtProvided = $finishAtInputRaw !== $missingValue;
        $dueDateProvided = $dueDateInputRaw !== $missingValue;
        $finishDateProvided = $finishDateInputRaw !== $missingValue;

        $assignedRule = $this->hasUserCode()
            ? ['nullable', 'string', 'max:64', Rule::exists('users', 'code')]
            : ['nullable', 'integer', 'min:1', Rule::exists('users', 'id')];

        $assignmentFallbacks = [
            'agent_id' => $ticket->agent_id ? (int) $ticket->agent_id : null,
            'assigned_id' => $ticket->assigned_id ? (int) $ticket->assigned_id : null,
            'assigned_user_ids' => $ticket->assignedUsers
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
        ];

        foreach ($assignmentFallbacks as $field => $fallback) {
            if (! $request->has($field)) {
                $request->merge([$field => $fallback]);
            }
        }

        $requiredFallbacks = [
            'title' => $ticket->title,
            'priority' => $ticket->priority ?? 'medium',
            'type' => $ticket->type ?? 'task',
        ];

        foreach ($requiredFallbacks as $field => $fallback) {
            if (! $request->has($field)) {
                $request->merge([$field => $fallback]);
            }
        }

        $currentStatus = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);
        $requestedStatus = WorkflowStatus::normalize($statusProvided ? ($statusInputRaw ?? $currentStatus) : $currentStatus);
        $finalStatus = $statusProvided ? $requestedStatus : $currentStatus;

        if ($statusProvided && $requestedStatus !== $currentStatus && ! $ticket->canUserSetStatus($request->user(), $requestedStatus)) {
            return back()->withErrors([
                'status' => 'Anda tidak memiliki izin untuk mengubah status tiket ini.',
            ]);
        }

        $request->merge(['status' => $finalStatus]);
        try {
            Log::info('tickets.update.status_debug', [
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()?->id,
                'status_before' => $currentStatus,
                'status_input' => $request->input('status'),
                'status_input_raw' => $statusProvided ? $statusInputRaw : null,
                'final_status' => $finalStatus,
                'status_provided' => $statusProvided,
            ]);
        } catch (\Throwable) {
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'letter_no' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', Rule::in($this->priorities())],
            'type' => ['required', Rule::in($this->types())],
            'status' => ['required', Rule::in($this->statuses())],
            'requester_id' => ['nullable', 'integer', 'min:1', Rule::exists('users', 'id')],
            'agent_id' => ['nullable', 'integer', 'min:1', Rule::exists('users', 'id')],
            'assigned_id' => $assignedRule,
            'assigned_user_ids' => ['nullable', 'array'],
            'assigned_user_ids.*' => ['integer', 'min:1', 'exists:users,id'],
            'due_date' => ['nullable', 'string'],
            'finish_date' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'finish_at' => ['nullable', 'date'],
            'sla' => ['nullable', Rule::in($this->slas())],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
        ]);
        $actingUser = $request->user();
        $canPickRequester = $this->userCanSelectRequester($actingUser);

        if ($canPickRequester) {
            if (array_key_exists('requester_id', $data)) {
                $data['requester_id'] = $data['requester_id'] ? (int) $data['requester_id'] : null;
            } else {
                $data['requester_id'] = $ticket->requester_id;
            }
        } else {
            $data['requester_id'] = $ticket->requester_id ?? ($actingUser?->id);
        }

        if (! $canPickRequester && ! $data['requester_id'] && $actingUser) {
            $data['requester_id'] = $actingUser->id;
        }
        if (! empty($data['description'])) {
            $data['description'] = $this->sanitizeDescription($data['description']);
            $data['description'] = mb_substr($data['description'], 0, 255);
        }

        if (! $dueDateProvided && ! array_key_exists('due_date', $data)) {
            $data['due_date'] = $ticket->due_date ? $ticket->due_date->format('d/m/Y') : null;
        }

        if (! $finishDateProvided && ! array_key_exists('finish_date', $data)) {
            $data['finish_date'] = $ticket->finish_date ? $ticket->finish_date->format('d/m/Y') : null;
        }

        if (! $dueAtProvided && ! array_key_exists('due_at', $data)) {
            $data['due_at'] = $ticket->due_at ? $ticket->due_at->format('Y-m-d H:i:s') : null;
        }

        if (! $finishAtProvided && ! array_key_exists('finish_at', $data)) {
            $data['finish_at'] = $ticket->finish_at ? $ticket->finish_at->format('Y-m-d H:i:s') : null;
        }

        $data['due_date'] = $this->parseDateOrNull($data['due_date'] ?? null);
        $data['finish_date'] = $this->parseDateOrNull($data['finish_date'] ?? null);

        $data['due_at'] = $this->normalizeDateTimeInput($data['due_at'] ?? $this->composeDateTime($request->input('due_date'), $request->input('due_time')));
        $data['finish_at'] = $this->normalizeDateTimeInput($data['finish_at'] ?? $this->composeDateTime($request->input('finish_date'), $request->input('finish_time')));

        if (empty($data['due_date']) && $data['due_at']) {
            $data['due_date'] = Carbon::parse($data['due_at'])->toDateString();
        }

        if (empty($data['finish_date']) && $data['finish_at']) {
            $data['finish_date'] = Carbon::parse($data['finish_at'])->toDateString();
        }

        $actingUser = $actingUser ?? $request->user();
        $lockCoreFields = $actingUser
            && ! RoleHelpers::userIsSuperAdmin($actingUser)
            && ($ticket->isRequester($actingUser) || $ticket->isAgent($actingUser));

        if ($lockCoreFields) {
            $data['title'] = $ticket->title;
            $data['priority'] = $ticket->priority ?? 'medium';
            $data['type'] = $ticket->type ?? 'task';
            $data['sla'] = $ticket->sla;
            $data['due_date'] = $ticket->due_date?->toDateString();
            $data['finish_date'] = $ticket->finish_date?->toDateString();
            $data['due_at'] = $ticket->due_at?->format('Y-m-d H:i:s');
            $data['finish_at'] = $ticket->finish_at?->format('Y-m-d H:i:s');
        }

        $assigneesInput = $request->input('assigned_user_ids', []);
        if (! is_array($assigneesInput)) {
            $assigneesInput = [$assigneesInput];
        }
        $assigneesInput = array_values(array_unique(array_filter(array_map('intval', $assigneesInput), fn ($v) => $v > 0)));
        $validIds = User::whereIn('id', $assigneesInput)->pluck('id')->all();

        $data['assigned_id'] = $validIds[0]
            ?? ((isset($data['assigned_id']) && (int) $data['assigned_id'] > 0) ? (int) $data['assigned_id'] : null);

        $data['status'] = $finalStatus;
        $data['status_id'] = WorkflowStatus::code($finalStatus);

        $projectCreated = null;
        $taskCreated = null;

        $ticket = DB::transaction(function () use ($ticket, $data, $validIds, &$projectCreated, &$taskCreated) {
            $ticket->update($data);
            $ticket->refresh();
            $ticket->assignedUsers()->sync($validIds);

            // === AUTO SYNC PROJECT
            if (($ticket->type ?? null) === 'project') {
                $projectStatus = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);

                $project = Project::firstOrCreate(
                    ['ticket_id' => $ticket->id],
                    [
                        'title' => $ticket->title,
                        'description' => $ticket->description,
                        'status' => $projectStatus,
                        'status_id' => WorkflowStatus::code($projectStatus),
                        'start_date' => $ticket->created_at?->toDateString() ?? now()->toDateString(),
                        'end_date' => $ticket->finish_date ?? $ticket->due_date,
                        'created_by' => Auth::id(),
                        'requester_id' => $ticket->requester_id ?: Auth::id(),
                    ]
                );

                if ($project->wasRecentlyCreated) {
                    $projectCreated = $project;
                }

                $this->syncProjectFromTicket($project, $ticket);

                // pastikan tidak ada Task yatim saat type=project
                Task::where('ticket_id', $ticket->id)->delete();
            } else {
                Project::where('ticket_id', $ticket->id)->delete();
            }

            // === AUTO SYNC TASK
            if (($ticket->type ?? null) === 'task') {
                $task = Task::firstOrCreate(
                    ['ticket_id' => $ticket->id],
                    $this->taskPayloadFromTicket($ticket, true)
                );

                $task->fill($this->taskPayloadFromTicket($ticket, false))->save();

                if ($task->wasRecentlyCreated) {
                    $taskCreated = $task;
                }

                // pastikan tidak ada Project yatim saat type=task
                Project::where('ticket_id', $ticket->id)->delete();
            } else {
                Task::where('ticket_id', $ticket->id)->delete();
            }

            return $ticket->refresh();
        });

        $updAttach = $request->input('attachments');
        if (empty($updAttach)) {
            $updAttach = $request->input('attachment_ids');
        }
        try {
            Log::info('tickets.update.attachments_input', ['attachments' => $updAttach]);
        } catch (\Throwable) {
        }
        $this->attachments->adoptFromServerIds($updAttach, $ticket);
        try {
            Log::info('tickets.update.attachments_adopted', ['ticket_id' => $ticket->id]);
        } catch (\Throwable) {
        }

        $notifier = app(WorkItemNotifier::class);
        $actor = Auth::user();

        $projectCreated = $projectCreated?->fresh();
        $taskCreated = $taskCreated?->fresh();

        if ($projectCreated || $taskCreated) {
            if ($projectCreated && ($ticket->type ?? null) !== 'project') {
                $notifier->notifyProjectCreated($projectCreated, $actor, true);
            }

            if ($taskCreated && ($ticket->type ?? null) !== 'task') {
                $notifier->notifyTaskCreated($taskCreated, [], $actor, true);
            }

            $notifier->notifyTicketWorkItemRouted($ticket, $taskCreated, $projectCreated, $actor);
        }

        $currentStatusAfterUpdate = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);
        if ($currentStatus !== WorkflowStatus::CANCELLED && $currentStatusAfterUpdate === WorkflowStatus::CANCELLED) {
            $notifier->notifyTicketCancelled($ticket, $actor);
        }

        $ticket->load('assignedUsers:id');
        $newAssigned = $this->collectTicketAssignedIds($ticket);
        $assignedDiff = array_values(array_diff($newAssigned, $previousAssigned));
        if (! empty($assignedDiff)) {
            $notifier->notifyTicketAssigned($ticket, $assignedDiff, $actor);
        }

        return redirect()->to($backTo)->with('success', 'Ticket updated successfully.');
    }

    public function destroy(Request $request, string $locale, Ticket $ticket)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        Project::where('ticket_id', $ticket->id)->delete();
        Task::where('ticket_id', $ticket->id)->delete();

        $ticket->delete();
        $backTo = $request->input('from', url()->previous() ?: route('tickets.index'));

        return redirect()->to($backTo)->with('success', 'Ticket deleted successfully.');
    }

    public function changeStatus(Request $request, string $locale, Ticket $ticket, string $status)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        $previousStatus = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);
        $statusNormalized = WorkflowStatus::normalize($status);
        if (! in_array($statusNormalized, $this->statuses(), true)) {
            return back()->with('error', 'Invalid status.');
        }

        if (! $ticket->canUserSetStatus($request->user(), $statusNormalized)) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengubah status tiket ini.');
        }

        $ticket->update([
            'status' => $statusNormalized,
            'status_id' => WorkflowStatus::code($statusNormalized),
        ]);

        if ($previousStatus !== WorkflowStatus::CANCELLED && $statusNormalized === WorkflowStatus::CANCELLED) {
            app(WorkItemNotifier::class)->notifyTicketCancelled($ticket, $request->user());
        }

        $backTo = $request->query('from', url()->previous() ?: route('tickets.index'));
        $label = WorkflowStatus::label($statusNormalized);

        return redirect()->to($backTo)->with('success', 'Ticket status updated to '.$label.'.');
    }

    public function onProgress(Request $request): Response
    {
        $statusScope = array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS),
            WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION)
        ));

        $origin = $this->pageOrigin($request, 'tickets.on-progress');
        $actor = $request->user();

        $ticketsQuery = Ticket::query()
            ->select(['id', 'title', 'description', 'status', 'updated_at'])
            ->whereIn('status', $statusScope);

        $ticketsQuery = UnitVisibility::scopeTickets($ticketsQuery, $actor);

        $tickets = $ticketsQuery
            ->latest('updated_at')
            ->limit(200)
            ->get()
            ->map(fn (Ticket $ticket) => $this->transformTicketOnProgress($ticket, $origin, $actor))
            ->values();

        return Inertia::render('Tickets/OnProgress', [
            'tickets' => $tickets,
        ]);
    }

    public function report(Request $request)
    {
        $filters = $this->resolveTicketReportFilters($request);
        $actor = $request->user();

        $statusOptions = collect($this->statuses())
            ->map(fn (string $status) => [
                'value' => $status,
                'label' => WorkflowStatus::label($status),
            ])
            ->values()
            ->all();

        $taskSummary = $this->ticketReportSummary($filters, 'task', $actor);
        $projectSummary = $this->ticketReportSummary($filters, 'project', $actor);

        $taskTickets = $this->ticketReportPaginator($filters, 'task', $request, $actor);
        $projectTickets = $this->ticketReportPaginator($filters, 'project', $request, $actor);

        return Inertia::render('Tickets/Report', [
            'filters' => [
                'q' => $filters['q'],
                'status' => $filters['status'],
                'from' => $filters['from'],
                'to' => $filters['to'],
            ],
            'statusOptions' => $statusOptions,
            'taskSummary' => $taskSummary,
            'projectSummary' => $projectSummary,
            'taskTickets' => $taskTickets,
            'projectTickets' => $projectTickets,
        ]);
    }

    public function downloadReport(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
        ];

        $statusNormalized = $filters['status'] ? WorkflowStatus::normalize($filters['status']) : null;
        if ($statusNormalized && ! in_array($statusNormalized, $this->statuses(), true)) {
            $statusNormalized = null;
        }

        $userColumns = $this->userSelectColumns();
        $actor = $request->user();

        $ticketsQuery = Ticket::query()
            ->select([
                'id',
                'ticket_no',
                'title',
                'priority',
                'type',
                'status',
                'status_id',
                'due_at',
                'due_date',
                'updated_at',
                'requester_id',
            ])
            ->with([
                'requester' => fn ($query) => $query->select($userColumns),
            ]);

        $ticketsQuery = UnitVisibility::scopeTickets($ticketsQuery, $actor);

        $tickets = $ticketsQuery
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $q = $filters['q'];
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('ticket_no', 'like', "%{$q}%");
                });
            })
            ->when($statusNormalized, fn ($query) => $query->where('status', $statusNormalized))
            ->orderByDesc('created_at')
            ->get();

        $tz = config('app.timezone');
        $statusLabel = $statusNormalized ? WorkflowStatus::label($statusNormalized) : 'Semua';

        $rows = $tickets->map(function (Ticket $ticket) use ($tz) {
            $status = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);

            return [
                $ticket->ticket_no ?? '—',
                $ticket->title ?? '—',
                WorkflowStatus::label($status),
                ucfirst($ticket->priority ?? '—'),
                ucfirst($ticket->type ?? '—'),
                $this->formatDate($ticket->due_at ?: $ticket->due_date, 'd M Y H:i', $tz),
                $this->formatDate($ticket->updated_at, 'd M Y H:i', $tz),
                $this->userDisplayName($ticket->requester),
            ];
        })->toArray();

        $columns = [
            ['label' => 'Ticket No'],
            ['label' => 'Judul'],
            ['label' => 'Status'],
            ['label' => 'Prioritas'],
            ['label' => 'Jenis'],
            ['label' => 'Jatuh Tempo'],
            ['label' => 'Diperbarui'],
            ['label' => 'Requester'],
        ];

        $meta = [
            'filters' => [
                'Pencarian' => $filters['q'] !== '' ? $filters['q'] : 'Semua',
                'Status' => $statusLabel,
                'Total Data' => number_format($tickets->count()),
            ],
        ];

        $filename = sprintf('tickets-report-%s.pdf', now()->format('Ymd-His'));

        return $this->reportExport->downloadPdf('Laporan Tiket', $columns, $rows, $meta, $filename);
    }

    private function ticketReportSummary(array $filters, string $type, ?User $viewer = null): array
    {
        $query = $this->buildTicketReportQuery($filters, $type, false, $viewer);

        $total = (clone $query)->count();
        $inProgress = (clone $query)->whereIn('status', WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS))->count();
        $done = (clone $query)->whereIn('status', WorkflowStatus::equivalents(WorkflowStatus::DONE))->count();

        return [
            'total' => $total,
            'in_progress' => $inProgress,
            'done' => $done,
        ];
    }

    private function ticketReportPaginator(array $filters, string $type, Request $request, ?User $viewer = null)
    {
        $perPageParam = $type === 'task' ? 'task_per_page' : 'project_per_page';
        $pageName = $type === 'task' ? 'task_page' : 'project_page';

        $perPage = (int) $request->integer($perPageParam, 15);
        $perPage = min(max($perPage, 5), 50);

        $origin = $this->pageOrigin($request, 'tickets.report');

        $query = $this->buildTicketReportQuery($filters, $type, true, $viewer);

        return $query
            ->paginate($perPage, ['*'], $pageName)
            ->appends($this->ticketReportQueryParams($filters))
            ->through(fn (Ticket $ticket) => $this->transformTicketReportRow($ticket, $origin));
    }

    private function buildTicketReportQuery(array $filters, string $type, bool $withRelations = false, ?User $viewer = null): Builder
    {
        $query = Ticket::query()
            ->select([
                'id',
                'ticket_no',
                'title',
                'description',
                'reason',
                'letter_no',
                'priority',
                'type',
                'status',
                'status_id',
                'requester_id',
                'agent_id',
                'assigned_id',
                'due_date',
                'due_at',
                'finish_date',
                'finish_at',
                'sla',
                'created_at',
                'updated_at',
            ])
            ->where('type', $type);

        $query = UnitVisibility::scopeTickets($query, $viewer);

        if ($withRelations) {
            $userColumns = $this->userRelationColumns();
            $taskColumns = $this->taskRelationColumns();
            $projectColumns = $this->projectRelationColumns();
            $query->with([
                'requester:'.$userColumns,
                'agent:'.$userColumns,
                'assignee:'.$userColumns,
                'assignedUsers:'.$userColumns,
                'project:'.$projectColumns,
                'tasks:'.$taskColumns,
                'tasks.assignee:'.$userColumns,
                'attachments:id,attachable_id,attachable_type,original_name,size',
            ]);

            $query->withCount(['tasks', 'projects']);
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('ticket_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($filters['normalized_status']) {
            $query->where('status', $filters['normalized_status']);
        }

        if ($filters['from_date']) {
            $from = $filters['from_date'];
            $query->where(function (Builder $builder) use ($from) {
                $builder->whereDate('due_at', '>=', $from)
                    ->orWhereDate('due_date', '>=', $from);
            });
        }

        if ($filters['to_date']) {
            $to = $filters['to_date'];
            $query->where(function (Builder $builder) use ($to) {
                $builder->whereDate('due_at', '<=', $to)
                    ->orWhereDate('due_date', '<=', $to);
            });
        }

        return $query->orderByDesc('created_at');
    }

    private function transformTicketReportRow(Ticket $ticket, string $origin): array
    {
        $status = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);
        $tz = config('app.timezone');
        $statusLabelMap = $this->statusMap();

        $requesterName = $this->userDisplayName($ticket->requester);
        $agentName = $this->userDisplayName($ticket->agent);
        $assigneeName = $this->userDisplayName($ticket->assignee);
        $assignedUsers = $ticket->relationLoaded('assignedUsers')
            ? $ticket->assignedUsers->map(fn ($user) => [
                'id' => $user->id,
                'name' => $this->userDisplayName($user),
                'email' => $user->email ?? null,
            ])->values()->all()
            : [];

        $tasks = $ticket->relationLoaded('tasks')
            ? $ticket->tasks->map(function (Task $task) use ($tz) {
                $taskStatus = WorkflowStatus::normalize($task->status ?? null);

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $taskStatus,
                    'status_label' => WorkflowStatus::label($taskStatus),
                    'status_badge' => WorkflowStatus::badgeClass($taskStatus),
                    'assignee' => $task->assignee ? $this->userDisplayName($task->assignee) : null,
                    'due_display' => $this->formatDate($task->due_at ?? $task->due_date, 'd M Y H:i', $tz),
                    'links' => [
                        'show' => route('tasks.show', ['taskSlug' => $task->public_slug]),
                    ],
                ];
            })->values()->all()
            : [];

        $project = null;
        if ($ticket->relationLoaded('project') && $ticket->project) {
            $projectStatus = WorkflowStatus::normalize($ticket->project->status ?? null);
            $project = [
                'id' => $ticket->project->id,
                'title' => $ticket->project->title,
                'project_no' => $ticket->project->project_no,
                'status' => $projectStatus,
                'status_label' => WorkflowStatus::label($projectStatus),
                'status_badge' => WorkflowStatus::badgeClass($projectStatus),
                'start_display' => $this->formatDate($ticket->project->start_date, 'd/m/Y', $tz),
                'end_display' => $this->formatDate($ticket->project->end_date, 'd/m/Y', $tz),
                'links' => [
                    'show' => route('projects.show', [
                        'project' => $ticket->project->public_slug,
                    ]),
                ],
            ];
        }

        $attachments = $ticket->relationLoaded('attachments')
            ? $ticket->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
            ])->values()->all()
            : [];

        $normalizedOrigin = $this->normalizeInternalUrl($origin);

        return [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'title' => $ticket->title,
            'description' => $ticket->description ? $this->sanitizeDescription($ticket->description) : null,
            'priority' => $ticket->priority,
            'priority_label' => ucfirst($ticket->priority ?? '—'),
            'type' => $ticket->type,
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'status_badge' => WorkflowStatus::badgeClass($status),
            'status_id' => $ticket->status_id,
            'status_id_label' => $ticket->status_id ? ($statusLabelMap[$ticket->status_id] ?? null) : null,
            'reason' => $ticket->reason,
            'letter_no' => $ticket->letter_no,
            'due_display' => $this->formatDate($ticket->due_at ?? $ticket->due_date, 'd M Y H:i', $tz),
            'finish_display' => $this->formatDate($ticket->finish_at ?? $ticket->finish_date, 'd M Y H:i', $tz),
            'created_display' => $this->formatDate($ticket->created_at, 'd M Y H:i', $tz),
            'updated_display' => $this->formatDate($ticket->updated_at, 'd M Y H:i', $tz),
            'sla' => $ticket->sla,
            'sla_label' => $this->formatSlaLabel($ticket->sla),
            'requester' => $requesterName ? [
                'name' => $requesterName,
            ] : null,
            'agent' => $agentName ? ['name' => $agentName] : null,
            'assignee' => $assigneeName ? ['name' => $assigneeName] : null,
            'assigned_users' => $assignedUsers,
            'tasks' => $tasks,
            'project' => $project,
            'attachments' => $attachments,
            'tasks_count' => (int) ($ticket->tasks_count ?? ($ticket->relationLoaded('tasks') ? $ticket->tasks->count() : 0)),
            'projects_count' => (int) ($ticket->projects_count ?? ($ticket->relationLoaded('projects') ? $ticket->projects->count() : 0)),
            'links' => [
                'show' => $this->ticketReportDetailUrl($ticket),
                'edit' => route('tickets.edit', [
                    'ticket' => $ticket->id,
                ]),
                'delete' => route('tickets.destroy', [
                    'ticket' => $ticket->id,
                ]),
            ],
        ];
    }

    private function ticketReportQueryParams(array $filters): array
    {
        return array_filter([
            'q' => $filters['q'] ?? null,
            'status' => $filters['status'] ?? null,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
        ], fn ($value) => filled($value));
    }

    private function resolveTicketReportFilters(Request $request): array
    {
        $statusInput = $request->query('status');
        $normalizedStatus = $statusInput ? WorkflowStatus::normalize($statusInput) : null;
        if ($normalizedStatus && ! in_array($normalizedStatus, $this->statuses(), true)) {
            $normalizedStatus = null;
        }

        $from = (string) ($request->query('from') ?? '');
        $to = (string) ($request->query('to') ?? '');

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) ($statusInput ?? ''),
            'normalized_status' => $normalizedStatus,
            'from' => $from,
            'to' => $to,
            'from_date' => $this->parseTicketReportDate($from, true),
            'to_date' => $this->parseTicketReportDate($to, false),
        ];
    }

    private function parseTicketReportDate(?string $value, bool $startOfDay = true): ?Carbon
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

    private function taskRelationColumns(): string
    {
        $columns = ['id', 'title', 'status', 'created_at', 'updated_at'];

        foreach (['ticket_id', 'assignee_id', 'due_at', 'due_date', 'task_no', 'priority'] as $column) {
            if (Schema::hasColumn('tasks', $column)) {
                $columns[] = $column;
            }
        }

        return implode(',', array_unique($columns));
    }

    private function projectRelationColumns(): string
    {
        $columns = ['id', 'projects.ticket_id', 'title', 'project_no', 'status', 'start_date', 'end_date', 'created_at', 'updated_at'];
        if (Schema::hasColumn('projects', 'status_id')) {
            $columns[] = 'status_id';
        }

        return implode(',', array_unique($columns));
    }

    public function downloadDetail(Request $request, string $locale, Ticket $ticket)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        $ticket->load([
            'attachments:id,attachable_id,attachable_type,original_name,size',
            'requester',
            'agent',
            'assignedUsers',
            'project',
            'tasks.assignee',
        ]);

        $detail = $this->transformTicketDetail($ticket, route('tickets.index'));

        $tz = config('app.timezone');

        $timeline = [
            'created_at' => $this->formatDate($ticket->created_at, 'd M Y H:i', $tz),
            'updated_at' => $this->formatDate($ticket->updated_at, 'd M Y H:i', $tz),
            'due_at' => $this->formatDate($ticket->due_at ?: $ticket->due_date, 'd M Y H:i', $tz),
            'finish_at' => $this->formatDate($ticket->finish_at ?: $ticket->finish_date, 'd M Y H:i', $tz),
        ];

        $description = $this->sanitizeDescription((string) ($detail['description'] ?? ''));
        if (trim(strip_tags($description)) === '') {
            $description = '<p>Tidak ada deskripsi.</p>';
        }

        $assignedUsers = $detail['assigned_users'] ?? [];

        $project = null;
        if ($ticket->relationLoaded('project') && $ticket->project) {
            $projectStatus = $ticket->project->status ? WorkflowStatus::label(WorkflowStatus::normalize($ticket->project->status)) : null;
            $projectStart = $ticket->project->start_date ?? $ticket->project->created_at;
            $projectEnd = $ticket->project->end_date ?? $ticket->project->updated_at;

            $project = [
                'title' => $ticket->project->title,
                'project_no' => $ticket->project->project_no,
                'status' => $projectStatus,
                'timeline' => $this->buildRangeLabel($projectStart, $projectEnd),
            ];
        }

        $tasks = $ticket->relationLoaded('tasks') ? $ticket->tasks->map(function (Task $task) use ($tz) {
            $status = WorkflowStatus::normalize($task->status ?? null);
            $assignee = $task->assignee ? ($task->assignee->display_name ?? $task->assignee->email) : null;

            return [
                'title' => $task->title,
                'status_label' => WorkflowStatus::label($status),
                'assignee' => $assignee,
                'due_at' => $this->formatDate($task->due_at ?: $task->due_date, 'd M Y H:i', $tz),
            ];
        })->values()->all() : [];

        $attachments = collect($detail['attachments'] ?? [])->map(function (array $attachment) {
            return [
                'name' => $attachment['name'] ?? '—',
                'size' => $this->formatFileSize($attachment['size'] ?? null),
            ];
        })->values()->all();

        $filename = sprintf(
            'ticket-%s-detail.pdf',
            $ticket->ticket_no ? Str::slug($ticket->ticket_no) : $ticket->id
        );

        return $this->reportExport->downloadDetailPdf('reports.pdf.ticket-detail', [
            'title' => $detail['title'] ?? 'Detail Ticket',
            'ticket' => $detail,
            'timeline' => $timeline,
            'description' => $description,
            'assignedUsers' => $assignedUsers,
            'project' => $project,
            'tasks' => $tasks,
            'attachments' => $attachments,
        ], $filename);
    }

    private function transformTicketSummary(Ticket $ticket, string $origin, array $statusLabelMap): array
    {
        $status = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);
        $statusLabel = WorkflowStatus::label($status);

        $requesterName = $this->userDisplayName($ticket->requester);

        $normalizedOrigin = $this->normalizeInternalUrl($origin);

        return [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'title' => $ticket->title,
            'priority' => $ticket->priority,
            'type' => $ticket->type,
            'status' => $status,
            'status_label' => $statusLabel,
            'status_id' => $ticket->status_id,
            'status_id_label' => $ticket->status_id ? ($statusLabelMap[$ticket->status_id] ?? null) : null,
            'due_at' => $this->toIsoString($ticket->due_at ?: $ticket->due_date),
            'updated_at' => $this->toIsoString($ticket->updated_at),
            'requester' => $requesterName ? [
                'id' => $ticket->requester?->id,
                'name' => $requesterName,
            ] : null,
            'links' => [
                'show' => $this->ticketReportDetailUrl($ticket),
                'edit' => route('tickets.edit', ['ticket' => $ticket->id]),
                'delete' => route('tickets.destroy', ['ticket' => $ticket->id]),
            ],
        ];
    }

    private function transformTicketOnProgress(Ticket $ticket, string $origin, ?User $actor): array
    {
        $status = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);
        $statusLabel = WorkflowStatus::label($status);
        $normalizedOrigin = $this->normalizeInternalUrl($origin);

        $statusActions = collect([
            WorkflowStatus::IN_PROGRESS,
            WorkflowStatus::CONFIRMATION,
            WorkflowStatus::REVISION,
            WorkflowStatus::DONE,
            WorkflowStatus::NEW,
        ])->filter(fn (string $candidate) => $actor && $ticket->canUserSetStatus($actor, $candidate))
            ->map(fn (string $candidate) => [
                'value' => $candidate,
                'label' => WorkflowStatus::label($candidate),
                'url' => route('tickets.status.change', [
                    'locale' => app()->getLocale(),
                    'ticket' => $ticket->id,
                    'status' => $candidate,
                ]),
            ])
            ->values()
            ->all();

        return [
            'id' => $ticket->id,
            'title' => $ticket->title,
            'description' => strip_tags((string) $ticket->description),
            'status' => $status,
            'status_label' => $statusLabel,
            'updated_diff' => $ticket->updated_at?->diffForHumans(),
            'links' => [
                'show' => $this->ticketReportDetailUrl($ticket),
            ],
            'status_actions' => $statusActions,
        ];
    }

    private function userDisplayName(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $full = trim(implode(' ', array_filter([$user->first_name ?? null, $user->last_name ?? null])));
        if ($full !== '') {
            return $full;
        }

        foreach (['name', 'full_name', 'username', 'email'] as $attr) {
            $value = $user->{$attr} ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return 'User #'.$user->id;
    }

    private function pageOrigin(Request $request, string $fallbackRoute): string
    {
        $uri = $request->getRequestUri();
        if (is_string($uri) && $uri !== '') {
            return $uri;
        }

        return $this->normalizeInternalUrl(route($fallbackRoute));
    }

    private function transformTicketDetail(Ticket $ticket, string $backUrl): array
    {
        $statusLabelMap = $this->statusMap();
        $normalizedStatus = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);
        $statusLabel = WorkflowStatus::label($normalizedStatus);

        $projects = $ticket->relationLoaded('projects')
            ? $ticket->projects->map(function ($project) {
                $status = WorkflowStatus::normalize($project->status ?? WorkflowStatus::NEW);

                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'project_no' => $project->project_no,
                    'status' => $status,
                    'status_label' => WorkflowStatus::label($status),
                    'status_badge' => WorkflowStatus::badgeClass($status),
                    'due_display' => optional($project->end_date)->format('d/m/Y'),
                    'updated_display' => optional($project->updated_at)->format('d M Y H:i'),
                    'links' => [
                        'show' => route('projects.show', ['project' => $project->public_slug ?? $project->id]),
                        'edit' => route('projects.edit', ['project' => $project->public_slug ?? $project->id]),
                    ],
                ];
            })->values()->all()
            : [];

        return [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'priority' => $ticket->priority,
            'type' => $ticket->type,
            'status' => $normalizedStatus,
            'status_label' => $statusLabel,
            'status_id' => $ticket->status_id,
            'status_id_label' => $statusLabelMap[$ticket->status_id] ?? null,
            'sla' => $ticket->sla,
            'reason' => $ticket->reason,
            'letter_no' => $ticket->letter_no,
            'timeline' => [
                'created_at' => $this->toIsoString($ticket->created_at),
                'updated_at' => $this->toIsoString($ticket->updated_at),
                'due_at' => $this->toIsoString($ticket->due_at ?: $ticket->due_date),
                'finish_at' => $this->toIsoString($ticket->finish_at ?: $ticket->finish_date),
            ],
            'requester' => $ticket->requester ? [
                'id' => $ticket->requester->id,
                'name' => $this->userDisplayName($ticket->requester),
                'email' => $ticket->requester->email,
            ] : null,
            'agent' => $ticket->agent ? [
                'id' => $ticket->agent->id,
                'name' => $this->userDisplayName($ticket->agent),
                'email' => $ticket->agent->email,
            ] : null,
            'assigned_users' => $ticket->assignedUsers->map(fn ($user) => [
                'id' => $user->id,
                'name' => $this->userDisplayName($user),
                'email' => $user->email,
            ])->values()->all(),
            'attachments' => $ticket->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
            ])->values()->all(),
            'projects' => $projects,
            'links' => [
                'index' => $this->normalizeInternalUrl(route('tickets.index')),
                'edit' => route('tickets.edit', [
                    'ticket' => $ticket->id,
                ]),
                'detail' => $this->ticketReportDetailUrl($ticket),
                'pdf' => route('tickets.report.detail', ['ticket' => $ticket->id]),
            ],
        ];
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

    private function syncProjectFromTicket(Project $project, Ticket $ticket): void
    {
        $status = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);

        $payload = [
            'title' => $ticket->title,
            'description' => $ticket->description,
            'status' => $status,
            'status_id' => WorkflowStatus::code($status),
            'end_date' => $ticket->finish_date ?? $ticket->due_date ?? $project->end_date,
        ];

        if (! $project->start_date) {
            $payload['start_date'] = $ticket->created_at?->toDateString() ?? now()->toDateString();
        }

        if ($ticket->requester_id) {
            $payload['requester_id'] = $ticket->requester_id;
        } elseif (! $project->requester_id && $ticket->relationLoaded('requester') && $ticket->requester) {
            $payload['requester_id'] = $ticket->requester->id;
        }

        $project->fill(array_filter($payload, fn ($value) => $value !== null))->save();
    }

    /**
     * @return array<int>
     */
    private function collectTicketAssignedIds(Ticket $ticket): array
    {
        $ids = [];

        $assignedUsers = $ticket->relationLoaded('assignedUsers')
            ? $ticket->assignedUsers
            : $ticket->assignedUsers()->get(['users.id']);

        foreach ($assignedUsers as $user) {
            $ids[] = (int) $user->id;
        }

        if ($ticket->assigned_id) {
            $ids[] = (int) $ticket->assigned_id;
        }

        return array_values(array_unique($ids));
    }

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

    private function toIsoString($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $dt = $value instanceof Carbon ? $value : Carbon::parse($value);

            return $dt->toISOString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatSlaLabel(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Str::of($value)
            ->replace('_', ' ')
            ->lower()
            ->title()
            ->value();
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

    private function buildRangeLabel($start, $end): string
    {
        $tz = config('app.timezone');
        $startLabel = $this->formatDate($start, 'd M Y', $tz);
        $endLabel = $this->formatDate($end, 'd M Y', $tz);

        if ($startLabel === '—' && $endLabel === '—') {
            return '—';
        }

        if ($startLabel === '—') {
            return 'Sampai '.$endLabel;
        }

        if ($endLabel === '—') {
            return 'Mulai '.$startLabel;
        }

        return $startLabel.' - '.$endLabel;
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
}
