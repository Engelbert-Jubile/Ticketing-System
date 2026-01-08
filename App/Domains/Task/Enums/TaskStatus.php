<?php

namespace App\Domains\Task\Enums;

/**
 * Enum untuk status task.
 * Gunakan ini untuk menghindari hard-code string status
 * dan memastikan konsistensi di seluruh aplikasi.
 */
enum TaskStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Confirmation = 'confirmation';
    case Revision = 'revision';
    case Done = 'done';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';

    /**
     * Ambil semua nilai status dalam bentuk array string.
     * Contoh hasil: ['new','pending','in_progress','completed']
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status) => $status->value,
            self::cases()
        );
    }

    /**
     * Ambil label yang lebih ramah dibaca manusia.
     * Contoh: 'in_progress' => 'In Progress'
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In Progress',
            self::Confirmation => 'Confirmation',
            self::Revision => 'Revision',
            self::Done => 'Done',
            self::OnHold => 'On Hold',
            self::Cancelled => 'Cancelled',
        };
    }
}
