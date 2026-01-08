<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        $appName = (string) config('app.name', 'App');

        return (new MailMessage)
            ->subject("Verifikasi Email - {$appName}")
            ->greeting('Halo!')
            ->line('Klik tombol di bawah untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi Email', $url)
            ->line('Jika tombol tidak bisa diklik, salin dan buka link berikut di browser:')
            ->line($url)
            ->line('Jika Anda tidak membuat akun, abaikan email ini.');
    }
}

