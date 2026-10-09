<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\PasswordRecoveryNotification;
use App\Support\SecurityPolicy;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PasswordRecoveryController extends Controller
{
    public function request()
    {
        return Inertia::render('Auth/PasswordRecovery', ['token' => null, 'email' => '']);
    }

    public function email(Request $request, string $locale)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        try {
            Password::sendResetLink($data, fn ($user, $token) => $user->notify(new PasswordRecoveryNotification($token, $locale)));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => 'Layanan email belum tersedia. Coba kembali atau hubungi admin.']);
        }

        return back()->with('success', 'Jika email terdaftar, tautan pemulihan akan dikirim. Periksa kotak masuk dan spam.');
    }

    public function reset(Request $request, string $locale, string $token)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        return Inertia::render('Auth/PasswordRecovery', ['token' => $token, 'email' => $data['email']]);
    }

    public function update(Request $request, string $locale)
    {
        $data = $request->validate([
            'token' => ['required', 'string'], 'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', SecurityPolicy::passwordRule()],
        ]);
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Tautan tidak valid atau kedaluwarsa. Minta tautan baru.']);
        }

        return redirect()->route('login', ['locale' => $locale])->with('success', 'Password diperbarui. Silakan masuk.');
    }
}
