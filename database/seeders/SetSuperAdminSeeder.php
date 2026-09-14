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

            // Configuration
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

            // Ensure required roles exist.
            foreach (['user', 'admin', 'superadmin'] as $roleName) {
                Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => $guard,
                ]);
            }

            // Normalize legacy role: super-admin -> superadmin.
            $legacyRole = Role::where('name', 'super-admin')
                ->where('guard_name', $guard)
                ->first();

            if ($legacyRole) {
                $legacyUsers = User::role('super-admin')->get();

                foreach ($legacyUsers as $legacyUser) {
                    $legacyUser->syncRoles(
                        $legacyUser->getRoleNames()
                            ->map(fn ($role) => $role === 'super-admin' ? 'superadmin' : $role)
                            ->unique()
                            ->values()
                            ->all()
                    );
                }

                $legacyRole->delete();
            }

            /*
             * Find the existing superadmin safely.
             *
             * Priority:
             * 1. Existing username
             * 2. Existing email
             * 3. Create new user
             */
            $user = User::where('username', $username)->first();

            if (! $user) {
                $user = User::where('email', $email)->first();
            }

            if (! $user) {
                $user = User::create([
                    'email' => $email,
                    'username' => $username,
                    'first_name' => $first,
                    'last_name' => $last,
                    'password' => $password,
                ]);
            } else {
                $updates = [];

                if ($user->email !== $email) {
                    $existingEmailUser = User::where('email', $email)
                        ->where('id', '!=', $user->id)
                        ->exists();

                    if (! $existingEmailUser) {
                        $updates['email'] = $email;
                    }
                }

                if ($user->username !== $username) {
                    $existingUsernameUser = User::where('username', $username)
                        ->where('id', '!=', $user->id)
                        ->exists();

                    if (! $existingUsernameUser) {
                        $updates['username'] = $username;
                    }
                }

                if ($user->first_name !== $first) {
                    $updates['first_name'] = $first;
                }

                if ($user->last_name !== $last) {
                    $updates['last_name'] = $last;
                }

                if (! Hash::check($password, $user->password)) {
                    $updates['password'] = $password;
                }

                if (! empty($updates)) {
                    $user->update($updates);
                    $user->refresh();
                }
            }

            /*
             * Ensure there is only one superadmin.
             * Any other superadmin becomes admin.
             */
            User::role('superadmin')
                ->where('id', '!=', $user->id)
                ->each(function (User $otherUser) {
                    $otherUser->syncRoles(['admin']);
                });

            // Make this user the only superadmin.
            $user->syncRoles(['superadmin']);
        });
    }
}