<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;

class RecaptchaVerifier
{
    private const VERIFY_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    public function isEnabled(): bool
    {
        return (bool) (
            config('security.recaptcha.enabled')
            && config('security.recaptcha.site_key')
            && config('security.recaptcha.secret')
        );
    }

    public function siteKey(): ?string
    {
        return config('security.recaptcha.site_key');
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        $response = Http::asForm()->post(self::VERIFY_ENDPOINT, [
            'secret' => config('security.recaptcha.secret'),
            'response' => $token,
            'remoteip' => $ip,
        ]);

        if (! $response->ok()) {
            return false;
        }

        $data = $response->json();

        return (bool) ($data['success'] ?? false);
    }
}
