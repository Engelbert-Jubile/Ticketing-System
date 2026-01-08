<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Debug: check superadmin record & password
Artisan::command('check:superadmin {email?} {password?}', function (?string $email = null, ?string $password = null) {
    $email = $email ?: 'superadmin@gmail.com';
    $password = $password ?: 'password12345';

    $user = \App\Models\User::where('email', $email)->first();
    if (! $user) {
        $this->error("User not found: {$email}");

        return 1;
    }

    $this->line('id: '.$user->id);
    $this->line('email: '.$user->email);
    $this->line('username: '.($user->username ?? ''));
    $this->line('email_verified_at: '.($user->email_verified_at ?? 'null'));
    $roles = method_exists($user, 'getRoleNames') ? implode(',', $user->getRoleNames()->toArray()) : '-';
    $this->line('roles: '.$roles);

    $ok = \Illuminate\Support\Facades\Hash::check($password, (string) $user->password);
    $this->line('Hash::check(password) => '.($ok ? 'true' : 'false'));

    // Validate via Auth provider
    $valid = \Illuminate\Support\Facades\Auth::validate(['email' => $email, 'password' => $password]);
    $this->line('Auth::validate(credentials) => '.($valid ? 'true' : 'false'));

    return 0;
})->purpose('Debug superadmin password and roles');
