<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // ===== tickets =====
        if (! Schema::hasColumn('tickets', 'uuid')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
                $table->unique('uuid');
            });

            DB::table('tickets')->orderBy('id')->chunk(500, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('tickets')->where('id', $r->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });

            // Jika ingin dijadikan NOT NULL di kemudian hari (butuh doctrine/dbal):
            // Schema::table('tickets', fn (Blueprint $t) => $t->uuid('uuid')->nullable(false)->change());
        }

        // ===== tasks =====
        if (! Schema::hasColumn('tasks', 'uuid')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
                $table->unique('uuid');
            });

            DB::table('tasks')->orderBy('id')->chunk(500, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('tasks')->where('id', $r->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });
        }

        // ===== projects =====
        // Di DB kamu, projects.id sudah UUID (char[36]). Kita tetap tambahkan kolom uuid
        // agar pola konsisten. Isi 'uuid' = nilai 'id' untuk baris lama.
        if (! Schema::hasColumn('projects', 'uuid')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
                $table->unique('uuid');
            });

            DB::table('projects')->orderBy('id')->chunk(500, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('projects')->where('id', $r->id)
                        ->update(['uuid' => (string) $r->id]);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tickets', 'uuid')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }

        if (Schema::hasColumn('tasks', 'uuid')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }

        if (Schema::hasColumn('projects', 'uuid')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }
    }
};
