<?php

namespace App\Policies;

use App\Models\User;
use App\Support\RoleHelpers;

class UserPolicy
{
    /**
     * God mode: superadmin boleh SEMUA ability pada model User.
     * Menghindari duplikasi pengecekan di tiap method.
     */
    public function before(User $actor, string $ability)
    {
        if ($ability === 'delete') {
            return null;
        }

        if (RoleHelpers::userIsSuperAdmin($actor)) {
            return true;
        }

        return null; // lanjut ke method spesifik
    }

    /**
     * Lihat daftar user
     * admin & superadmin boleh (superadmin sudah lolos via before()).
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasRole('admin');
    }

    /**
     * Lihat detail user
     * admin boleh lihat siapa saja; user boleh lihat dirinya sendiri.
     */
    public function view(User $actor, User $target): bool
    {
        return $actor->hasRole('admin') || $actor->id === $target->id;
    }

    /**
     * Membuat user:
     * admin boleh (superadmin lewat before()).
     */
    public function create(User $actor): bool
    {
        return $actor->hasRole('admin');
    }

    /**
     * Update (edit) user:
     * - admin hanya boleh edit user biasa (role 'user') atau dirinya sendiri.
     * - superadmin sudah diizinkan via before().
     */
    public function update(User $actor, User $target): bool
    {
        if (RoleHelpers::userIsSuperAdmin($actor)) {
            return true;
        }

        if ($actor->hasRole('admin')) {
            return $target->hasRole('user') || $actor->id === $target->id;
        }

        return false;
    }

    /**
     * Delete user:
     * - admin boleh hapus user biasa (bukan dirinya sendiri).
     * - superadmin sudah diizinkan via before(), tapi demi safety tidak boleh hapus superadmin lain.
     */
    public function delete(User $actor, User $target): bool
    {
        if (RoleHelpers::userIsSuperAdmin($target)) {
            return false;
        }

        if (RoleHelpers::userIsSuperAdmin($actor)) {
            return true;
        }

        if ($actor->hasRole('admin')) {
            return $target->hasRole('user') && $target->id !== $actor->id;
        }

        return false;
    }

    /**
     * Assign role:
     * - superadmin bebas via before().
     * - admin boleh assign 'admin' atau 'user' dan TIDAK boleh menetapkan 'superadmin'.
     *   Admin hanya boleh mengubah role user biasa atau dirinya sendiri.
     */
    public function assignRole(User $actor, User $target): bool
    {
        if (RoleHelpers::userIsSuperAdmin($actor)) {
            return true;
        }

        if (! $actor->hasRole('admin')) {
            return false;
        }

        $requestedRole = request()->input('role') ?? $target->getRoleNames()->first();
        if ($target->id === $actor->id) {
            return RoleHelpers::canonical($requestedRole) === 'admin';
        }

        return RoleHelpers::canonical($requestedRole) === 'user' && $target->hasRole('user');
    }
}
