<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\ReportExportService;
use App\Services\TicketLifecycle;
use App\Services\WorkItemNotifier;
use App\Support\RoleHelpers;
use App\Support\UnitVisibility;
use App\Support\UserUnitOptions;
use App\Support\WorkflowStatus;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(
        private AttachmentService $attachments,
        private ReportExportService $reportExport,
        private TicketLifecycle $lifecycle
    ) {}

    private function statuses(): array
    {
        return WorkflowStatus::all();
    }

    private function ticketReportDetailUrl(Ticket $ticket): string
    {
        $locale = request()->route('locale') ?? app()->getLocale();
        $ticketNo = is_string($ticket->ticket_no ?? null) ? trim((string) $ticket->ticket_no) : '';
        if ($ticketNo !== '') {
            return route('tickets.report.detail.view', ['locale' => $locale, 'ticket' => $ticketNo]);
        }

        return route('tickets.edit', ['locale' => $locale, 'ticket' => $ticket->id]);
    }

    private function priorities(): array
    {
        return ['high', 'medium', 'low'];
    }

    private function hasUserCode(): bool
    {
        return Schema::hasColumn('users', 'code');
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
        if (! $viewer || $ticket?->status === WorkflowStatus::DONE) {
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
            'agent' => __('Status In Progress dan Confirmation hanya dapat diubah oleh agent atau assigned user.'),
            'requester' => __('Status Revision, Done, Cancelled, dan On Hold hanya dapat diubah oleh Requester.'),
            'admin' => __('Hanya Super Admin yang dapat mengubah seluruh status di luar aturan di atas.'),
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


    public function index(Request $request): RedirectResponse
    {
        $query = $request->getQueryString();
        $target = route('tickets.report', ['locale' => $request->route('locale') ?? app()->getLocale()]);

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
                'label' => $this->reportValueLabel($priority),
            ])
            ->values();

        $viewer = $request->user();
        $users = $this->usersList($viewer);
        $userOptions = $this->formatUserOptions($users);
        $canSelectRequester = $this->userCanSelectRequester($viewer);
        $unitOptions = $this->unitOptionsForUser($users, $viewer);
        $allowedStatuses = $this->allowedTicketStatuses($viewer);
        $lockStatus = empty($allowedStatuses);

        $defaults = [
            'priority' => null,
            'status' => WorkflowStatus::normalize(WorkflowStatus::default()),
            'due_at' => null,
            'requester_id' => $viewer?->id,
        ];

        if ($request->filled('template')) {
            $request->validate(['template' => ['integer']]);
            $template = \App\Models\KnowledgeEntry::visibleTo($viewer)->where('kind', 'template')->where('published', true)->findOrFail($request->integer('template'));
            $defaults['template_id'] = $template->id;
            $defaults['title'] = $template->title;
            $defaults['description'] = '<p>'.nl2br(e($template->body)).'</p>';
            if ($template->default_assignee_id && $users->contains('id', $template->default_assignee_id)) {
                $defaults['assigned_user_ids'] = [(int) $template->default_assignee_id];
                $defaults['assignment_reason'] = __('PIC disarankan oleh template :template. Anda dapat mengubahnya.', ['template' => $template->title]);
            }
        }

        return Inertia::render('Tickets/Create', [
            'statusOptions' => $statusOptions,
            'priorityOptions' => $priorityOptions,
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
                'minTicketDate' => now(config('app.timezone'))->toDateString(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $currentStatus = WorkflowStatus::default();
        $today = now(config('app.timezone'))->startOfDay()->toDateString();
        $request->merge([
            'agent_id' => null,
            'description' => (string) $request->input('description', ''),
            'reason' => mb_substr((string) $request->input('reason', ''), 0, 255),
            'letter_no' => mb_substr((string) $request->input('letter_no', ''), 0, 255),
        ]);

        // The create form and assigned_user_ids both submit the database user ID.
        // Validating assigned_id against users.code made every selected PIC fail
        // validation on installations that also have a code column.
        $submittedAssignedId = filter_var($request->input('assigned_id'), FILTER_VALIDATE_INT);
        $request->merge([
            'assigned_id' => $submittedAssignedId && User::whereKey($submittedAssignedId)->exists()
                ? (int) $submittedAssignedId
                : null,
        ]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:20000'],
            'reason' => ['nullable', 'string', 'max:255'],
            'letter_no' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', Rule::in($this->priorities())],
            'status' => ['nullable', Rule::in($this->statuses())],
            'requester_id' => ['nullable', 'integer', 'min:1', Rule::exists('users', 'id')],
            'agent_id' => ['nullable'],
            'assigned_id' => ['nullable', 'integer', 'min:1'],
            'assigned_user_ids' => ['nullable', 'array'],
            'assigned_user_ids.*' => ['integer', 'min:1', 'exists:users,id'],
            'due_at' => ['required', 'date', 'after_or_equal:'.$today],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
            'submission_token' => ['required', 'uuid'],
        ]);

        $template = null;
        if ($request->filled('template_id')) {
            $request->validate(['template_id' => ['integer']]);
            $template = \App\Models\KnowledgeEntry::visibleTo($request->user())->where('kind', 'template')->where('published', true)->findOrFail($request->integer('template_id'));
        }
        $actingUser = $request->user();
        $submissionToken = $data['submission_token'];
        unset($data['submission_token']);
        $canPickRequester = $this->userCanSelectRequester($actingUser);

        if ($canPickRequester) {
            $data['requester_id'] = $data['requester_id'] ?? ($actingUser?->id);
        } else {
            $data['requester_id'] = $actingUser?->id;
        }

        $data['requester_id'] = $data['requester_id'] ? (int) $data['requester_id'] : null;
        $data['agent_id'] = null;
        if (! empty($data['description'])) {
            $data['description'] = $this->sanitizeDescription($data['description']);
        }

        $deadline = $this->parseDeadline($data['due_at']);
        if ($deadline->lt(now())) {
            throw ValidationException::withMessages(['due_at' => __('Deadline harus berada di masa mendatang.')]);
        }
        $data['due_at'] = $deadline;
        $data['due_date'] = $deadline->toDateString();

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

        $idempotencyKey = sprintf('tickets:store:%d:%s', (int) ($actingUser?->id ?? 0), $submissionToken);
        $lock = Cache::lock($idempotencyKey.':lock', 30);
        if (! $lock->get()) {
            return back()->with('error', __('Penyimpanan ticket sedang diproses.'));
        }

        try {
            $existingTicketId = Cache::get($idempotencyKey);
            if ($existingTicketId && ($existingTicket = Ticket::find($existingTicketId))) {
                return redirect()->route('tickets.create', ['locale' => app()->getLocale()])
                    ->with('success', __('Ticket sudah tersimpan.'));
            }

            $ticket = DB::transaction(function () use ($data, $validIds, $template) {
                $ticket = Ticket::create($data);
                $ticket->assignedUsers()->sync($validIds);
                foreach ($template?->checklist ?? [] as $title) {
                    DB::table('ticket_checklist_items')->insert(['ticket_id' => $ticket->id, 'title' => $title, 'done' => false, 'created_at' => now(), 'updated_at' => now()]);
                }

                return $ticket;
            });

            Cache::put($idempotencyKey, $ticket->id, now()->addMinutes(10));
        } finally {
            $lock->release();
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

        app(WorkItemNotifier::class)->notifyTicketCreated($ticket, $request->user());

        return redirect()->route('tickets.create', ['locale' => app()->getLocale()])
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
        $this->loadTicketDetailRelations($ticket);

        $backUrl = $this->resolveBackUrl($request, routeLocale('tickets.index'));
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
                'label' => $this->reportValueLabel($priority),
            ])
            ->values();

        $viewer = $request->user();
        $lockCoreFields = $viewer
            && ! RoleHelpers::userIsSuperAdmin($viewer)
            && ($ticket->isRequester($viewer) || $ticket->isAgent($viewer));
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
            'status' => $normalizedStatus,
            'status_id' => $ticket->status_id,
            'status_id_label' => WorkflowStatus::label($normalizedStatus),
            'due_at' => $this->toInputDateTime($ticket->deadline()),
            'deadline_at' => $this->toIsoString($ticket->deadline()),
            'worked_on_at' => $this->toIsoString($ticket->worked_on_at),
            'created_at' => $this->toIsoString($ticket->created_at),
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
                'delete_url' => $attachment->ticket_confirmation_id ? null : $this->attachmentRoute('attachments.destroy', $attachment),
            ])->values()->all(),
        ];

        return Inertia::render('Tickets/Edit', [
            'ticket' => $ticketPayload,
            'statusOptions' => $statusOptions,
            'priorityOptions' => $priorityOptions,
            'userOptions' => $userOptions,
            'meta' => [
                'canSelectRequester' => $canSelectRequester,
                'requesterLabel' => $this->userDisplayName($ticket->requester) ?? $this->userDisplayName($viewer),
                'from' => $request->query('from'),
                'statusGuide' => $this->statusGuidance(),
                'statusDefault' => WorkflowStatus::label(WorkflowStatus::default()),
                'lockStatus' => empty($allowedStatuses),
                'lockCoreFields' => $lockCoreFields,
                'canEditDeadline' => $this->lifecycle->canEditDeadline($ticket, $viewer),
                'minTicketDate' => now()->toDateString(),
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
                'delete_url' => $attachment->ticket_confirmation_id ? null : $this->attachmentRoute('attachments.destroy', $attachment),
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

        return redirect()->route('tickets.attachments.manage', $ticket)->with('success', __('Lampiran diperbarui.'));
    }

    public function update(Request $request, string $locale, Ticket $ticket)
    {
        $actor = $request->user();
        UnitVisibility::ensureTicketAccess($actor, $ticket);
        $backTo = $this->resolveBackUrl($request, routeLocale('tickets.index'));
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:20000'],
            'reason' => ['nullable', 'string', 'max:255'],
            'letter_no' => ['nullable', 'string', 'max:255'],
            'priority' => ['sometimes', 'required', Rule::in($this->priorities())],
            'status' => ['sometimes', 'required', Rule::in($this->statuses())],
            'requester_id' => ['nullable', 'integer', 'exists:users,id'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_user_ids' => ['nullable', 'array'],
            'assigned_user_ids.*' => ['integer', 'exists:users,id'],
            'due_at' => ['sometimes', 'required', 'date'],
            'confirmation_note' => ['nullable', 'string', 'max:4000'],
            'evidence' => ['nullable', 'array', 'max:10'],
            'evidence.*' => ['uuid', 'distinct'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
        ]);

        [$ticket, $previousStatus, $previousAssigned] = DB::transaction(function () use ($ticket, $data, $actor) {
            $current = Ticket::lockForUpdate()->findOrFail($ticket->id);
            $current->load('assignedUsers');
            $previousStatus = $current->status;
            $previousAssigned = $this->collectTicketAssignedIds($current);
            $fields = \Illuminate\Support\Arr::only($data, ['title', 'description', 'reason', 'letter_no', 'priority', 'requester_id', 'agent_id', 'assigned_id']);
            if (! $this->userCanSelectRequester($actor)) {
                unset($fields['requester_id']);
                if ($current->isRequester($actor) || $current->isAgent($actor)) {
                    unset($fields['title'], $fields['priority']);
                }
            }
            if (! empty($fields['description'])) {
                $fields['description'] = $this->sanitizeDescription($fields['description']);
            }
            if (isset($data['due_at'])) {
                $this->lifecycle->changeDeadline($current, $actor, $this->parseDeadline($data['due_at']));
            }
            if (isset($data['status'])) {
                $this->lifecycle->changeStatus($current, $actor, $data['status'], $data);
            }

            if (array_key_exists('assigned_user_ids', $data)) {
                $ids = array_values(array_unique($data['assigned_user_ids'] ?? []));
                $fields['assigned_id'] = in_array($fields['assigned_id'] ?? null, $ids) ? $fields['assigned_id'] : ($ids[0] ?? null);
                $current->assignedUsers()->sync($ids);
            }
            $current->fill($fields)->save();

            return [$current->load('assignedUsers'), $previousStatus, $previousAssigned];
        });

        $this->attachments->adoptFromServerIds($data['attachments'] ?? $request->input('attachment_ids'), $ticket);
        $notifier = app(WorkItemNotifier::class);
        if ($previousStatus !== WorkflowStatus::CANCELLED && $ticket->status === WorkflowStatus::CANCELLED) {
            $notifier->notifyTicketCancelled($ticket, $actor);
        }
        $assignedDiff = array_values(array_diff($this->collectTicketAssignedIds($ticket), $previousAssigned));
        if ($assignedDiff) {
            $notifier->notifyTicketAssigned($ticket, $assignedDiff, $actor);
        }

        return redirect()->to($backTo)->with('success', __('Ticket updated successfully.'));
    }

    private function parseDeadline(string $value): Carbon
    {
        $date = Carbon::parse($value)->setTimezone(config('app.timezone'));

        return preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) ? $date->endOfDay()->setMicrosecond(0) : $date;
    }

    public function destroy(Request $request, string $locale, Ticket $ticket)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);

        $ticket->delete();
        $backTo = $request->input('from', url()->previous() ?: route('tickets.index', ['locale' => app()->getLocale()]));

        return redirect()->to($backTo)->with('success', 'Ticket deleted successfully.');
    }

    public function changeStatus(Request $request, string $locale, Ticket $ticket, string $status)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        abort_unless(in_array($status, WorkflowStatus::all(), true), 422, __('Status tidak valid.'));
        $input = $request->validate([
            'confirmation_note' => ['nullable', 'string', 'max:4000'],
            'evidence' => ['nullable', 'array', 'max:10'],
            'evidence.*' => ['uuid', 'distinct'],
        ]);
        $cancelled = DB::transaction(function () use ($ticket, $status, $request, $input): bool {
            $current = Ticket::lockForUpdate()->findOrFail($ticket->id);
            $previous = $current->status;
            $this->lifecycle->changeStatus($current, $request->user(), $status, $input);
            $current->save();

            return $previous !== WorkflowStatus::CANCELLED && $status === WorkflowStatus::CANCELLED;
        });
        if ($cancelled) {
            app(WorkItemNotifier::class)->notifyTicketCancelled($ticket->refresh(), $request->user());
        }

        $backTo = $this->resolveBackUrl($request, route('tickets.index', ['locale' => app()->getLocale()]));
        $label = WorkflowStatus::label($status);

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

        /** @var Builder $ticketsQuery */
        $ticketsQuery = Ticket::query()
            ->select(['id', 'ticket_no', 'title', 'description', 'status', 'updated_at', 'requester_id', 'agent_id', 'assigned_id'])
            ->with('assignedUsers:id')
            ->whereIn('status', $statusScope);

        /** @var Builder $ticketsQuery */
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

        $summary = $this->ticketReportSummary($filters, $actor);
        $tickets = $this->ticketReportPaginator($filters, $request, $actor);

        return Inertia::render('WorkItemReport', [
            'kind' => 'ticket',
            'filters' => [
                'q' => $filters['q'],
                'status' => $filters['status'],
                'from' => $filters['from'],
                'to' => $filters['to'],
            ],
            'statusOptions' => $statusOptions,
            'summary' => $summary,
            'items' => $tickets,
        ]);
    }

    public function downloadReport(Request $request)
    {
        $filters = $this->resolveTicketReportFilters($request);
        $statusNormalized = $filters['normalized_status'];
        $userColumns = $this->userSelectColumns();
        $tickets = $this->buildTicketReportQuery($filters, false, $request->user())
            ->with('requester:'.implode(',', $userColumns))
            ->get();

        $tz = config('app.timezone');
        $statusLabel = $statusNormalized ? WorkflowStatus::label($statusNormalized) : __('Semua');

        $rows = $tickets->map(function (Ticket $ticket) use ($tz) {
            $status = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);

            return [
                $ticket->ticket_no ?? 'â€”',
                $ticket->title ?? 'â€”',
                WorkflowStatus::label($status),
                ucfirst($ticket->priority ?? 'â€”'),
                $this->formatDate($ticket->deadline(), 'd M Y H:i', $tz),
                $this->formatDate($ticket->worked_on_at, 'd M Y H:i', $tz),
                $this->formatDate($ticket->updated_at, 'd M Y H:i', $tz),
                $this->userDisplayName($ticket->requester),
            ];
        })->toArray();

        $columns = [
            ['label' => 'Ticket No'],
            ['label' => __('Judul')],
            ['label' => 'Status'],
            ['label' => __('Prioritas')],
            ['label' => __('Deadline')],
            ['label' => __('Mulai dikerjakan')],
            ['label' => __('Diperbarui')],
            ['label' => 'Requester'],
        ];

        $meta = [
            'filters' => [
                'Pencarian' => $filters['q'] !== '' ? $filters['q'] : __('Semua'),
                'Status' => $statusLabel,
                'Total Data' => number_format($tickets->count()),
            ],
        ];

        $filename = sprintf('tickets-report-%s.pdf', now()->format('Ymd-His'));

        return $this->reportExport->downloadPdf(__('Laporan Tiket'), $columns, $rows, $meta, $filename);
    }

    private function ticketReportSummary(array $filters, ?User $viewer = null): array
    {
        $counts = WorkflowStatus::countStatuses($this->buildTicketReportQuery($filters, false, $viewer));

        return [
            'total' => array_sum($counts),
            'in_progress' => $counts[WorkflowStatus::IN_PROGRESS],
            'done' => $counts[WorkflowStatus::DONE],
        ];
    }

    private function ticketReportPaginator(array $filters, Request $request, ?User $viewer = null)
    {
        $perPageParam = 'per_page';
        $pageName = 'page';

        $perPage = (int) $request->integer($perPageParam, 15);
        $perPage = min(max($perPage, 5), 50);

        $origin = $this->pageOrigin($request, 'tickets.report');

        $query = $this->buildTicketReportQuery($filters, true, $viewer);

        return $query
            ->paginate($perPage, ['*'], $pageName)
            ->appends($this->ticketReportQueryParams($filters))
            ->through(fn (Ticket $ticket) => $this->transformTicketReportRow($ticket, $origin));
    }

    private function buildTicketReportQuery(array $filters, bool $withRelations = false, ?User $viewer = null): Builder
    {
        $query = UnitVisibility::scopeTickets(Ticket::query(), $viewer)->searchReport($filters['q']);

        if ($withRelations) {
            $userColumns = $this->userRelationColumns();
            $query->with([
                'requester:'.$userColumns,
                'agent:'.$userColumns,
                'assignee:'.$userColumns,
                'assignedUsers:'.$userColumns,
                'attachments:id,attachable_id,attachable_type,original_name,size',
            ]);
        }

        if ($filters['normalized_status']) {
            WorkflowStatus::filterStatus($query, $filters['normalized_status']);
        }

        if ($filters['from_date']) {
            $from = $filters['from_date'];
            $query->where(function (Builder $b) use ($from) {
                $b->whereDate('due_at', '>=', $from)
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('due_at')->whereDate('due_date', '>=', $from));
            });
        }

        if ($filters['to_date']) {
            $to = $filters['to_date'];
            $query->where(function (Builder $b) use ($to) {
                $b->whereDate('due_at', '<=', $to)
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('due_at')->whereDate('due_date', '<=', $to));
            });
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    private function transformTicketReportRow(Ticket $ticket, string $origin): array
    {
        $status = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);
        $tz = config('app.timezone');

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
            'priority_label' => ucfirst($ticket->priority ?? 'â€”'),
            'status' => $status,
            'status_label' => WorkflowStatus::label($status),
            'status_badge' => WorkflowStatus::badgeClass($status),
            'status_id' => $ticket->status_id,
            'status_id_label' => WorkflowStatus::label($status),
            'reason' => $ticket->reason,
            'letter_no' => $ticket->letter_no,
            'due_display' => $this->formatDate($ticket->deadline(), 'd M Y H:i', $tz),
            'worked_on_display' => $this->formatDate($ticket->worked_on_at, 'd M Y H:i', $tz),
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
            'attachments' => $attachments,
            'links' => [
                'show' => $this->ticketReportDetailUrl($ticket),
                'edit' => routeLocale('tickets.edit', [
                    'ticket' => $ticket->id,
                ]),
                'delete' => routeLocale('tickets.destroy', [
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

    public function downloadDetail(Request $request, string $locale, Ticket $ticket)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        $this->loadTicketDetailRelations($ticket);
        $detail = $this->transformTicketDetail($ticket, route('tickets.index', ['locale' => app()->getLocale()]));

        $timeline = [
            'created_at' => $this->formatReportDate($detail['timeline']['created_at']),
            'updated_at' => $this->formatReportDate($detail['timeline']['updated_at']),
            'due_at' => $this->formatReportDate($detail['timeline']['due_at']),
            'worked_on_at' => $this->formatReportDate($detail['timeline']['worked_on_at']),
            'finish_at' => $this->formatReportDate($detail['timeline']['finish_at']),
        ];

        $description = $this->sanitizeDescription((string) ($detail['description'] ?? ''));
        if (trim(strip_tags($description)) === '') {
            $description = '<p>'.e(__('ticket_report.no_description')).'</p>';
        }

        $assignedUsers = $detail['assigned_users'] ?? [];

        $attachments = collect($detail['attachments'] ?? [])->map(function (array $attachment) {
            return [
                'name' => $attachment['name'] ?? null,
                'size' => isset($attachment['size']) ? $this->formatFileSize($attachment['size']) : null,
            ];
        })->values()->all();

        $filename = sprintf(
            'ticket-%s-detail.pdf',
            $ticket->ticket_no ? Str::slug($ticket->ticket_no) : $ticket->id
        );

        return $this->reportExport->downloadDetailPdf('reports.pdf.ticket-detail', [
            'title' => $detail['title'] ?? __('ticket_report.title'),
            'ticket' => $detail,
            'labels' => [
                'priority' => $this->reportValueLabel($ticket->priority),
                'sla' => $this->formatSlaLabel($ticket->sla) ?? '-',
            ],
            'timeline' => $timeline,
            'description' => $description,
            'assignedUsers' => $assignedUsers,
            'attachments' => $attachments,
        ], $filename);
    }

    private function formatReportDate($value): ?string
    {
        return $value
            ? Carbon::parse($value)->timezone(config('app.timezone'))->locale(app()->getLocale())->translatedFormat('d M Y H:i')
            : null;
    }

    private function loadTicketDetailRelations(Ticket $ticket): void
    {
        $ticket->load(['attachments', 'requester', 'agent', 'assignedUsers']);

        if (! $ticket->relationLoaded('confirmations')
            && Schema::hasTable('ticket_confirmations')
            && Schema::hasColumn('attachments', 'ticket_confirmation_id')) {
            $ticket->load(['confirmations' => fn ($query) => $query->with('evidence')->latest('id')]);
        }
    }

    private function reportValueLabel(?string $value): string
    {
        if (! $value) {
            return '—';
        }

        return in_array($value, ['high', 'medium', 'low'], true)
            ? __('ticket_report.'.$value)
            : __(ucfirst($value));
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
            'status' => $status,
            'status_label' => $statusLabel,
            'status_id' => $ticket->status_id,
            'status_id_label' => $statusLabel,
            'due_at' => $this->toIsoString($ticket->deadline()),
            'updated_at' => $this->toIsoString($ticket->updated_at),
            'requester' => $requesterName ? [
                'id' => $ticket->requester?->id,
                'name' => $requesterName,
            ] : null,
            'links' => [
                'show' => $this->ticketReportDetailUrl($ticket),
                'edit' => route('tickets.edit', ['locale' => app()->getLocale(),
                    'ticket' => $ticket->id]),
                'delete' => route('tickets.destroy', ['locale' => app()->getLocale(),
                    'ticket' => $ticket->id]),
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
                'edit' => routeLocale('tickets.edit', ['ticket' => $ticket->id, 'from' => $normalizedOrigin]),
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
        $normalizedStatus = WorkflowStatus::normalize($ticket->status ?? WorkflowStatus::NEW);
        $statusLabel = WorkflowStatus::label($normalizedStatus);

        return [
            'id' => $ticket->id,
            'ticket_no' => $ticket->ticket_no,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'priority' => $ticket->priority,
            'status' => $normalizedStatus,
            'status_label' => $statusLabel,
            'status_id' => $ticket->status_id,
            'status_id_label' => $statusLabel,
            'sla' => $ticket->sla,
            'reason' => $ticket->reason,
            'letter_no' => $ticket->letter_no,
            'timeline' => [
                'created_at' => $this->toIsoString($ticket->created_at),
                'updated_at' => $this->toIsoString($ticket->updated_at),
                'due_at' => $this->toIsoString($ticket->deadline()),
                'worked_on_at' => $this->toIsoString($ticket->worked_on_at),
                'finish_at' => $normalizedStatus === WorkflowStatus::DONE
                    ? $this->toIsoString($ticket->completed_at ?? $ticket->updated_at)
                    : null,
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
            'attachments' => $ticket->attachments->whereNull('ticket_confirmation_id')->map(fn ($attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
                'view_url' => $this->attachmentRoute('attachments.view', $attachment),
                'download_url' => $this->attachmentRoute('attachments.download', $attachment),
            ])->values()->all(),
            'confirmations' => ($ticket->relationLoaded('confirmations') ? $ticket->confirmations : collect())->map(fn ($confirmation) => [
                'id' => $confirmation->id,
                'note' => $confirmation->note,
                'created_at' => $this->toIsoString($confirmation->created_at),
                'evidence' => $confirmation->evidence->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'name' => $attachment->original_name,
                    'download_url' => $this->attachmentRoute('attachments.download', $attachment),
                ])->all(),
            ])->all(),
            'links' => [
                'index' => $this->normalizeInternalUrl(route('tickets.index', ['locale' => app()->getLocale()])),
                'edit' => route('tickets.edit', [
                    'locale' => app()->getLocale(),
                    'ticket' => $ticket->id,
                ]),
                'detail' => $this->ticketReportDetailUrl($ticket),
                'pdf' => route('tickets.report.detail', ['locale' => app()->getLocale(), 'ticket' => $ticket->id]),
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

            if (preg_match('/[\\\\\x00-\x20]/', $url)) {
                return false;
            }

            return (str_starts_with($url, '/') && !str_starts_with($url, '//'))
                || ($app !== '' && ($url === $app || str_starts_with($url, $app.'/')));
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
            if (preg_match('~^(https?://|mailto:|#)~i', $url)) {
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
        return match ($value) {
            'ontime' => __('On Time'),
            'late' => __('Late'),
            default => null,
        };
    }

    private function formatDate($value, string $format, ?string $tz = null): string
    {
        if (empty($value)) {
            return 'â€”';
        }

        try {
            $dt = $value instanceof Carbon ? $value : Carbon::parse($value);
            if ($tz) {
                $dt = $dt->timezone($tz);
            }

            return $dt->locale(app()->getLocale())->translatedFormat($format);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function buildRangeLabel($start, $end): string
    {
        $tz = config('app.timezone');
        $startLabel = $this->formatDate($start, 'd M Y', $tz);
        $endLabel = $this->formatDate($end, 'd M Y', $tz);

        if ($startLabel === 'â€”' && $endLabel === 'â€”') {
            return 'â€”';
        }

        if ($startLabel === 'â€”') {
            return 'Sampai '.$endLabel;
        }

        if ($endLabel === 'â€”') {
            return 'Mulai '.$startLabel;
        }

        return $startLabel.' - '.$endLabel;
    }

    private function formatFileSize($bytes): string
    {
        if (! is_numeric($bytes)) {
            return 'â€”';
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

