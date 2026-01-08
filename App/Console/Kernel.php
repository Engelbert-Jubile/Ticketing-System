<?php

namespace App\Console;

use App\Console\Commands\SendWorkItemReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        SendWorkItemReminders::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Hapus notifikasi yang sudah dibaca > 7 hari, jalan tiap malam
        $schedule->command('notifications:prune-read --days=7')->dailyAt('01:30');

        // Bersihkan tmp attachments (orphaned files + stale records), setiap jam
        $hours = (int) (env('TMP_UPLOAD_RETENTION_HOURS', 24));
        $schedule->command("attachments:cleanup-tmp --hours={$hours}")->hourly();

        // Kirim pengingat work item secara berkala (setiap pagi)
        $schedule->command('reminders:send')->dailyAt('08:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
