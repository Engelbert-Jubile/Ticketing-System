<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        foreach (['projects_ticket_id_unique', 'project_ticket_id_unique'] as $idx) {
            $this->dropIndexIfExists('projects', $idx);
        }

        Schema::table('projects', function (Blueprint $table) {
            if (! $this->indexExists('projects', 'projects_ticket_id_idx')) {
                $table->index('ticket_id', 'projects_ticket_id_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        $this->dropIndexIfExists('projects', 'projects_ticket_id_idx');

        Schema::table('projects', function (Blueprint $table) {
            if (! $this->indexExists('projects', 'projects_ticket_id_unique')) {
                $table->unique('ticket_id', 'projects_ticket_id_unique');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $results = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]);

        return ! empty($results);
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            DB::statement('ALTER TABLE `'.$table.'` DROP INDEX `'.$index.'`');
        } catch (\Throwable $e) {
            // ignore if missing
        }
    }
};
