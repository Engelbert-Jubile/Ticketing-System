<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class InitRolesAndSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan roles Spatie tersedia
        Role::firstOrCreate(['name' => 'user']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'super-admin']);

        // Jika sudah ada super-admin, sinkronkan kolom enum lalu selesai (jangan turunkan)
        $existing = User::role('super-admin')->first();
        if ($existing) {
            if ($existing->role !== 'superadmin') {
                $existing->update(['role' => 'superadmin']);
            }

            return;
        }

        // Tidak ada super-admin → pakai ENV jika ada, kalau tidak, pilih kandidat pertama yang ada
        $targetEmail = env('SUPERADMIN_EMAIL'); // opsional
        $candidate = $targetEmail
            ? User::where('email', $targetEmail)->first()
            : User::orderBy('id')->first();

        if ($candidate) {
            $candidate->syncRoles(['super-admin']);     // Spatie
            $candidate->update(['role' => 'superadmin']); // enum users.role
        }
    }
}
