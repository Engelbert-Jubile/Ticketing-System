<?php

namespace App\Http\Middleware;

use App\Domains\Project\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SettingsService;
use App\Support\UnitVisibility;
use App\Support\WorkflowStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $announcement = $this->resolveAnnouncement();
        $maintenance = $this->resolveMaintenance();
        $resolvedLocale = $this->resolveLocaleFromSettings();

        if ($resolvedLocale && $resolvedLocale !== app()->getLocale()) {
            app()->setLocale($resolvedLocale);
        }

        if (config('app.debug')) {
            Log::info('inertia.locale.share', [
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'app_locale' => app()->getLocale(),
                'resolved_locale' => $resolvedLocale,
                'route_locale' => $request->route('locale'),
                'session_locale' => $request->session()->get('app.locale'),
            ]);
        }

        return array_merge(parent::share($request), [
            'status' => fn () => $request->session()->get('status'),
            'locale' => $resolvedLocale ?? app()->getLocale(),
            'announcement' => $announcement,
            'maintenance' => $maintenance,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name ?? null,
                    'username' => $user->username ?? null,
                    'email' => $user->email ?? null,
                    'display_name' => $user->display_name ?? null,
                    'full_name' => $user->full_name ?? null,
                    'first_name' => $user->first_name ?? null,
                    'last_name' => $user->last_name ?? null,
                    'unit' => $user->unit ?? null,
                    'roles' => $this->resolveRoles($user),
                ] : null,
            ],
            'notifications' => $user ? $this->mapNotifications($user) : [
                'unread_count' => 0,
                'items' => [],
            ],
            'sidebarMetrics' => $user ? $this->resolveSidebarMetrics($request, $user) : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'impersonation' => [
                'active' => $request->session()->has('impersonator_id'),
                'impersonator_id' => $request->session()->get('impersonator_id'),
            ],
        ]);
    }

    private function resolveLocaleFromSettings(): ?string
    {
        $supported = config('app.supported_locales', ['en', 'id']);
        $settings = app(SettingsService::class);
        $dbLocale = $settings->get('general', 'locale', config('app.locale', 'en'));
        $normalized = $this->normalizeLocale($dbLocale, $supported);

        return $normalized ?: ($supported[0] ?? 'en');
    }

    private function normalizeLocale($locale, array $supported): ?string
    {
        if (! is_string($locale)) {
            return null;
        }

        $clean = strtolower(trim($locale));

        return in_array($clean, $supported, true) ? $clean : null;
    }

    private function resolveSidebarMetrics(Request $request, $user): array
    {
        return [
            'tickets_in_progress_count' => $this->countTicketsInProgress($request),
            'tasks_in_progress_count' => $this->countTasksInProgress($request),
            'projects_in_progress_count' => $this->countProjectsInProgress($request),
            'can_manage_users' => $user ? $user->can('viewAny', User::class) : false,
        ];
    }

    private function inProgressStatusScope(): array
    {
        return array_values(array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS),
            WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION)
        )));
    }

    private function countTicketsInProgress(Request $request): int
    {
        try {
            $query = Ticket::query();
            $query = UnitVisibility::scopeTickets($query, $request->user());

            return $query
                ->whereIn('status', $this->inProgressStatusScope())
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countTasksInProgress(Request $request): int
    {
        try {
            $query = Task::query();
            $query = UnitVisibility::scopeTasks($query, $request->user());

            return $query
                ->whereIn('status', $this->inProgressStatusScope())
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countProjectsInProgress(Request $request): int
    {
        try {
            $query = Project::query();
            $query = UnitVisibility::scopeProjects($query, $request->user());

            return $query
                ->whereIn('status', $this->inProgressStatusScope())
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function parseDateFilter(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Throwable) {
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
        }

        return null;
    }

    private function sanitizeQuery(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function mapNotifications($user): array
    {
        try {
            $unreadCount = $user->unreadNotifications()->count();
            $notifications = $user->notifications()->latest()->limit(15)->get();

            return [
                'unread_count' => $unreadCount,
                'items' => $notifications->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'icon' => $notification->data['icon'] ?? 'notifications',
                        'title' => $notification->data['title'] ?? 'Activity',
                        'message' => $notification->data['message'] ?? null,
                        'url' => $notification->data['url'] ?? null,
                        'is_unread' => is_null($notification->read_at),
                        'read_at' => optional($notification->read_at)?->toDateTimeString(),
                        'time_ago' => optional($notification->created_at)?->diffForHumans(),
                    ];
                })->all(),
            ];
        } catch (\Throwable $e) {
            return [
                'unread_count' => 0,
                'items' => [],
            ];
        }
    }

    private function resolveRoles($user): array
    {
        try {
            if (method_exists($user, 'getRoleNames')) {
                return $user->getRoleNames()->toArray();
            }

            if (method_exists($user, 'roles')) {
                return $user->roles->pluck('name')->all();
            }
        } catch (\Throwable $e) {
            // ignore role resolution errors
        }

        return [];
    }

    private function resolveAnnouncement(): array
    {
        $empty = [
            'enabled' => false,
            'title' => null,
            'body' => null,
            'message' => null,
            'start_at' => null,
            'end_at' => null,
            'starts_at' => null,
            'ends_at' => null,
        ];

        try {
            /** @var SettingsService $settings */
            $settings = app(SettingsService::class);
            $general = $settings->getGroup('general');

            $enabled = (bool) ($general['announcement_enabled'] ?? false);
            if (! $enabled) {
                return $empty;
            }

            $start = $general['announcement_starts_at'] ?? null;
            $end = $general['announcement_ends_at'] ?? null;
            $now = Carbon::now();

            $startsAt = $start ? Carbon::parse($start)->startOfDay() : null;
            $endsAt = $end ? Carbon::parse($end)->endOfDay() : null;

            if ($startsAt && $now->lt($startsAt)) {
                return $empty;
            }
            if ($endsAt && $now->gt($endsAt)) {
                return $empty;
            }

            $body = $general['announcement_body'] ?? null;

            return [
                'enabled' => true,
                'title' => $general['announcement_title'] ?? null,
                'body' => $body,
                'message' => $body,
                'start_at' => $start,
                'end_at' => $end,
                'starts_at' => $start,
                'ends_at' => $end,
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    private function resolveMaintenance(): array
    {
        try {
            /** @var SettingsService $settings */
            $settings = app(SettingsService::class);
            $enabled = (bool) $settings->getRaw('general', 'maintenance_enabled', false);
            $message = $settings->getRaw('general', 'maintenance_message', null);

            return [
                'enabled' => $enabled,
                'message' => $message ?: null,
            ];
        } catch (\Throwable $e) {
            return [
                'enabled' => false,
                'message' => null,
            ];
        }
    }
}
