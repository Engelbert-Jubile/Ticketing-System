<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = Schema::hasTable('project') && ! Schema::hasTable('projects') ? 'project' : 'projects';
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'ticket_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            foreach (['projects_ticket_id_unique', 'project_ticket_id_unique'] as $idx) {
                if ($this->indexExists($tableName, $idx)) {
                    $table->dropUnique($idx);
                }
            }

            if (! $this->indexExists($tableName, 'projects_ticket_id_idx')) {
                try {
                    $table->index('ticket_id', 'projects_ticket_id_idx');
                } catch (\Throwable $e) {
                    // ignore if cannot be created (e.g., duplicate name)
                }
            }
        });
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('project') && ! Schema::hasTable('projects') ? 'project' : 'projects';
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'ticket_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if ($this->indexExists($tableName, 'projects_ticket_id_idx')) {
                $table->dropIndex('projects_ticket_id_idx');
            }

            if (! $this->indexExists($tableName, 'projects_ticket_id_unique')) {
                try {
                    $table->unique('ticket_id', 'projects_ticket_id_unique');
                } catch (\Throwable $e) {
                    // ignore if cannot be created
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT index_name FROM information_schema.STATISTICS WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $index]
        );

        return ! empty($result);
    }
};

