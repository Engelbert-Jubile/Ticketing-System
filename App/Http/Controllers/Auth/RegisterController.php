<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * Tampilkan halaman form register.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $locale = app()->getLocale() ?? config('app.locale', 'en');
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:10', 'unique:users,username'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'ends_with:@kftd.co.id'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'email.ends_with' => 'Gunakan email perusahaan @kftd.co.id.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('dashboard', ['locale' => $locale]);
    }
}
