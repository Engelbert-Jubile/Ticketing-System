<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SetSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $guard = 'web';

            // Konfigurasi default (bisa override via .env)
            $email = env('SUPERADMIN_EMAIL', 'superadmin@gmail.com');
            $username = env('SUPERADMIN_USERNAME', 'superadmin');
            $passwordEnv = env('SUPERADMIN_PASSWORD');
            $password = is_string($passwordEnv) && trim($passwordEnv) !== ''
                ? trim($passwordEnv)
                : 'superadmin12345';

            if ($password === 'password12345') {
                $password = 'superadmin12345';
            }
            $first = env('SUPERADMIN_FIRST', 'Super');
            $last = env('SUPERADMIN_LAST', 'Admin');

            // Pastikan roles ada
            foreach (['user', 'admin', 'superadmin'] as $r) {
                Role::firstOrCreate(['name' => $r, 'guard_name' => $guard]);
            }

            // Normalisasi role lama "super-admin" -> "superadmin"
            $legacy = Role::where('name', 'super-admin')->where('guard_name', $guard)->first();
            if ($legacy) {
                $usersLegacy = User::role('super-admin')->get();
                foreach ($usersLegacy as $u) {
                    $u->syncRoles(
                        $u->getRoleNames()
                            ->map(fn ($n) => $n === 'super-admin' ? 'superadmin' : $n)
                            ->unique()
                            ->values()
                            ->all()
                    );
                }
                $legacy->delete();
            }

            // Buat / ambil superadmin (password di-hash oleh cast 'hashed' di model)
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'username' => $username,
                    'first_name' => $first,
                    'last_name' => $last,
                    'password' => $password, // plain; cast akan meng-hash
                ]
            );

            $updates = [];
            if ($user->username !== $username) {
                $updates['username'] = $username;
            }
            if (($user->first_name !== $first) || ($user->last_name !== $last)) {
                $updates['first_name'] = $first;
                $updates['last_name'] = $last;
            }
            if (! Hash::check($password, $user->password)) {
                $updates['password'] = $password;
            }

            if (! empty($updates)) {
                $user->update($updates);
                $user->refresh();
            }

            // Pastikan hanya satu superadmin
            User::role('superadmin')
                ->where('id', '!=', $user->id)
                ->each(fn ($u) => $u->syncRoles(['admin']));

            $user->syncRoles(['superadmin']);
        });
    }
}
