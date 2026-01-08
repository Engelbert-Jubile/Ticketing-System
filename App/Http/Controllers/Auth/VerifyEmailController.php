<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Mark a user's email address as verified.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        /** @var User|null $user */
        $user = User::query()->find($id);
        $locale = $this->resolveLocale($request);

        if (! $user) {
            return redirect()
                ->route('login', ['locale' => $locale])
                ->with('error', 'User tidak ditemukan.');
        }

        $emailHash = sha1($user->getEmailForVerification());
        if (! hash_equals((string) $hash, (string) $emailHash)) {
            abort(403, 'Invalid verification link.');
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        $sessionUser = $request->user();
        if ($sessionUser && (string) $sessionUser->getKey() === (string) $user->getKey()) {
            return redirect()->intended(route('dashboard', ['locale' => $locale], false).'?verified=1');
        }

        return redirect()
            ->route('login', ['locale' => $locale])
            ->with('success', 'Email berhasil diverifikasi. Silakan login.');
    }

    private function resolveLocale(Request $request): string
    {
        $supported = config('app.supported_locales', ['en', 'id']);
        $candidates = [
            app()->getLocale(),
            $request->route('locale'),
            $request->query('lang'),
            $request->query('locale'),
            $request->session()->get('app.locale'),
            config('app.locale', 'en'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && in_array($value, $supported, true)) {
                return $value;
            }
        }

        return $supported[0] ?? 'en';
    }
}
