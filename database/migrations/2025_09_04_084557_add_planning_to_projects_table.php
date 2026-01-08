<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Cari nama tabel yang benar: 'projects' atau 'project' */
    private function resolveTableName(): ?string
    {
        if (Schema::hasTable('projects')) {
            return 'projects';
        }
        if (Schema::hasTable('project')) {
            return 'project';
        }

        return null; // tidak ada dua-duanya
    }

    public function up(): void
    {
        $tableName = $this->resolveTableName();

        // Kalau tabel tidak ada sama sekali, hentikan dengan pesan jelas
        if (! $tableName) {
            // Kamu bisa lempar exception agar artisan berhenti dengan pesan yang gampang dipahami
            throw new \RuntimeException("Tabel 'projects' / 'project' tidak ditemukan. Cek nama tabel Project.");
        }

        // Tambahkan kolom planning kalau belum ada
        if (! Schema::hasColumn($tableName, 'planning')) {
            Schema::table($tableName, function (Blueprint $table) {
                // Jika DB-mu tidak mendukung JSON, ganti json(...) menjadi longText(...)
                $table->json('planning')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        $tableName = $this->resolveTableName();
        if ($tableName && Schema::hasColumn($tableName, 'planning')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('planning');
            });
        }
    }
};
