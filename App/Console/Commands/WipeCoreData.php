<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WipeCoreData extends Command
{
    protected $signature = 'app:wipe-core
        {--yes : Jalankan tanpa konfirmasi}
        {--also-comments : Sekalian hapus tabel komentar bila ada}
        {--also-attachments : Sekalian hapus lampiran bila ada}';

    protected $description = 'Menghapus SEMUA data Ticket, Task, Project (dan relasinya yang relevan) secara aman.';

    public function handle(): int
    {
        $targets = [
            'ticket_assignees', // pivot
            'tasks',
            'projects',
            'tickets',
        ];

        if ($this->option('also-comments')) {
            array_unshift($targets, 'comments');
        }
        if ($this->option('also-attachments')) {
            // urutan: relation tables/attachments dulu
            array_unshift($targets, 'attachments');
            array_unshift($targets, 'attachment_versions');
            array_unshift($targets, 'attachment_links');
        }

        $existing = array_values(array_filter($targets, fn ($t) => Schema::hasTable($t)));

        if (empty($existing)) {
            $this->warn('Tidak ada tabel target yang ditemukan.');

            return self::SUCCESS;
        }

        $this->line('Tabel yang akan DIKOSONGKAN:');
        foreach ($existing as $t) {
            $this->line("- {$t} (".DB::table($t)->count().' baris)');
        }

        if (! $this->option('yes')) {
            if (! $this->confirm('Lanjutkan wipe SEMUA data di atas?', false)) {
                $this->warn('Dibatalkan. Tidak ada perubahan.');

                return self::SUCCESS;
            }
        }

        DB::beginTransaction();
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            foreach ($existing as $t) {
                DB::table($t)->truncate();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal wipe data: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Selesai. Semua data inti telah dikosongkan.');

        return self::SUCCESS;
    }
}
