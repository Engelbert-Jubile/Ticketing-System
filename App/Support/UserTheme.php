<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class UserTheme
{
    public const PUBLIC_SESSION_KEY = 'public.theme_preferences';

    public static function normalize(mixed $preferences): array
    {
        $preferences = is_array($preferences) ? $preferences : [];

        return [
            'appearance' => ($preferences['appearance'] ?? null) === 'glassy' ? 'glassy' : 'default',
            'color_mode' => ($preferences['color_mode'] ?? null) === 'dark' ? 'dark' : 'light',
        ];
    }

    public static function forUser(?User $user): array
    {
        return self::normalize($user?->theme_preferences);
    }

    public static function context(Request $request): array
    {
        $user = $request->user();

        return [
            'user_id' => $user?->getKey(),
            'preferences' => $user ? self::forUser($user) : self::normalize(
                $request->hasSession() ? $request->session()->get(self::PUBLIC_SESSION_KEY) : null
            ),
            'save_url' => $user ? route('account.theme.update', ['locale' => app()->getLocale()]) : null,
        ];
    }
}
