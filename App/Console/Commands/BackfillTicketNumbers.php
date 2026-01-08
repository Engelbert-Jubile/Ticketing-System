<?php

namespace App\Console\Commands;

use App\Support\TicketNumberBackfill;
use Illuminate\Console\Command;

class BackfillTicketNumbers extends Command
{
    protected $signature = 'tickets:backfill-no {--dry-run : Hanya tampilkan jumlah yang akan diproses}';

    protected $description = 'Isi tickets.ticket_no yang kosong agar konsisten dan menghindari error routing.';

    public function handle(): int
    {
        $missing = TicketNumberBackfill::missingCount();

        if ($missing <= 0) {
            $this->info('Tidak ada ticket_no yang kosong.');

            return self::SUCCESS;
        }

        $this->line("Ditemukan {$missing} tickets tanpa ticket_no.");

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: tidak ada perubahan data.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Lanjutkan backfill ticket_no sekarang?', true)) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        $result = TicketNumberBackfill::backfillMissing();

        $this->info("Selesai. processed={$result['processed']} updated={$result['updated']} skipped={$result['skipped']}");

        $remaining = TicketNumberBackfill::missingCount();
        if ($remaining > 0) {
            $this->error("Masih ada {$remaining} ticket_no yang kosong.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

