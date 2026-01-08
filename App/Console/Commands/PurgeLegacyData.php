<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurgeLegacyData extends Command
{
    protected $signature = 'app:purge-legacy
        {--table=all : Pilih tabel: tasks|tickets|projects|all}
        {--yes : Jalankan tanpa konfirmasi}';

    protected $description = 'Hapus data lama yang memiliki status di luar workflow baru agar tidak mengganggu.';

    public function handle(): int
    {
        $tables = $this->option('table') === 'all'
            ? ['tasks', 'tickets', 'projects']
            : [strtolower((string) $this->option('table'))];

        $tables = array_values(array_intersect($tables, ['tasks', 'tickets', 'projects']));
        if (empty($tables)) {
            $this->error('Opsi --table tidak valid. Gunakan tasks|tickets|projects|all');

            return self::INVALID;
        }

        $totalToDelete = 0;
        $plan = [];

        foreach ($tables as $t) {
            if (! Schema::hasTable($t)) {
                $this->warn("Tabel {$t} tidak ada — dilewati.");

                continue;
            }

            switch ($t) {
                case 'tasks':
                    $allowed = ['new', 'in_progress', 'confirmation', 'revision', 'done'];
                    $legacy = DB::table('tasks')->when(Schema::hasColumn('tasks', 'status'), function ($q) use ($allowed) {
                        $q->whereNotIn('status', $allowed)
                            ->orWhereIn('status', ['completed', 'pending']);
                    });
                    $count = (clone $legacy)->count();
                    $plan[] = ['tasks', $count, $legacy];
                    $totalToDelete += $count;
                    break;
                case 'tickets':
                    $allowed = ['new', 'in_progress', 'confirmation', 'revision', 'done'];
                    $legacy = DB::table('tickets')->when(Schema::hasColumn('tickets', 'status'), function ($q) use ($allowed) {
                        $q->whereNotIn('status', $allowed)
                            ->orWhereIn('status', ['on_progress']);
                    });
                    $count = (clone $legacy)->count();
                    $plan[] = ['tickets', $count, $legacy];
                    $totalToDelete += $count;
                    break;
                case 'projects':
                    $allowed = ['in_progress', 'completed'];
                    $legacy = DB::table('projects')->when(Schema::hasColumn('projects', 'status'), function ($q) use ($allowed) {
                        $q->whereNotIn('status', $allowed);
                    });
                    $count = (clone $legacy)->count();
                    $plan[] = ['projects', $count, $legacy];
                    $totalToDelete += $count;
                    break;
            }
        }

        if ($totalToDelete === 0) {
            $this->info('Tidak ada data legacy yang perlu dihapus.');

            return self::SUCCESS;
        }

        $this->line('Ringkasan data legacy yang akan dihapus:');
        foreach ($plan as [$name, $count]) {
            $this->line("- {$name}: {$count} baris");
        }

        if (! $this->option('yes')) {
            if (! $this->confirm('Lanjutkan hapus data di atas?', false)) {
                $this->warn('Dibatalkan. Tidak ada data yang dihapus.');

                return self::SUCCESS;
            }
        }

        foreach ($plan as [$name, $count, $query]) {
            if ($count > 0) {
                $deleted = $query->delete();
                $this->info("{$name}: {$deleted} baris dihapus.");
            }
        }

        return self::SUCCESS;
    }
}
