<?php

namespace App\Support;

use App\Services\SettingsService;
use Illuminate\Validation\Rules\Password;

class SecurityPolicy
{
    public static function passwordRule(?SettingsService $settings = null): Password
    {
        $settings = $settings ?? app(SettingsService::class);
        $min = (int) $settings->get('security', 'password_min_length', 8);
        $min = $min > 0 ? $min : 8;

        $rule = Password::min($min);

        if ($settings->get('security', 'password_require_uppercase', false)) {
            $rule = $rule->mixedCase();
        }

        if ($settings->get('security', 'password_require_number', true)) {
            $rule = $rule->numbers();
        }

        if ($settings->get('security', 'password_require_symbol', false)) {
            $rule = $rule->symbols();
        }

        return $rule;
    }

    public static function allowedEmailDomains(?SettingsService $settings = null): array
    {
        $settings = $settings ?? app(SettingsService::class);
        $domains = $settings->get('security', 'allowed_email_domains', []);
        $domains = is_array($domains) ? $domains : [];

        $normalized = [];
        foreach ($domains as $domain) {
            if (! is_string($domain)) {
                continue;
            }
            $value = strtolower(trim($domain));
            $value = ltrim($value, '@');
            if ($value !== '') {
                $normalized[$value] = $value;
            }
        }

        if (empty($normalized)) {
            $normalized['kftd.co.id'] = 'kftd.co.id';
        }

        return array_values($normalized);
    }

    public static function emailDomainRule(?SettingsService $settings = null): callable
    {
        $domains = self::allowedEmailDomains($settings);

        return function (string $attribute, $value, $fail) use ($domains) {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            $email = strtolower(trim($value));
            $parts = explode('@', $email);
            $domain = count($parts) > 1 ? end($parts) : '';

            if ($domain === '' || ! in_array($domain, $domains, true)) {
                $fail('Email harus menggunakan domain yang diizinkan.');
            }
        };
    }

    public static function ipRestrictionsEnabled(?SettingsService $settings = null): bool
    {
        $settings = $settings ?? app(SettingsService::class);

        return (bool) config('features.ip_restrictions', false)
            && (bool) $settings->get('security', 'enforce_ip_restrictions', false);
    }

    public static function normalizedIpList($items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_string($item)) {
                continue;
            }
            $value = trim($item);
            if ($value !== '') {
                $normalized[$value] = $value;
            }
        }

        return array_values($normalized);
    }

    public static function ipAllowed(string $ip, ?SettingsService $settings = null): bool
    {
        $settings = $settings ?? app(SettingsService::class);

        $allowlist = self::normalizedIpList($settings->get('security', 'ip_allowlist', []));
        $blocklist = self::normalizedIpList($settings->get('security', 'ip_blocklist', []));

        if (in_array($ip, $blocklist, true)) {
            return false;
        }

        if (! empty($allowlist)) {
            return in_array($ip, $allowlist, true);
        }

        return true;
    }
}
