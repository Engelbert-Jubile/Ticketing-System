<?php

namespace App\Support;

use App\Models\User;
use App\Services\SettingsService;

final class UserLocale
{
    public static function globalDefault(): string
    {
        $value = app(SettingsService::class)->get('general', 'locale', config('app.locale', 'en'));

        return in_array($value, ['en', 'id'], true) ? $value : 'en';
    }

    public static function resolve(?User $user): string
    {
        $preference = $user?->preferred_locale;

        return in_array($preference, ['en', 'id'], true) ? $preference : self::globalDefault();
    }
}
