<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Services\WorkItemNotifier;
use App\Support\SecurityPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Tampilkan form edit profil pengguna
     */
    public function profile(Request $request): Response
    {
        return Inertia::render('Account/Profile', [
            'user' => $this->transformAccountUser($request->user()),
            'meta' => [
                'updateRoute' => route('account.update-profile'),
                'method' => 'put',
            ],
        ]);
    }

    /**
     * Proses update profil pengguna
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Tampilkan form ubah password
     */
    public function changePassword(Request $request): Response
    {
        return Inertia::render('Account/ChangePassword', [
            'user' => $this->transformAccountUser($request->user()),
        ]);
    }

    /**
     * Proses update password pengguna
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        // Terima dua skema field: new_password(+_confirmation) ATAU password(+_confirmation)
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['nullable', 'confirmed', SecurityPolicy::passwordRule()],
            'password' => ['nullable', 'confirmed', SecurityPolicy::passwordRule()],
        ]);

        $user = $request->user();

        if (! Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama salah.']);
        }

        $new = (string) ($request->input('new_password') ?? $request->input('password'));
        if ($new === '') {
            return back()->withErrors(['password' => 'Password baru wajib diisi.']);
        }

        // Model User sudah punya cast 'password' => 'hashed', jadi boleh set plain
        $user->update(['password' => $new]);

        // Optional: regenerate session token untuk keamanan
        $request->session()->regenerateToken();

        app(WorkItemNotifier::class)->notifyPasswordChanged($user, $request->user());

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    private function transformAccountUser($user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ];
    }
}
