<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        $supported = config('app.supported_locales', ['en', 'id']);
        $locale = is_string($notifiable->locale ?? null) ? $notifiable->locale : null;
        if (! $locale || ! in_array($locale, $supported, true)) {
            $locale = app()->getLocale();
        }
        if (! is_string($locale) || ! in_array($locale, $supported, true)) {
            $locale = config('app.locale', 'en');
        }

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(
                Config::get('auth.verification.expire', 60)
            ),
            [
                'locale' => $locale,
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

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
