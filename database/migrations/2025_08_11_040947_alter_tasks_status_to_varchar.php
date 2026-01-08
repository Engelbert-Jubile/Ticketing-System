<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah ENUM -> VARCHAR dan set default 'pending'
        DB::statement("
            ALTER TABLE tasks 
            MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'
        ");

        // (Opsional, MySQL 8+) batasi nilai yang valid
        DB::statement("
            ALTER TABLE tasks
            ADD CONSTRAINT chk_tasks_status
            CHECK (status IN ('pending','in_progress','completed','new','confirmation','revision','done'))
        ");
    }

    public function down(): void
    {
        // Balik ke enum awal (ikuti kondisi awal kamu)
        DB::statement("
            ALTER TABLE tasks
            MODIFY status ENUM('in_progress','completed') 
            NOT NULL DEFAULT 'in_progress'
        ");

        // Hapus CHECK kalau ada
        DB::statement('
            ALTER TABLE tasks DROP CHECK IF EXISTS chk_tasks_status
        ');
    }
};
