<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendWorkItemReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Kirim pengingat berkala untuk ticket, task, dan project aktif';

    public function __construct(private readonly ReminderService $reminderService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Memproses pengingat work item...');

        $this->reminderService->run();

        $this->info('Pengingat selesai diproses.');

        return self::SUCCESS;
    }
}
