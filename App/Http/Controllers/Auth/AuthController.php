<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\RecaptchaVerifier;
use App\Services\SettingsService;
use App\Support\SecurityPolicy;
use App\Support\UserUnitOptions;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function __construct(private RecaptchaVerifier $recaptcha) {}

    /* Proses register — disesuaikan dengan model User kamu */
    public function register(Request $request): \Illuminate\Http\RedirectResponse
    {
        $locale = app()->getLocale() ?? config('app.locale', 'en');
        $rules = [
            'username' => ['required', 'string', 'max:10', 'unique:users,username'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', SecurityPolicy::emailDomainRule(), 'unique:users,email'],
            'password' => ['required', 'confirmed', SecurityPolicy::passwordRule()],
            'unit' => ['required', 'string', 'max:120', Rule::in(UserUnitOptions::values())],
        ];

        if ($this->recaptcha->isEnabled()) {
            $rules['g-recaptcha-response'] = ['required', 'string'];
        }

        $data = $request->validate($rules);

        $this->ensureRecaptcha($request);

        $settings = app(SettingsService::class);
        if (SecurityPolicy::ipRestrictionsEnabled($settings) && ! SecurityPolicy::ipAllowed($request->ip(), $settings)) {
            abort(403, 'Access blocked by IP restriction policy.');
        }

        // Normalisasi email: trim + lowercase agar konsisten
        $data['email'] = strtolower(trim($data['email']));

        // password akan di-hash otomatis oleh cast 'hashed' di model User
        $user = User::create([
            'username' => $data['username'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'password' => $data['password'], // plain; cast 'hashed' yang handle
            'unit' => $data['unit'],
        ]);

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->assignRole('user');

        Auth::guard('web')->login($user);
        request()->session()->regenerate();

        if (! (bool) config('features.email_verification', false)) {
            return redirect()->route('dashboard', ['locale' => $locale])->with('success', 'Selamat datang!');
        }

        try {
            if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();

                return redirect()
                    ->route('verification.notice', ['locale' => $locale])
                    ->with('status', 'verification-link-sent');
            }
        } catch (\Throwable $e) {
            return redirect()
                ->route('verification.notice', ['locale' => $locale])
                ->with('error', 'Gagal mengirim email verifikasi. Silakan coba lagi nanti atau hubungi admin.');
        }

        return redirect()->route('dashboard', ['locale' => $locale])->with('success', 'Selamat datang!');
    }

    public function login(Request $request): \Illuminate\Http\RedirectResponse
    {
        $locale = app()->getLocale() ?? config('app.locale', 'en');
        $rules = [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];

        if ($this->recaptcha->isEnabled()) {
            $rules['g-recaptcha-response'] = ['required', 'string'];
        }

        $data = $request->validate($rules);

        $this->ensureRecaptcha($request);

        $settings = app(SettingsService::class);
        $allowedDomains = SecurityPolicy::allowedEmailDomains($settings);
        $defaultDomain = $allowedDomains[0] ?? 'kftd.co.id';
        $lockedDomain = '@'.$defaultDomain;
        $rawLogin = strtolower(trim($data['email']));
        $hasAt = str_contains($rawLogin, '@');
        $emailToQuery = $hasAt ? $rawLogin : ($rawLogin . $lockedDomain);
        $throttleKey = sprintf('login:%s|%s', $request->ip(), $emailToQuery);
        $maxAttempts = (int) $settings->get('security', 'max_login_attempts', 5);
        $lockoutMinutes = (int) $settings->get('security', 'lockout_minutes', 15);

        if ($maxAttempts > 0 && RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan login. Coba lagi dalam '.ceil($seconds / 60).' menit.',
            ]);
        }

        /** @var \App\Models\User|null $user */
        $user = User::where('email', $emailToQuery)->first();

        if (! $user) {
            $usernameCandidate = $rawLogin;
            if ($hasAt) {
                $beforeAt = strstr($rawLogin, '@', true);
                $usernameCandidate = $beforeAt === false ? $rawLogin : $beforeAt;
            }

            if ($usernameCandidate !== '') {
                $candidate = User::where('username', $usernameCandidate)->first();
                if ($candidate && $candidate->hasRole('superadmin')) {
                    $user = $candidate;
                }
            }
        }

        if ($user && ! $user->hasRole('superadmin')) {
            $userEmail = strtolower(trim((string) $user->email));
            $domain = str_contains($userEmail, '@') ? substr(strrchr($userEmail, '@'), 1) : '';
            if ($domain === '' || ! in_array($domain, $allowedDomains, true)) {
                $user = null;
            }
        }

        if (SecurityPolicy::ipRestrictionsEnabled($settings) && ! SecurityPolicy::ipAllowed($request->ip(), $settings)) {
            $allowBypass = (bool) $settings->get('security', 'allow_superadmin_ip_bypass', true);
            if (! ($allowBypass && $user && $user->hasRole('superadmin'))) {
                return back()->withErrors([
                    'email' => 'Access blocked by IP restriction policy.',
                ])->onlyInput('email');
            }
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            if ($maxAttempts > 0) {
                RateLimiter::hit($throttleKey, $lockoutMinutes * 60);
            }
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        RateLimiter::clear($throttleKey);

        if (! (bool) config('features.email_verification', false)) {
            return redirect()->intended(route('dashboard', ['locale' => $locale]));
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice', ['locale' => $locale]);
        }

        return redirect()->intended(route('dashboard', ['locale' => $locale]));
    }

    /* Logout */
    public function logout(Request $request)
    {
        $locale = app()->getLocale() ?? config('app.locale', 'en');
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('home', ['locale' => $locale]));
        }

        return redirect()->route('home', ['locale' => $locale]);
    }

    private function ensureRecaptcha(Request $request): void
    {
        if (! $this->recaptcha->isEnabled()) {
            return;
        }

        if (! $this->recaptcha->verify($request->input('g-recaptcha-response'), $request->ip())) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Verifikasi keamanan gagal, silakan coba lagi.',
            ]);
        }
    }
}
