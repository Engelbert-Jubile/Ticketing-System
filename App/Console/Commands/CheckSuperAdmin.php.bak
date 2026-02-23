<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CheckSuperAdmin extends Command
{
    protected $signature = 'check:superadmin {--email=superadmin@gmail.com} {--password=password12345}';

    protected $description = 'Debug: show superadmin record and verify password match';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $plain = (string) $this->option('password');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User not found: {$email}");

            return self::FAILURE;
        }

        $this->info('User found');
        $this->line('id: '.$user->id);
        $this->line('email: '.$user->email);
        $this->line('username: '.($user->username ?? ''));
        $this->line('email_verified_at: '.($user->email_verified_at ?? 'null'));
        $this->line('has roles: '.(method_exists($user, 'getRoleNames') ? implode(',', $user->getRoleNames()->toArray()) : '-'));

        $ok = Hash::check($plain, (string) $user->password);
        $this->line('Hash::check(password) => '.($ok ? 'true' : 'false'));

        return self::SUCCESS;
    }
}
