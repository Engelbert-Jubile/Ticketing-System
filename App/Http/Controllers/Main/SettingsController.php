<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\SettingsAuditLog;
use App\Models\User;
use App\Services\ReportExportService;
use App\Services\SettingsService;
use App\Support\RoleHelpers;
use App\Support\WorkflowStatus;
use App\Domains\Project\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    /**
     * Tampilkan halaman Settings berbasis Inertia.
     */
    public function index(Request $request, SettingsService $settings): Response
    {
        $general = $settings->getGroupWithMeta('general');
        $security = $settings->getGroupWithMeta('security');
        $notifications = $settings->getGroupWithMeta('notifications');
        $defaults = $settings->getGroupWithMeta('defaults');

        $logoPath = $general['values']['app_logo_path'] ?? null;
        $logoUrl = $logoPath ? Storage::disk('public')->url($logoPath) : null;

        $rolesPayload = [];
        $permissionsPayload = [];

        try {
            $roles = Role::query()->with('permissions')->orderBy('name')->get();
            $permissions = Permission::query()->orderBy('name')->get();
            $builtInRoles = ['superadmin', 'admin', 'user'];

            $rolesPayload = $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => RoleHelpers::displayLabel($role->name),
                'is_builtin' => in_array(RoleHelpers::canonical($role->name), $builtInRoles, true),
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ])->values()->all();

            $permissionsPayload = $permissions->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => Str::of($permission->name)->replace('_', ' ')->title()->toString(),
            ])->values()->all();
        } catch (\Throwable) {
            $rolesPayload = [];
            $permissionsPayload = [];
        }

        $now = Carbon::now();
        $dateFormats = [
            ['value' => 'd/m/Y', 'label' => $now->format('d/m/Y')],
            ['value' => 'Y-m-d', 'label' => $now->format('Y-m-d')],
            ['value' => 'd M Y', 'label' => $now->format('d M Y')],
            ['value' => 'M d, Y', 'label' => $now->format('M d, Y')],
        ];

        $statusOptions = collect(WorkflowStatus::all())->map(fn (string $status) => [
            'value' => $status,
            'label' => WorkflowStatus::label($status),
        ])->values()->all();

        // Ensure dropdown reflects the applied locale (single source of truth)
        $general['values']['locale'] = app()->getLocale();

        return Inertia::render('Settings/Index', [
            'settings' => [
                'general' => $general['values'],
                'security' => $security['values'],
                'notifications' => $notifications['values'],
                'defaults' => $defaults['values'],
            ],
            'settingsMeta' => [
                'general' => $general['meta'],
                'security' => $security['meta'],
                'notifications' => $notifications['meta'],
                'defaults' => $defaults['meta'],
                'logo_url' => $logoUrl,
            ],
            'options' => [
                'timezones' => \DateTimeZone::listIdentifiers(),
                'dateFormats' => $dateFormats,
                'locales' => [
                    ['value' => 'id', 'label' => 'Bahasa Indonesia'],
                    ['value' => 'en', 'label' => 'English'],
                ],
                'statusOptions' => $statusOptions,
                'priorityOptions' => [
                    ['value' => 'high', 'label' => 'High'],
                    ['value' => 'medium', 'label' => 'Medium'],
                    ['value' => 'low', 'label' => 'Low'],
                ],
                'autoAssignRoles' => $rolesPayload,
            ],
            'roleMatrix' => [
                'roles' => $rolesPayload,
                'permissions' => $permissionsPayload,
            ],
            'features' => [
                'two_factor' => (bool) config('features.two_factor', false),
                'impersonation' => (bool) config('features.impersonation', false),
                'ip_restrictions' => (bool) config('features.ip_restrictions', false),
                'maintenance_controls' => (bool) config('features.maintenance_controls', true),
                'cache_actions' => (bool) config('features.cache_actions', true),
                'rebuild_indexes' => (bool) config('features.rebuild_indexes', false),
                'system_actions_in_production' => (bool) config('features.system_actions_in_production', false),
                'environment' => config('app.env'),
            ],
        ]);
    }

    public function updateGeneral(Request $request, SettingsService $settings): RedirectResponse
    {
        $payload = $request->except(['app_logo']);

        if ($request->boolean('app_logo_clear')) {
            $payload['app_logo_path'] = null;
            $oldLogo = $settings->getRaw('general', 'app_logo_path');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
        }

        if ($request->hasFile('app_logo')) {
            $request->validate([
                'app_logo' => ['image', 'max:2048'],
            ]);

            $oldLogo = $settings->getRaw('general', 'app_logo_path');
            $path = $request->file('app_logo')->store('settings', 'public');
            $payload['app_logo_path'] = $path;

            if ($oldLogo && $oldLogo !== $path) {
                Storage::disk('public')->delete($oldLogo);
            }
        }

        $result = $settings->updateGroup('general', $payload, $request->user(), $request);

        if (! empty($result['changes']['maintenance_enabled']) && config('features.maintenance_controls', true)) {
            $this->applyMaintenanceMode($settings);
        }

        $supportedLocales = config('app.supported_locales', ['en', 'id']);
        $selectedLocale = $request->input('locale');
        if (is_string($selectedLocale) && in_array($selectedLocale, $supportedLocales, true)) {
            $request->session()->put('app.locale', $selectedLocale);
        }

        if (is_string($selectedLocale) && in_array($selectedLocale, $supportedLocales, true)) {
            $targetLocale = $selectedLocale;
        } elseif (is_string($request->route('locale')) && in_array($request->route('locale'), $supportedLocales, true)) {
            $targetLocale = $request->route('locale');
        } else {
            $targetLocale = $supportedLocales[0];
        }

        return redirect()->route('settings', ['locale' => $targetLocale])->with('success', 'General settings updated.');
    }

    public function updateSecurity(Request $request, SettingsService $settings): RedirectResponse
    {
        $settings->updateGroup('security', $request->all(), $request->user(), $request);

        return back()->with('success', 'Security settings updated.');
    }

    public function updateNotifications(Request $request, SettingsService $settings): RedirectResponse
    {
        $settings->updateGroup('notifications', $request->all(), $request->user(), $request);

        return back()->with('success', 'Notification settings updated.');
    }

    public function updateDefaults(Request $request, SettingsService $settings): RedirectResponse
    {
        $settings->updateGroup('defaults', $request->all(), $request->user(), $request);

        return back()->with('success', 'Defaults updated.');
    }

    public function updateRoles(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*.id' => ['required', 'integer', 'exists:roles,id'],
            'roles.*.permissions' => ['nullable', 'array'],
            'roles.*.permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $builtInRoles = ['superadmin', 'admin', 'user'];
        $incoming = collect($data['roles']);
        $roles = Role::query()->with('permissions')->whereIn('id', $incoming->pluck('id'))->get();

        foreach ($incoming as $roleData) {
            $role = $roles->firstWhere('id', $roleData['id']);
            if (! $role) {
                continue;
            }

            if (in_array(RoleHelpers::canonical($role->name), $builtInRoles, true)) {
                continue;
            }

            $previous = $role->permissions->pluck('name')->sort()->values()->all();
            $next = collect($roleData['permissions'] ?? [])->sort()->values()->all();

            if (json_encode($previous) === json_encode($next)) {
                continue;
            }

            $role->syncPermissions($next);

            SettingsAuditLog::create([
                'user_id' => $request->user()?->id,
                'action' => 'roles.update',
                'group' => 'roles',
                'key' => $role->name,
                'old_value' => json_encode($previous, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'new_value' => json_encode($next, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return back()->with('success', 'Role permissions updated.');
    }

    public function rbac(): JsonResponse
    {
        $roles = Role::query()->with('permissions')->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('name')->get();
        $builtInRoles = ['superadmin', 'admin', 'user'];

        return response()->json([
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => RoleHelpers::displayLabel($role->name),
                'is_builtin' => in_array(RoleHelpers::canonical($role->name), $builtInRoles, true),
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ])->values()->all(),
            'permissions' => $permissions->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => Str::of($permission->name)->replace('_', ' ')->title()->toString(),
            ])->values()->all(),
        ]);
    }

    public function updateRole(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $builtInRoles = ['superadmin', 'admin', 'user'];
        if (in_array(RoleHelpers::canonical($role->name), $builtInRoles, true)) {
            return response()->json(['message' => 'Built-in roles are read-only.'], 422);
        }

        $previous = $role->permissions->pluck('name')->sort()->values()->all();
        $next = collect($data['permissions'] ?? [])->sort()->values()->all();

        if (json_encode($previous) === json_encode($next)) {
            return response()->json(['message' => 'No changes detected.']);
        }

        $role->syncPermissions($next);

        SettingsAuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'roles.update',
            'group' => 'roles',
            'key' => $role->name,
            'old_value' => json_encode($previous, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'new_value' => json_encode($next, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Role permissions updated.']);
    }

    public function testEmail(Request $request, SettingsService $settings): JsonResponse
    {
        $validated = $request->validate([
            'recipient' => ['nullable', 'email'],
        ]);

        $recipient = $validated['recipient'] ?? $request->user()?->email;
        if (! $recipient) {
            return response()->json(['message' => 'Recipient email is required.'], 422);
        }

        $host = $settings->getRaw('notifications', 'smtp_host');
        $port = $settings->getRaw('notifications', 'smtp_port');
        $username = $settings->getRaw('notifications', 'smtp_username');
        $password = $settings->getRaw('notifications', 'smtp_password');
        $encryption = $settings->getRaw('notifications', 'smtp_encryption');

        if (! $host) {
            return response()->json(['message' => 'SMTP host is required.'], 422);
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port ?: 587,
            'mail.mailers.smtp.username' => $username,
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
        ]);

        try {
            Mail::mailer('smtp')->raw('Test email from TICKORA Settings.', function ($message) use ($recipient) {
                $message->to($recipient)->subject('TICKORA SMTP Test');
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => 'SMTP test failed: '.$e->getMessage()], 500);
        }

        return response()->json(['message' => 'Test email sent successfully.']);
    }

    public function audit(Request $request): JsonResponse
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                ],
            ]);
        }

        $logs = SettingsAuditLog::query()
            ->with('user')
            ->orderByDesc('id')
            ->paginate(15);

        $search = $request->query('q');
        if (is_string($search) && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $logs = SettingsAuditLog::query()
                ->with('user')
                ->where(function ($query) use ($term) {
                    $query->where('action', 'like', $term)
                        ->orWhere('group', 'like', $term)
                        ->orWhere('key', 'like', $term)
                        ->orWhere('ip_address', 'like', $term)
                        ->orWhere('old_value', 'like', $term)
                        ->orWhere('new_value', 'like', $term)
                        ->orWhereHas('user', function ($builder) use ($term) {
                            $builder->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                })
                ->orderByDesc('id')
                ->paginate(15);
        }

        return response()->json([
            'data' => $logs->getCollection()->map(function (SettingsAuditLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'group' => $log->group,
                    'key' => $log->key,
                    'old_value' => $log->old_value,
                    'new_value' => $log->new_value,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'actor' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name ?? $log->user->email,
                    ] : null,
                    'created_at' => optional($log->created_at)->toIsoString(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'app_name' => config('app.name'),
            'environment' => config('app.env'),
            'timezone' => config('app.timezone'),
            'framework_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'queue_connection' => config('queue.default'),
            'cache_driver' => config('cache.default'),
            'storage_driver' => config('filesystems.default'),
            'timestamp' => now()->toIsoString(),
        ]);
    }

    public function clearCaches(Request $request): JsonResponse
    {
        if (! config('features.cache_actions', true)) {
            abort(404);
        }

        if (app()->environment('production')) {
            if (! config('features.system_actions_in_production', false)) {
                return response()->json(['message' => 'System actions are disabled in production.'], 403);
            }
            if (! $request->boolean('confirmed')) {
                return response()->json(['message' => 'Production confirmation required.'], 422);
            }
        }

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Cache clear failed: '.$e->getMessage()], 500);
        }

        SettingsAuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'system.cache.clear',
            'group' => 'system',
            'key' => 'cache',
            'old_value' => null,
            'new_value' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Caches cleared successfully.']);
    }

    public function rebuildIndexes(Request $request): JsonResponse
    {
        if (! config('features.rebuild_indexes', false)) {
            abort(404);
        }

        if (app()->environment('production')) {
            if (! config('features.system_actions_in_production', false)) {
                return response()->json(['message' => 'System actions are disabled in production.'], 403);
            }
            if (! $request->boolean('confirmed')) {
                return response()->json(['message' => 'Production confirmation required.'], 422);
            }
        }

        try {
            Artisan::call('optimize:clear');
            Artisan::call('optimize');
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Index rebuild failed: '.$e->getMessage()], 500);
        }

        SettingsAuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'system.index.rebuild',
            'group' => 'system',
            'key' => 'indexes',
            'old_value' => null,
            'new_value' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Indexes rebuilt successfully.']);
    }

    public function export(Request $request, ReportExportService $reports)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:tickets,tasks,projects,users'],
            'format' => ['required', 'string', 'in:csv,pdf'],
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:60'],
            'role' => ['nullable', 'string', 'max:60'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $limit = $validated['limit'] ?? 1000;
        $type = $validated['type'];
        $format = $validated['format'];
        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        $q = $validated['q'] ?? null;
        $status = $validated['status'] ?? null;
        $role = $validated['role'] ?? null;

        $filters = [
            'Query' => $q ?: '-',
            'Status' => $status ?: '-',
            'Role' => $role ?: '-',
            'From' => $from ?: '-',
            'To' => $to ?: '-',
        ];

        [$columns, $rows, $title, $filename] = $this->buildExportPayload(
            $type,
            $q,
            $status,
            $role,
            $from,
            $to,
            $limit
        );

        if ($format === 'pdf') {
            return $reports->downloadPdf($title, $columns, $rows, ['filters' => $filters], $filename.'.pdf');
        }

        $outputName = $filename.'.csv';

        return response()->streamDownload(function () use ($columns, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_map(fn ($col) => $col['label'], $columns));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $outputName);
    }

    public function impersonate(Request $request, SettingsService $settings, ?User $user = null): RedirectResponse
    {
        if (! config('features.impersonation', false) || ! $settings->get('security', 'allow_impersonation', false)) {
            abort(403);
        }

        $target = $user;
        if (! $target) {
            $data = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
            ]);
            $target = User::query()->findOrFail($data['user_id']);
        }

        if (RoleHelpers::userIsSuperAdmin($target)) {
            return back()->withErrors(['user_id' => 'Tidak dapat impersonate superadmin lain.']);
        }

        $request->session()->put('impersonator_id', $request->user()?->id);
        Auth::guard('web')->login($target);

        SettingsAuditLog::create([
            'user_id' => $request->session()->get('impersonator_id'),
            'action' => 'impersonation.start',
            'group' => 'impersonation',
            'key' => (string) $target->id,
            'old_value' => null,
            'new_value' => json_encode(['user_id' => $target->id, 'email' => $target->email]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $locale = app()->getLocale() ?? config('app.locale', 'en');

        return redirect()->route('dashboard', ['locale' => $locale])->with('success', 'Impersonation started.');
    }

    public function stopImpersonate(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        if (! $impersonatorId) {
            return back()->withErrors(['impersonation' => 'Tidak ada sesi impersonasi aktif.']);
        }

        Auth::guard('web')->loginUsingId($impersonatorId);

        SettingsAuditLog::create([
            'user_id' => $impersonatorId,
            'action' => 'impersonation.stop',
            'group' => 'impersonation',
            'key' => (string) $impersonatorId,
            'old_value' => null,
            'new_value' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $locale = app()->getLocale() ?? config('app.locale', 'en');

        return redirect()->route('settings', ['locale' => $locale])->with('success', 'Impersonation stopped.');
    }

    private function applyMaintenanceMode(SettingsService $settings): void
    {
        if (! config('features.maintenance_controls', true)) {
            return;
        }

        try {
            // Keep Laravel's native maintenance mode off; enforcement is handled
            // via CheckMaintenanceMode to allow role-based bypass + allowlists.
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
            }
        } catch (\Throwable) {
        }
    }

    private function buildExportPayload(
        string $type,
        ?string $q,
        ?string $status,
        ?string $role,
        ?string $from,
        ?string $to,
        int $limit
    ): array {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;

        if ($type === 'tickets') {
            $query = Ticket::query();
            if ($q) {
                $query->where(function ($builder) use ($q) {
                    $builder->where('title', 'like', '%'.$q.'%')
                        ->orWhere('ticket_no', 'like', '%'.$q.'%');
                });
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->where('created_at', '<=', $toDate);
            }

            $rows = $query->latest()->limit($limit)->get();

            $columns = [
                ['label' => 'Ticket No'],
                ['label' => 'Title'],
                ['label' => 'Status'],
                ['label' => 'Priority'],
                ['label' => 'Type'],
                ['label' => 'Created At'],
            ];

            $data = $rows->map(function (Ticket $ticket) {
                return [
                    $ticket->ticket_no ?? $ticket->id,
                    $ticket->title,
                    $ticket->status,
                    $ticket->priority,
                    $ticket->type,
                    optional($ticket->created_at)->format('Y-m-d H:i'),
                ];
            })->values()->all();

            return [$columns, $data, 'Tickets Export', 'tickets-export'];
        }

        if ($type === 'tasks') {
            $query = Task::query();
            if ($q) {
                $query->where('title', 'like', '%'.$q.'%');
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->where('created_at', '<=', $toDate);
            }

            $rows = $query->latest()->limit($limit)->get();

            $columns = [
                ['label' => 'Task No'],
                ['label' => 'Title'],
                ['label' => 'Status'],
                ['label' => 'Priority'],
                ['label' => 'Due'],
                ['label' => 'Created At'],
            ];

            $data = $rows->map(function (Task $task) {
                return [
                    $task->task_no ?? $task->id,
                    $task->title,
                    $task->status,
                    $task->priority ?? '-',
                    $task->due_at ?? $task->due_date,
                    optional($task->created_at)->format('Y-m-d H:i'),
                ];
            })->values()->all();

            return [$columns, $data, 'Tasks Export', 'tasks-export'];
        }

        if ($type === 'projects') {
            $query = Project::query();
            if ($q) {
                $query->where('title', 'like', '%'.$q.'%')
                    ->orWhere('project_no', 'like', '%'.$q.'%');
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->where('created_at', '<=', $toDate);
            }

            $rows = $query->latest()->limit($limit)->get();

            $columns = [
                ['label' => 'Project No'],
                ['label' => 'Title'],
                ['label' => 'Status'],
                ['label' => 'Start Date'],
                ['label' => 'End Date'],
                ['label' => 'Updated At'],
            ];

            $data = $rows->map(function (Project $project) {
                return [
                    $project->project_no ?? $project->id,
                    $project->title,
                    $project->status ?? '-',
                    $project->start_date,
                    $project->end_date,
                    optional($project->updated_at)->format('Y-m-d H:i'),
                ];
            })->values()->all();

            return [$columns, $data, 'Projects Export', 'projects-export'];
        }

        $query = User::query();
        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('username', 'like', '%'.$q.'%');
            });
        }
        if ($role) {
            $query->whereHas('roles', fn ($builder) => $builder->where('name', $role));
        }
        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        $rows = $query->latest()->limit($limit)->get();

        $columns = [
            ['label' => 'Name'],
            ['label' => 'Email'],
            ['label' => 'Role'],
            ['label' => 'Unit'],
            ['label' => 'Created At'],
        ];

        $data = $rows->map(function (User $user) {
            $roleName = $user->getRoleNames()->first();

            return [
                $user->name ?? $user->full_name ?? $user->username ?? $user->email,
                $user->email,
                RoleHelpers::displayLabel($roleName),
                $user->unit ?? '-',
                optional($user->created_at)->format('Y-m-d H:i'),
            ];
        })->values()->all();

        return [$columns, $data, 'Users Export', 'users-export'];
    }
}
