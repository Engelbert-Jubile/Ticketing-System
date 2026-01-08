<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDueDate = Schema::hasColumn('tickets', 'due_date');
        $hasFinishDate = Schema::hasColumn('tickets', 'finish_date');

        // Tambah kolom baru dengan 'after' hanya jika kolom referensi ada
        Schema::table('tickets', function (Blueprint $table) use ($hasDueDate, $hasFinishDate) {
            if (! Schema::hasColumn('tickets', 'due_at')) {
                $col = $table->dateTime('due_at')->nullable();
                if ($hasDueDate) {
                    $col->after('due_date');
                }
            }
            if (! Schema::hasColumn('tickets', 'finish_at')) {
                $col = $table->dateTime('finish_at')->nullable();
                if ($hasFinishDate) {
                    $col->after('finish_date');
                }
            }
        });

        // Backfill hanya jika kolom sumber ada
        if ($hasDueDate && Schema::hasColumn('tickets', 'due_at')) {
            DB::table('tickets')
                ->whereNull('due_at')
                ->whereNotNull('due_date')
                ->update([
                    // aman di MySQL/MariaDB; cast ke DATETIME
                    'due_at' => DB::raw("CAST(CONCAT(due_date, ' 00:00:00') AS DATETIME)"),
                ]);
        }

        if ($hasFinishDate && Schema::hasColumn('tickets', 'finish_at')) {
            DB::table('tickets')
                ->whereNull('finish_at')
                ->whereNotNull('finish_date')
                ->update([
                    'finish_at' => DB::raw("CAST(CONCAT(finish_date, ' 00:00:00') AS DATETIME)"),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'due_at')) {
                $table->dropColumn('due_at');
            }
            if (Schema::hasColumn('tickets', 'finish_at')) {
                $table->dropColumn('finish_at');
            }
        });
    }
};
