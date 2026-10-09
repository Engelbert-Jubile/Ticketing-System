<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class PasswordRecoveryNotification extends ResetPassword
{
    public function __construct(string $token, private string $resetLocale)
    {
        parent::__construct($token);
    }

    protected function resetUrl($notifiable)
    {
        return route('password.reset', ['locale' => $this->resetLocale, 'token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }
}
