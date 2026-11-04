<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Services\WorkItemNotifier;
use App\Support\SecurityPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
                'updateRoute' => route('account.update-profile', ['locale' => app()->getLocale()]),
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
            'position' => 'nullable|string|max:255',
            'phone_wa' => 'nullable|string|max:30',
            'bio' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'avatar_template' => 'sometimes|required|in:male,female',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=6000,max_height=6000',
            'remove_photo' => 'sometimes|boolean',
        ]);

        $oldPhoto = $user->photo;
        $newPhoto = $request->hasFile('photo')
            ? $request->file('photo')->store('profile-photos', 'public') : null;
        unset($validated['photo'], $validated['remove_photo']);
        if ($newPhoto || $request->boolean('remove_photo')) {
            $validated['photo'] = $newPhoto;
        }
        try {
            $user->forceFill($validated)->save();
        } catch (\Throwable $exception) {
            if ($newPhoto) Storage::disk('public')->delete($newPhoto);
            throw $exception;
        }
        if ($oldPhoto && $oldPhoto !== $user->photo && str_starts_with($oldPhoto, 'profile-photos/')) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return back()->with('success', __('Profil berhasil diperbarui.'));
    }

    public function updateHeaderColor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'header_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $color = isset($validated['header_color'])
            ? strtolower($validated['header_color'])
            : null;

        $request->user()->update(['header_color' => $color]);

        return response()->json(['header_color' => $color]);
    }

    public function updateLocale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['present', 'nullable', \Illuminate\Validation\Rule::in(['en', 'id'])],
        ]);

        $request->user()->forceFill(['preferred_locale' => $validated['locale']])->save();

        return response()->json([
            'locale' => \App\Support\UserLocale::resolve($request->user()),
            'preferred_locale' => $request->user()->preferred_locale,
        ]);
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
            return back()->withErrors(['current_password' => __('Password lama salah.')]);
        }

        $new = (string) ($request->input('new_password') ?? $request->input('password'));
        if ($new === '') {
            return back()->withErrors(['password' => __('Password baru wajib diisi.')]);
        }

        // Model User sudah punya cast 'password' => 'hashed', jadi boleh set plain
        $user->update(['password' => $new]);

        // Optional: regenerate session token untuk keamanan
        $request->session()->regenerateToken();

        app(WorkItemNotifier::class)->notifyPasswordChanged($user, $request->user());

        return back()->with('success', __('Password berhasil diperbarui.'));
    }

    private function transformAccountUser($user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'position' => $user->position,
            'phone_wa' => $user->phone_wa,
            'bio' => $user->bio,
            'address' => $user->address,
            'avatar_template' => $user->avatar_template ?? 'male',
            'photo_url' => $user->photo_url,
            'role' => $user->role_label,
            'unit' => $user->unit,
        ];
    }
}
