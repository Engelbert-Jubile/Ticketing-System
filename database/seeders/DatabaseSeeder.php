<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\AppSettingsSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1) Buat/ambil user admin tester (password akan di-hash oleh cast 'hashed')
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'], // kunci unik yang pasti ada
            [
                'username' => 'admin',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email_verified_at' => now(),
                'password' => 'password', // plain; model akan auto-hash
            ]
        );

        // 2) Jalankan seeders lain (roles/permissions, statuses, dan superadmin)
        $this->call([
            RolePermissionSeeder::class, // pastikan roles 'user|admin|superadmin' ada (guard 'web')
            StatusSeeder::class,
            SetSuperAdminSeeder::class,  // memastikan superadmin utama sesuai konfigurasi
            AppSettingsSeeder::class,
        ]);

        // 3) Pastikan user tester berperan sebagai 'admin' (bukan superadmin)
        $user->syncRoles(['admin']);
    }
}
