<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class RoleHelpers
{
    /**
     * Normalize nama role menjadi bentuk ringkas (lowercase + tanpa spasi/underscore/dash).
     */
    public static function normalize(?string $role): ?string
    {
        if ($role === null) {
            return null;
        }

        $slug = Str::of($role)
            ->lower()
            ->trim()
            ->replaceMatches('/[\s_\-]+/', '');

        $value = $slug->toString();

        return $value === '' ? null : $value;
    }

    /**
     * Cek apakah nama role (dalam bentuk apa pun) adalah superadmin.
     */
    public static function isSuperAdminRole(?string $role): bool
    {
        return self::normalize($role) === 'superadmin';
    }

    /**
     * Konversi nama role ke bentuk kanonis yang konsisten.
     */
    public static function canonical(?string $role): ?string
    {
        $normalized = self::normalize($role);

        return match ($normalized) {
            null => null,
            'superadmin' => 'superadmin',
            'admin' => 'admin',
            'user' => 'user',
            default => $role !== null ? trim($role) : null,
        };
    }

    /**
     * Label siap tampil untuk nama role.
     */
    public static function displayLabel(?string $role): string
    {
        $normalized = self::normalize($role);

        return match ($normalized) {
            'superadmin' => 'Super Admin',
            'admin' => 'Admin',
            'user' => 'User',
            null => '—',
            default => Str::of($role ?? '')->trim()->title()->toString(),
        };
    }

    /**
     * Deteksi apakah user memiliki hak superadmin dengan berbagai fallback.
     */
    public static function userIsSuperAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            try {
                if ($user->hasAnyRole(['superadmin', 'Super Admin'])) {
                    return true;
                }
            } catch (\Throwable) {
                // abaikan error dari Spatie Permission
            }
        }

        if (method_exists($user, 'getRoleNames')) {
            try {
                foreach ($user->getRoleNames() as $name) {
                    if (self::isSuperAdminRole($name)) {
                        return true;
                    }
                }
            } catch (\Throwable) {
                // abaikan dan lanjutkan fallback berikutnya
            }
        }

        if (method_exists($user, 'roles') && $user->relationLoaded('roles')) {
            try {
                foreach ($user->roles as $role) {
                    if (self::isSuperAdminRole($role->name ?? null)) {
                        return true;
                    }
                }
            } catch (\Throwable) {
                // abaikan
            }
        }

        if (self::isSuperAdminRole($user->role ?? null)) {
            return true;
        }

        $guard = $user->superadmin_guard ?? null;
        if (is_string($guard)) {
            if (strtoupper(trim($guard)) === 'Y') {
                return true;
            }

            if (self::isSuperAdminRole($guard)) {
                return true;
            }
        }

        if (is_bool($guard)) {
            return $guard;
        }

        return false;
    }

    /**
     * Tambahkan sinonim role (misalnya Super Admin) ke daftar dan jadikan unik.
     *
     * @param  array<int,string>  $roles
     * @return array<int,string>
     */
    public static function canonicalizeList(array $roles): array
    {
        $items = [];

        foreach ($roles as $role) {
            if ($role === null) {
                continue;
            }

            $canonical = self::canonical($role) ?? trim($role);

            if ($canonical === '') {
                continue;
            }

            $items[$canonical] = $canonical;
        }

        return array_values($items);
    }
}
