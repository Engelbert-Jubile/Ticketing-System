<?php

namespace App\Http\Requests;

use App\Support\RoleHelpers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $userParam = $this->route('user');                // {user} dari route model binding
        $userId = is_object($userParam) ? $userParam->id : $userParam;

        $roleNames = $this->getRoleNames();               // ['user','admin','superadmin'] dst

        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'ends_with:@kftd.co.id', Rule::unique('users', 'email')->ignore($userId)],

            'role' => [
                'required', 'string', Rule::in($roleNames),

                // Batasi eskalasi role, tapi superadmin selalu boleh
                function (string $attribute, mixed $value, \Closure $fail) {
                    $me = auth()->user();

                    // Deteksi superadmin dari 3 sumber: Spatie role, kolom 'role', atau 'superadmin_guard'
                    if (RoleHelpers::userIsSuperAdmin($me)) {
                        return; // superadmin boleh pilih role apapun
                    }

                    // Non-superadmin dilarang set 'superadmin'
                    if (RoleHelpers::canonical($value) === 'superadmin') {
                        return $fail('Anda tidak berwenang memilih role tersebut.');
                    }

                    $isAdmin = false;
                    if (method_exists($me, 'hasRole')) {
                        try {
                            $isAdmin = $me->hasRole('admin');
                        } catch (\Throwable) {
                        }
                    }
                    $isAdmin = $isAdmin || RoleHelpers::canonical($me->role ?? null) === 'admin';

                    $targetParam = $this->route('user');
                    $targetId = is_object($targetParam) ? $targetParam->id : $targetParam;

                    if ($isAdmin) {
                        if ((int) $targetId === (int) $me->id) {
                            if (RoleHelpers::canonical($value) !== 'admin') {
                                return $fail('Anda tidak berwenang memilih role tersebut.');
                            }
                        } else {
                            if (RoleHelpers::canonical($value) !== 'user') {
                                return $fail('Anda tidak berwenang memilih role tersebut.');
                            }
                        }
                    } elseif (RoleHelpers::canonical($value) !== 'user') {
                        return $fail('Anda tidak berwenang memilih role tersebut.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('role')) {
            $this->merge([
                'role' => RoleHelpers::canonical($this->input('role')) ?? $this->input('role'),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Role tidak valid.',
            'email.ends_with' => 'Email harus menggunakan domain @kftd.co.id.',
        ];
    }

    /**
     * Ambil nama-nama role dari Spatie; jika gagal, pakai fallback.
     */
    private function getRoleNames(): array
    {
        try {
            $names = Role::query()->pluck('name')->all();
            if (! empty($names)) {
                return RoleHelpers::canonicalizeList($names);
            }
        } catch (\Throwable) {
            // ignore
        }

        // fallback kalau tabel roles belum siap
        return RoleHelpers::canonicalizeList(['user', 'admin', 'superadmin']);
        // tambahkan jika kamu punya role lain, mis. ['user','admin','manager','superadmin']
    }
}
