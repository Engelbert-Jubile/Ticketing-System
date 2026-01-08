<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Reset cache permission
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2) Buat daftar permission yang relevan untuk ticketing/project/task
        $permissions = [
            // Ticket
            'view tickets',
            'create tickets',
            'update tickets',
            'delete tickets',
            'assign tickets',

            // Task
            'view tasks',
            'create tasks',
            'update tasks',
            'delete tasks',

            // Project
            'view projects',
            'create projects',
            'update projects',
            'delete projects',

            // User management (admin-ish)
            'view users',
            'create users',
            'update users',
            'delete users',
            'assign roles', // hati-hati: kontrol di aplikasi siapa boleh assign super-admin

            // Misc / reporting
            'view reports',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName]);
        }

        // 3) Clear cache sebelum assign role
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 4) Buat roles
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // 5) Assign permissions:
        // super-admin -> semua permission
        $superAdmin->givePermissionTo(Permission::all());

        // admin -> subset (boleh sesuaikan sesuai kebijakan)
        $admin->givePermissionTo([
            'view tickets', 'create tickets', 'update tickets', 'delete tickets', 'assign tickets',
            'view tasks', 'create tasks', 'update tasks', 'delete tasks',
            'view projects', 'create projects', 'update projects', 'delete projects',
            'view users', 'create users', 'update users', 'delete users',
            'view reports',
            // jangan berikan 'assign roles' ke admin bila kamu tidak ingin admin assign role sensitif
        ]);

        // user -> permission minimal
        $userRole->givePermissionTo([
            'view tickets', 'create tickets', 'view projects', 'view tasks',
        ]);

        // 6) Jika belum ada super-admin di DB, buat satu akun super-admin awal
        // Ambil kredensial dari .env jika tersedia, jika tidak gunakan default (ubah segera)
        $superEmail = env('SUPERADMIN_EMAIL', 'superadmin@example.com');
        $superPass = env('SUPERADMIN_PASSWORD', 'ChangeMe123!');

        if (! User::role('superadmin')->exists()) {
            $user = User::firstOrCreate(
                ['email' => $superEmail],
                [
                    // sesuaikan fields users table (username/first_name/last_name dsb)
                    'username' => 'superadmin',
                    'first_name' => 'Super',
                    'last_name' => 'Admin',
                    'password' => Hash::make($superPass),
                ]
            );

            // pastikan ter-assign role super-admin
            if (! $user->hasRole('superadmin')) {
                $user->assignRole('superadmin');
            }
        }

        // 7) Clear cache di akhir
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
