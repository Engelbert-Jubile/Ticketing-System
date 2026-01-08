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
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'ticket_id')) {
                foreach (['fk_project_ticket', 'projects_ticket_id_foreign', 'project_ticket_id_foreign'] as $key) {
                    if ($this->foreignKeyExists($tableName, $key)) {
                        $table->dropForeign($key);
                    }
                }
                foreach (['projects_ticket_id_unique', 'project_ticket_id_unique'] as $idx) {
                    if ($this->indexExists($tableName, $idx)) {
                        $table->dropUnique($idx);
                    }
                }
                foreach (['projects_ticket_id_idx', 'project_ticket_id_idx', 'ticket_id'] as $idx) {
                    if ($this->indexExists($tableName, $idx)) {
                        $table->dropIndex($idx);
                    }
                }
                $table->dropColumn('ticket_id');
            }
        });

        Schema::table($tableName, function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_id')->nullable()->after('project_no');
            try {
                $table->unique('ticket_id', 'projects_ticket_id_unique');
            } catch (\Throwable $e) {
            }
            $table->foreign('ticket_id', 'fk_project_ticket')
                ->references('id')->on('tickets')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('project') && ! Schema::hasTable('projects') ? 'project' : 'projects';
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            foreach (['fk_project_ticket', 'projects_ticket_id_foreign', 'project_ticket_id_foreign'] as $key) {
                if ($this->foreignKeyExists($tableName, $key)) {
                    $table->dropForeign($key);
                }
            }
            foreach (['projects_ticket_id_unique', 'project_ticket_id_unique'] as $idx) {
                if ($this->indexExists($tableName, $idx)) {
                    $table->dropUnique($idx);
                }
            }
            foreach (['projects_ticket_id_idx', 'project_ticket_id_idx', 'ticket_id'] as $idx) {
                if ($this->indexExists($tableName, $idx)) {
                    $table->dropIndex($idx);
                }
            }
            if (Schema::hasColumn($tableName, 'ticket_id')) {
                $table->dropColumn('ticket_id');
            }
        });
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT constraint_name FROM information_schema.TABLE_CONSTRAINTS WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = ? LIMIT 1',
            [$database, $table, $constraint, 'FOREIGN KEY']
        );

        return ! empty($result);
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
