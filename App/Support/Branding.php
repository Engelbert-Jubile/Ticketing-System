<?php

namespace App\Support;

final class Branding
{
    public const NAME = 'TICKME!';

    public const TAGLINE = 'Ticketing, Integration & Collaboration – KFTD Project Management Ecosystem';

    public const TITLE = self::NAME.' - '.self::TAGLINE;

    public const LOGO = 'branding/tickme-horizontal.svg';

    public const PRINT_LOGO = 'branding/tickme-horizontal.jpg';

    public const STACKED_LOGO = 'branding/tickme-stacked.jpg';

    public static function normalizeName(mixed $name): string
    {
        return match (strtolower(trim((string) $name))) {
            '', 'tickora', 'tickora - ticket management system', 'tactic',
            'tactic - ticket integrated center', 'task & ticket integrated center',
            'tactic - task & ticket integrated center', 'tickme!' => self::NAME,
            default => trim((string) $name),
        };
    }

    public static function shared(): array
    {
        return [
            'name' => config('app.name'),
            'tagline' => self::TAGLINE,
            'title' => self::TITLE,
            'logo' => self::logoUrl(),
            'stacked_logo' => self::logoUrl(stacked: true),
        ];
    }

    public static function logoUrl(bool $stacked = false): string
    {
        $path = $stacked ? self::STACKED_LOGO : self::LOGO;

        return asset($path).'?v='.filemtime(public_path($path));
    }
}
