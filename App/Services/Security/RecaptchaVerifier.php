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

    public function verify(?string $token, ?string $ip = null, ?string $expectedAction = null): bool
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

        $success = (bool) ($data['success'] ?? false);
        if (! $success) {
            logger()->warning('recaptcha_verify_failed', ['data' => $data]);
            return false;
        }

        // Optional hardening untuk v3:
        $action = $data['action'] ?? null;
        $score = (float) ($data['score'] ?? 0);

        // kalau expectedAction diset dan action tidak cocok, anggap gagal
        if ($expectedAction !== null && $action !== null && $action !== $expectedAction) {
            logger()->warning('recaptcha_action_mismatch', [
                'expected' => $expectedAction,
                'got' => $action,
                'data' => $data,
            ]);
            return false;
        }

        // threshold score (silakan atur 0.3–0.7 sesuai kebutuhan)
        $minScore = (float) config('security.recaptcha.min_score', 0.5);
        if ($score > 0 && $score < $minScore) {
            logger()->warning('recaptcha_low_score', ['score' => $score, 'min' => $minScore, 'data' => $data]);
            return false;
        }

        return true;
    }

}
