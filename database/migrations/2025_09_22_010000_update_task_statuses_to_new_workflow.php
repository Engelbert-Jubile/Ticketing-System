<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Normalisasi nilai status lama ke workflow baru
        DB::statement("UPDATE tasks SET status = 'new' WHERE status IN ('pending','new','') OR status IS NULL");
        DB::statement("UPDATE tasks SET status = 'in_progress' WHERE status IN ('in_progress','on_progress')");
        DB::statement("UPDATE tasks SET status = 'confirmation' WHERE status = 'confirmation'");
        DB::statement("UPDATE tasks SET status = 'revision' WHERE status = 'revision'");
        DB::statement("UPDATE tasks SET status = 'done' WHERE status IN ('completed','done')");

        // Drop constraint lama jika ada
        try {
            DB::statement('ALTER TABLE tasks DROP CHECK IF EXISTS chk_tasks_status');
        } catch (\Throwable $e) {
            // MySQL <8 tidak mengenal DROP CHECK, lanjut saja
        }

        // Pastikan kolom bertipe VARCHAR dengan default 'new'
        DB::statement("ALTER TABLE tasks MODIFY status VARCHAR(32) NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        // Tidak perlu mengembalikan detail constraint lama; cukup default ke in_progress
        DB::statement("ALTER TABLE tasks MODIFY status VARCHAR(32) NOT NULL DEFAULT 'in_progress'");
    }
};
