<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Normalisasi nilai lama
        try {
            DB::statement("UPDATE tickets SET status='new'  WHERE status IN ('open')");
        } catch (\Throwable $e) {
        }
        try {
            DB::statement("UPDATE tickets SET status='done' WHERE status IN ('closed')");
        } catch (\Throwable $e) {
        }

        // Ganti jadi VARCHAR agar fleksibel
        DB::statement("ALTER TABLE tickets MODIFY status VARCHAR(20) NOT NULL DEFAULT 'new'");

        // CHECK (opsional, MySQL 8+)
        try {
            DB::statement("ALTER TABLE tickets
                           ADD CONSTRAINT chk_tickets_status
                           CHECK (status IN ('new','on_progress','done'))");
        } catch (\Throwable $e) {
        }

        // Index untuk dashboard
        try {
            DB::statement('CREATE INDEX idx_tickets_status ON tickets(status)');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE tickets DROP CHECK chk_tickets_status');
        } catch (\Throwable $e) {
        }
        try {
            DB::statement('DROP INDEX idx_tickets_status ON tickets');
        } catch (\Throwable $e) {
        }
    }
};
