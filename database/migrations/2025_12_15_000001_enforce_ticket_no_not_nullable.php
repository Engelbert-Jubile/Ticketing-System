<?php

use App\Support\TicketNumberBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets') || ! Schema::hasColumn('tickets', 'ticket_no')) {
            return;
        }

        // Backfill dulu supaya alter NOT NULL tidak gagal.
        if (TicketNumberBackfill::missingCount() > 0) {
            TicketNumberBackfill::backfillMissing();
        }

        $remaining = TicketNumberBackfill::missingCount();
        if ($remaining > 0) {
            throw new RuntimeException("Tidak bisa enforce constraint: masih ada {$remaining} ticket_no kosong.");
        }

        Schema::table('tickets', function (Blueprint $table) {
            // doctrine/dbal tersedia di project ini, jadi change() aman.
            $table->string('ticket_no', 40)->nullable(false)->change();
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $indexes = DB::select("SHOW INDEX FROM `tickets` WHERE Key_name = 'tickets_ticket_no_unique'");
            if (empty($indexes)) {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->unique('ticket_no');
                });
            }
        } elseif ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('tickets')");
            $hasUnique = false;
            foreach ($indexes as $idx) {
                $name = $idx->name ?? null;
                if (is_string($name) && str_contains($name, 'ticket_no')) {
                    $hasUnique = true;
                    break;
                }
            }
            if (! $hasUnique) {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->unique('ticket_no');
                });
            }
        } else {
            // Best-effort untuk driver lain: coba buat unique index, abaikan jika sudah ada.
            try {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->unique('ticket_no');
                });
            } catch (\Throwable) {
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets') || ! Schema::hasColumn('tickets', 'ticket_no')) {
            return;
        }

        // Jangan dibuat nullable lagi; cukup drop unique agar rollback tidak berisiko kehilangan integrity.
        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropUnique(['ticket_no']);
            });
        } catch (\Throwable) {
        }
    }
};

