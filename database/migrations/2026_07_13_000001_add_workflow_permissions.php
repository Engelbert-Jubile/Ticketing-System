<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view workflows',
        'create workflows',
        'update workflows',
        'toggle workflows',
        'delete workflows',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        $user->givePermissionTo(['view workflows']);
        $admin->givePermissionTo(['view workflows', 'create workflows', 'update workflows', 'toggle workflows']);
        $superadmin->givePermissionTo(self::PERMISSIONS);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()->whereIn('name', ['user', 'admin', 'superadmin'])->get()
            ->each(fn (Role $role) => $role->revokePermissionTo(
                Permission::query()->whereIn('name', self::PERMISSIONS)->get()
            ));

        Permission::query()->whereIn('name', self::PERMISSIONS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
