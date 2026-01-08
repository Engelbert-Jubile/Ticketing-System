<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Support\TicketStatusSync;
use Illuminate\Console\Command;

class ResyncTicketStatus extends Command
{
    protected $signature = 'tickets:resync-status';

    protected $description = 'Rescan & sync ticket status from related projects/tasks';

    public function handle(): int
    {
        $bar = $this->output->createProgressBar(Ticket::count());
        Ticket::chunk(200, function ($chunk) use ($bar) {
            foreach ($chunk as $t) {
                TicketStatusSync::rescanTicket((int) $t->id);
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
