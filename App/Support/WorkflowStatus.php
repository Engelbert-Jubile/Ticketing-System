<?php

namespace App\Support;

final class WorkflowStatus
{
    /**
     * Urutan tampilan standar status workflow.
     */
    private const ORDER = [
        self::NEW,
        self::IN_PROGRESS,
        self::CONFIRMATION,
        self::REVISION,
        self::DONE,
        self::ON_HOLD,
        self::CANCELLED,
    ];

    public const NEW = 'new';

    public const IN_PROGRESS = 'in_progress';

    public const CONFIRMATION = 'confirmation';

    public const REVISION = 'revision';

    public const DONE = 'done';

    public const CANCELLED = 'cancelled';

    public const ON_HOLD = 'on_hold';

    private const CODE_MAP = [
        self::NEW => 'NEW',
        self::IN_PROGRESS => 'INPR',
        self::CONFIRMATION => 'CONF',
        self::REVISION => 'REVS',
        self::DONE => 'DONE',
        self::ON_HOLD => 'HOLD',
        self::CANCELLED => 'CANC',
    ];

    private const LABEL_MAP = [
        self::NEW => 'New',
        self::IN_PROGRESS => 'In Progress',
        self::CONFIRMATION => 'Confirmation',
        self::REVISION => 'Revision',
        self::DONE => 'Done',
        self::ON_HOLD => 'On Hold',
        self::CANCELLED => 'Cancelled',
    ];

    /** @return array<string,string> */
    public static function codes(): array
    {
        return self::orderedMap(self::CODE_MAP);
    }

    public static function code(string $status): string
    {
        $normalized = self::normalize($status);

        return self::codes()[$normalized]
            ?? strtoupper(substr(str_replace([' ', '_'], '', $normalized), 0, 4));
    }

    /** Legacy aliases map -> normalized status */
    private const ALIASES = [
        'on_progress' => self::IN_PROGRESS,
        'inprogress' => self::IN_PROGRESS,
        'pending' => self::IN_PROGRESS,
        'completed' => self::DONE,
        'complete' => self::DONE,
        'canc' => self::CANCELLED,
        'cancel' => self::CANCELLED,
        'hold' => self::ON_HOLD,
    ];

    /** @return string[] */
    public static function all(): array
    {
        return self::ORDER;
    }

    /** @return array<string,string> */
    public static function labels(): array
    {
        return self::orderedMap(self::LABEL_MAP);
    }

    public static function label(string $status): string
    {
        $normalized = self::normalize($status);

        return self::labels()[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized));
    }

    /** Normalize status (termasuk alias legacy) ke salah satu konstanta */
    public static function normalize(?string $status): string
    {
        $status = strtolower((string) $status);
        if (isset(self::ALIASES[$status])) {
            return self::ALIASES[$status];
        }

        return in_array($status, self::all(), true) ? $status : self::NEW;
    }

    /** Status yang ekuivalen (mis. untuk query with legacy values) */
    public static function equivalents(string $status): array
    {
        $normalized = self::normalize($status);
        $map = [
            self::NEW => ['new'],
            self::IN_PROGRESS => ['in_progress', 'on_progress', 'pending'],
            self::CONFIRMATION => ['confirmation'],
            self::REVISION => ['revision'],
            self::DONE => ['done', 'completed'],
            self::ON_HOLD => ['on_hold'],
            self::CANCELLED => ['cancelled'],
        ];

        return $map[$normalized] ?? [$normalized];
    }

    /** Mendapatkan kelas badge standar untuk status tertentu */
    public static function badgeClass(string $status): string
    {
        return match (self::normalize($status)) {
            self::IN_PROGRESS => 'bg-amber-200 text-amber-900 ring-amber-300 dark:bg-amber-500 dark:text-slate-900 dark:ring-amber-300',
            self::CONFIRMATION => 'bg-fuchsia-200 text-fuchsia-900 ring-fuchsia-300 dark:bg-fuchsia-600 dark:text-white dark:ring-fuchsia-400',
            self::REVISION => 'bg-violet-200 text-violet-900 ring-violet-300 dark:bg-violet-600 dark:text-white dark:ring-violet-400',
            self::DONE => 'bg-emerald-200 text-emerald-900 ring-emerald-300 dark:bg-emerald-500 dark:text-white dark:ring-emerald-300',
            self::CANCELLED => 'bg-rose-200 text-rose-900 ring-rose-300 dark:bg-rose-600 dark:text-white dark:ring-rose-400',
            self::ON_HOLD => 'bg-pink-200 text-pink-900 ring-pink-300 dark:bg-pink-500 dark:text-white dark:ring-pink-400',
            default => 'bg-blue-200 text-blue-900 ring-blue-300 dark:bg-blue-600 dark:text-white dark:ring-blue-400',
        };
    }

    /** Status yang dapat diubah oleh Agent / Assigned Agent */
    public static function agentAllowed(): array
    {
        return [self::IN_PROGRESS, self::CONFIRMATION];
    }

    /** Status yang dapat diubah oleh Requester */
    public static function requesterAllowed(): array
    {
        return [self::REVISION, self::DONE, self::CANCELLED, self::ON_HOLD];
    }

    public static function default(): string
    {
        return self::NEW;
    }

    /**
     * Mengurutkan map sesuai self::ORDER.
     *
     * @param  array<string,string>  $map
     * @return array<string,string>
     */
    private static function orderedMap(array $map): array
    {
        $ordered = [];
        foreach (self::ORDER as $status) {
            if (isset($map[$status])) {
                $ordered[$status] = $map[$status];
            }
        }

        return $ordered;
    }
}
