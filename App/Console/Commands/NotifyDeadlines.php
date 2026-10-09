<?php

namespace App\Console\Commands;

use App\Services\DeadlineNotifier;
use Illuminate\Console\Command;

class NotifyDeadlines extends Command
{
    protected $signature = 'deadlines:notify';

    protected $description = 'Create deduplicated in-app alerts for upcoming and overdue work';

    public function handle(DeadlineNotifier $service): int
    {
        $this->info($service->run().' notifikasi deadline dibuat.');

        return self::SUCCESS;
    }
}
