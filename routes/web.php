<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\GeminiAIController;
use App\Http\Controllers\Main\AccountController;
use App\Http\Controllers\Main\AttachmentController;
/* ─────────────────────────────────────────────────────────
 |  Controllers
 ───────────────────────────────────────────────────────── */
use App\Http\Controllers\Main\AttachmentUploadController;
use App\Http\Controllers\Main\DashboardController;
use App\Http\Controllers\Main\ProfileController;
use App\Http\Controllers\Main\ProjectController;
use App\Http\Controllers\Main\ReportController;
use App\Http\Controllers\Main\SettingsController;
use App\Http\Controllers\Main\SLAReportController;
use App\Http\Controllers\Main\TaskController;
use App\Http\Controllers\Main\TicketController;
use App\Http\Controllers\Main\UnitReportsController;
use App\Http\Controllers\Main\UserController;
use App\Http\Controllers\SearchController;
use App\Support\UserUnitOptions;
use App\Models\Task;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$emailVerificationEnabled = (bool) config('features.email_verification', false);

Route::pattern('locale', 'en|id');

$resolveLocale = function (Request $request): string {
    $supported = config('app.supported_locales', ['en', 'id']);
    $settings = app(SettingsService::class);
    $default = $settings->get('general', 'locale', config('app.locale', 'en')) ?? 'en';

    return in_array($default, $supported, true) ? $default : ($supported[0] ?? 'en');
};

$resolveAnnouncement = function (): array {
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
    } catch (\Throwable) {
        return $empty;
    }
};

$resolveMaintenance = function (): array {
    try {
        $settings = app(SettingsService::class);

        return [
            'enabled' => (bool) $settings->getRaw('general', 'maintenance_enabled', false),
            'message' => $settings->getRaw('general', 'maintenance_message', null),
        ];
    } catch (\Throwable) {
        return [
            'enabled' => false,
            'message' => null,
        ];
    }
};

$publicWelcomeProps = function () use ($resolveAnnouncement, $resolveMaintenance): array {
    return [
        'announcement' => $resolveAnnouncement(),
        'maintenance' => $resolveMaintenance(),
    ];
};

// Redirect legacy root tanpa prefix locale ke locale aktif/default
Route::get('/', function (Request $request) use ($resolveLocale) {
    $locale = $resolveLocale($request);

    return redirect()->route('home', ['locale' => $locale]);
})->name('root.locale.redirect');

Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => 'en|id'],
], function () use ($emailVerificationEnabled, $publicWelcomeProps) {

/* ─────────────────────────────────────────────────────────
 |  PUBLIC ROUTES
 ───────────────────────────────────────────────────────── */

Route::get('/', function (Request $request) use ($publicWelcomeProps) {
    return $request->user()
        ? redirect()->route('dashboard')
        : view('welcome', $publicWelcomeProps());
})->name('home');

Route::get('/welcome', function () use ($publicWelcomeProps) {
    return view('welcome', $publicWelcomeProps());
})->name('welcome');

Route::get('/403', function (Request $request) {
    return response()->view('errors.403', [], 403);
})->name('errors.forbidden');

Route::get('/inertia-health', function () {
    return Inertia::render('Health/Ok', ['msg' => 'Inertia alive']);
})->name('inertia.health');

/* 🔐 Auth (Login & Register) — hanya untuk guest */
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', function () {
        return view('auth.register', [
            'units' => UserUnitOptions::values(),
        ]);
    })->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

/* 📧 Email Verification */
if ($emailVerificationEnabled) {
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', fn () => Inertia::render('Auth/VerifyEmail'))
        ->name('verification.notice');

    Route::get('/email/verification-status', function (Request $request) {
        $user = $request->user();
        $verified = $user && method_exists($user, 'hasVerifiedEmail')
            ? $user->hasVerifiedEmail()
            : false;

        return response()->json(['verified' => $verified]);
    })->name('verification.status');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');
});

Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
} else {
    Route::get('/email/verify', function (Request $request) {
        return $request->user()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    })->name('verification.notice');
}

/* ─────────────────────────────────────────────────────────
 |  PROTECTED ROUTES  (auth)
 ───────────────────────────────────────────────────────── */
Route::middleware($emailVerificationEnabled
    ? ['auth', 'verified', 'idle.timeout', 'access.restrict']
    : ['auth', 'idle.timeout', 'access.restrict']
)->group(function () {

    /* 📊 Dashboard Home */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/unit-reports', [UnitReportsController::class, 'index'])->name('dashboard.unit-reports');

    /* 🤖 Gemini AI Assistant */
    Route::post('/dashboard/ai/gemini', [GeminiAIController::class, 'respond'])->name('ai.gemini.chat');

    /* 🔍 Global Search */
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    /* 👤 Profil user */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /* 🎫 Tickets */
    Route::prefix('dashboard/tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/create', [TicketController::class, 'create'])->name('create');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::get('/on-progress', [TicketController::class, 'onProgress'])->name('on-progress');
        Route::get('/report', [TicketController::class, 'report'])->name('report');
        Route::get('/report/download', [TicketController::class, 'downloadReport'])->name('report.download');
        Route::get('/report/detail/{ticket:ticket_no}', [TicketController::class, 'reportDetailView'])->name('report.detail.view');
        Route::get('/{ticket}/report/pdf', [TicketController::class, 'downloadDetail'])->name('report.detail');

        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [TicketController::class, 'edit'])->name('edit');
        Route::put('/{ticket}', [TicketController::class, 'update'])->name('update');
        Route::delete('/{ticket}', [TicketController::class, 'destroy'])->name('destroy');
        Route::get('/{ticket}/status/{status}', [TicketController::class, 'changeStatus'])->name('status.change');

        // Manage attachments standalone (reattach)
        Route::get('/{ticket}/attachments', [TicketController::class, 'manageAttachments'])->name('attachments.manage');
        Route::put('/{ticket}/attachments', [TicketController::class, 'updateAttachments'])->name('attachments.update');
    });

    // 🔔 Notifications
    Route::prefix('dashboard/notifications')->name('notifications.')->group(function () {
        Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('read-all');
        Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->name('read');
        Route::post('/{id}/mark', [\App\Http\Controllers\NotificationController::class, 'mark'])->name('mark');
        Route::delete('/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
    });

    /* 📋 Tasks */
    Route::prefix('dashboard/tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('/create', [TaskController::class, 'create'])->name('create');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/on-progress', [TaskController::class, 'onProgress'])->name('on-progress');
        Route::get('/report', [TaskController::class, 'report'])->name('report');
        Route::get('/report/download', [TaskController::class, 'downloadReport'])->name('report.download');
        Route::get('/report/detail/{ticket:ticket_no}', [TaskController::class, 'reportTicketDetail'])->name('report.ticket');
        Route::get('/{task}/report/pdf', [TaskController::class, 'downloadDetail'])->name('report.detail');

        Route::get('/report/detail/task/{taskSlug}', [TaskController::class, 'showBySlug'])->name('show');
        Route::get('/report/edit/{task:public_slug}', [TaskController::class, 'edit'])->name('edit');
        Route::get('/{task}', [TaskController::class, 'show'])->name('show.legacy');
        Route::get('/{task}/view', [TaskController::class, 'view'])->name('view');
        Route::get('/{task}/edit', function (Task $task) {
            return redirect()->route('tasks.edit', ['task' => $task->public_slug]);
        })->name('edit.legacy');
        Route::put('/{task}', [TaskController::class, 'update'])->name('update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
        Route::post('/{task}/promote', [TaskController::class, 'promoteToProject'])->name('promote');
    });

    /* 🗂 Projects */
    Route::prefix('dashboard/projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('/on-progress', [ProjectController::class, 'onProgress'])->name('on-progress');
        Route::get('/report', [ProjectController::class, 'report'])->name('report');
        Route::get('/report/download', [ProjectController::class, 'downloadReport'])->name('report.download');
        Route::get('/{project}/report/pdf', [ProjectController::class, 'downloadDetail'])->name('report.detail');

        Route::get('/report/detail/project/{project:public_slug}', [ProjectController::class, 'show'])->name('show');
        Route::get('/report/edit/{project:public_slug}', [ProjectController::class, 'edit'])->name('edit');
        Route::get('/{project}', [ProjectController::class, 'showLegacy'])->name('show.legacy');
        Route::get('/{project}/edit', function (
            \App\Domains\Project\Models\Project $project
        ) {
            return redirect()->route('projects.edit', ['project' => $project->public_slug]);
        })->name('edit.legacy');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
    });

    /* 📈 Reports */
    Route::prefix('dashboard/reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });

    /* ⏱ SLA Reports */
    Route::prefix('dashboard/sla')->group(function () {
        Route::get('/', [SLAReportController::class, 'index'])->name('dashboard.sla');
        Route::get('/download', [SLAReportController::class, 'download'])->name('sla.download');
        Route::get('/{type}/{id}/pdf', [SLAReportController::class, 'downloadDetail'])->name('sla.detail.download');
    });

    /* ⚙️ Settings */
    Route::prefix('dashboard/settings')
        ->middleware('superadmin')
        ->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('settings');
            Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('settings.general.update');
            Route::post('/security', [SettingsController::class, 'updateSecurity'])->name('settings.security.update');
            Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
            Route::post('/notifications/test-email', [SettingsController::class, 'testEmail'])->name('settings.notifications.test');
            Route::post('/defaults', [SettingsController::class, 'updateDefaults'])->name('settings.defaults.update');
            Route::post('/roles', [SettingsController::class, 'updateRoles'])->name('settings.roles.update');
            Route::get('/rbac', [SettingsController::class, 'rbac'])->name('settings.rbac');
            Route::put('/rbac/roles/{role}', [SettingsController::class, 'updateRole'])->name('settings.rbac.roles.update');
            Route::get('/audit', [SettingsController::class, 'audit'])->name('settings.audit');
            Route::get('/health', [SettingsController::class, 'health'])->name('settings.health');
            Route::post('/system/clear-caches', [SettingsController::class, 'clearCaches'])->name('settings.system.clear-caches');
            Route::post('/system/clear-cache', [SettingsController::class, 'clearCaches'])->name('settings.system.clear-cache');
            Route::post('/system/rebuild-indexes', [SettingsController::class, 'rebuildIndexes'])->name('settings.system.rebuild-indexes');
            Route::post('/system/rebuild-index', [SettingsController::class, 'rebuildIndexes'])->name('settings.system.rebuild-index');
            Route::get('/export', [SettingsController::class, 'export'])->name('settings.export');
            Route::post('/impersonate', [SettingsController::class, 'impersonate'])->name('settings.impersonate');
            Route::post('/impersonate/{user}', [SettingsController::class, 'impersonate'])->name('settings.impersonate.user');
        });
    Route::post('/dashboard/settings/impersonate/stop', [SettingsController::class, 'stopImpersonate'])
        ->name('settings.impersonate.stop');

    /* 👥 Users (ADMIN) */
    Route::prefix('dashboard/users')
        ->name('users.')
        ->middleware('can:viewAny,App\Models\User')
        ->group(function () {
            Route::get('/', [UserController::class, 'report'])->name('index');
            Route::get('/report', [UserController::class, 'report'])->name('report');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });

    /* ⚙️ Account */
    Route::prefix('dashboard/account')->name('account.')->group(function () {
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [AccountController::class, 'updateProfile'])->name('update-profile');
        Route::get('/change-password', [AccountController::class, 'changePassword'])->name('change-password');
        Route::put('/change-password', [AccountController::class, 'updatePassword'])->name('password.update');
    });

    /* 🚪 Logout (POST utama + GET fallback) */
    Route::post('/logout', [AuthController::class, 'logout'])
        ->withoutMiddleware(['verified'])
        ->name('logout');

    Route::get('/logout', [AuthController::class, 'logout'])
        ->withoutMiddleware(['verified'])
        ->name('logout.get');
});

/* ─────────────────────────────────────────────────────────
 |  ATTACHMENTS (FilePond) — auth saja (tanpa verified)
 |  Agar request dari FilePond tidak gagal karena email belum verified.
 ───────────────────────────────────────────────────────── */
Route::middleware(['auth'])
    ->prefix('dashboard/attachments')
    ->name('attachments.')
    ->group(function () {

        // FilePond endpoints
        Route::post('/', [AttachmentUploadController::class, 'process'])->name('process'); // POST /dashboard/attachments
        Route::delete('/revert', [AttachmentUploadController::class, 'revert'])->name('revert');   // DELETE /dashboard/attachments/revert
        Route::get('/tmp/{id}', [AttachmentUploadController::class, 'load'])->name('load');       // GET /dashboard/attachments/tmp/{id}

        // Existing attachments actions
        Route::get('/{attachment}/view', [AttachmentController::class, 'view'])->name('view');
        Route::get('/{attachment}/download', [AttachmentController::class, 'download'])->name('download');
        Route::delete('/{attachment}', [AttachmentController::class, 'destroy'])->name('destroy');
    });

}); // end locale-prefixed group

// Redirect legacy URL tanpa prefix locale ke URL berlocale
Route::get('{path}', function (Request $request, string $path) use ($resolveLocale) {
    $locale = $resolveLocale($request);
    $cleanPath = ltrim($path, '/');
    $target = $cleanPath ? "/{$locale}/{$cleanPath}" : "/{$locale}";

    return redirect($target);
})->where('path', '^(?!storage)(?!livewire)(?!_debugbar)(?!vendor).*$');
