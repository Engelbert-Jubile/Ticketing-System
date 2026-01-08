<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan kolom status fleksibel (bukan ENUM)
        DB::statement("ALTER TABLE tasks MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");

        $projectTable = Schema::hasTable('projects') ? 'projects' : 'project';
        $projectIdType = $this->getColumnDataType($projectTable, 'id');

        Schema::table('tasks', function (Blueprint $table) use ($projectTable, $projectIdType) {
            // Relasi ke ticket (opsional)
            if (! Schema::hasColumn('tasks', 'ticket_id')) {
                $table->unsignedBigInteger('ticket_id')->nullable()->after('id');
                $table->foreign('ticket_id')->references('id')->on('tickets')->nullOnDelete();
                $table->index('ticket_id');
            }

            // Relasi ke project (opsional, UUID)
            if (! Schema::hasColumn('tasks', 'project_id')) {
                if (in_array($projectIdType, ['char', 'varchar', 'text', 'uuid', 'binary', 'varbinary'], true)) {
                    $table->uuid('project_id')->nullable()->after('ticket_id');
                } else {
                    $table->unsignedBigInteger('project_id')->nullable()->after('ticket_id');
                }

                if (Schema::hasTable($projectTable)) {
                    $table->foreign('project_id', 'tasks_project_id_foreign')
                        ->references('id')->on($projectTable)
                        ->nullOnDelete();
                }

                $table->index('project_id');
            }

            // Assignee (opsional)
            if (! Schema::hasColumn('tasks', 'assignee_id')) {
                $table->unsignedBigInteger('assignee_id')->nullable()->after('project_id');
                $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
                $table->index('assignee_id');
            }

            // Timestamps selesai (untuk grafik 7 hari)
            if (! Schema::hasColumn('tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('status');
                $table->index('completed_at');
            }

            // Index status agar query cepat
            $table->index('status');
        });
    }

    public function down(): void
    {
        $self = $this;

        Schema::table('tasks', function (Blueprint $table) use ($self) {
            if (Schema::hasColumn('tasks', 'ticket_id')) {
                foreach (['tasks_ticket_id_foreign', 'fk_tasks_ticket'] as $key) {
                    if ($self->foreignKeyExists('tasks', $key)) {
                        $table->dropForeign($key);
                    }
                }
                foreach (['tasks_ticket_id_index', 'tasks_ticket_id_idx', 'ticket_id'] as $index) {
                    if ($self->indexExists('tasks', $index)) {
                        $table->dropIndex($index);
                    }
                }
                $table->dropColumn('ticket_id');
            }

            if (Schema::hasColumn('tasks', 'project_id')) {
                foreach (['tasks_project_id_foreign', 'fk_tasks_project'] as $key) {
                    if ($self->foreignKeyExists('tasks', $key)) {
                        $table->dropForeign($key);
                    }
                }
                foreach (['tasks_project_id_index', 'tasks_project_id_idx', 'project_id'] as $index) {
                    if ($self->indexExists('tasks', $index)) {
                        $table->dropIndex($index);
                    }
                }
                $table->dropColumn('project_id');
            }

            if (Schema::hasColumn('tasks', 'assignee_id')) {
                foreach (['tasks_assignee_id_foreign', 'fk_tasks_assignee'] as $key) {
                    if ($self->foreignKeyExists('tasks', $key)) {
                        $table->dropForeign($key);
                    }
                }
                foreach (['tasks_assignee_id_index', 'tasks_assignee_id_idx', 'assignee_id'] as $index) {
                    if ($self->indexExists('tasks', $index)) {
                        $table->dropIndex($index);
                    }
                }
                $table->dropColumn('assignee_id');
            }

            if (Schema::hasColumn('tasks', 'completed_at')) {
                foreach (['tasks_completed_at_index', 'completed_at'] as $index) {
                    if ($self->indexExists('tasks', $index)) {
                        $table->dropIndex($index);
                    }
                }
                $table->dropColumn('completed_at');
            }
            // index status dibiarkan
        });
    }

    private function getColumnDataType(string $table, string $column): ?string
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1',
            [$database, $table, $column]
        );

        if (empty($result)) {
            return null;
        }

        return strtolower($result[0]->DATA_TYPE ?? '');
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1',
            [$database, $table, $constraint]
        );

        return ! empty($result);
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$database, $table, $index]
        );

        return ! empty($result);
    }
};
