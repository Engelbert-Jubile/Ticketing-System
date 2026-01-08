<?php

namespace App\Http\Requests;

use App\Support\RoleHelpers;
use App\Support\SecurityPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (RoleHelpers::userIsSuperAdmin($user)) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            try {
                if ($user->hasRole('admin')) {
                    return true;
                }
            } catch (\Throwable) {
                // abaikan dan lanjut ke fallback kolom
            }
        }

        return RoleHelpers::canonical($user->role ?? null) === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', SecurityPolicy::emailDomainRule()],
            'password' => ['required', 'confirmed', SecurityPolicy::passwordRule()],
            'role' => ['required', Rule::in($this->allowedRoles())],
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

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $creator = auth()->user();

            if (RoleHelpers::canonical($this->role) === 'superadmin' && \App\Models\User::whereHas('roles', fn ($query) => $query->whereIn('name', ['superadmin', 'Super Admin']))->exists()) {
                $v->errors()->add('role', 'Hanya boleh ada satu superadmin.');
            }

            if (method_exists($creator, 'hasRole')) {
                try {
                    if ($creator->hasRole('admin') && RoleHelpers::canonical($this->role) !== 'user') {
                        $v->errors()->add('role', 'Admin hanya boleh membuat user biasa.');
                    }
                } catch (\Throwable) {
                    // ignore
                }
            }
        });
    }

    private function allowedRoles(): array
    {
        return RoleHelpers::canonicalizeList(['user', 'admin', 'superadmin']);
    }
}
