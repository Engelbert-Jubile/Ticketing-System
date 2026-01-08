<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneReadNotifications extends Command
{
    protected $signature = 'notifications:prune-read {--days=7 : Hapus notifikasi yang sudah dibaca lebih dari N hari}';

    protected $description = 'Hapus notifikasi yang read_at lebih lama dari N hari (default 7).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cut = now()->subDays(max($days, 1));

        $count = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<', $cut)
            ->delete();

        $this->info("Pruned {$count} notifications read before {$cut}.");

        return self::SUCCESS;
    }
}
