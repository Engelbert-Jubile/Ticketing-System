<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_deliverables')) {
            return;
        }

        $projectTable = Schema::hasTable('projects') ? 'projects' : 'project';
        $projectIdType = $this->getColumnDataType($projectTable, 'id');

        Schema::create('project_deliverables', function (Blueprint $table) use ($projectTable, $projectIdType) {
            $table->uuid('id')->primary();
            if (in_array($projectIdType, ['char', 'varchar', 'text', 'uuid', 'binary', 'varbinary'], true)) {
                $table->uuid('project_id');
            } else {
                $table->unsignedBigInteger('project_id');
            }
            $table->string('name', 60);
            $table->text('description')->nullable();
            $table->string('status_id', 4); // FK ke statuses (varchar 4)
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->enum('verified_by', ['identified', 'monitored', 'mitigated', 'occurred'])
                ->default('identified');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Indexes
            $table->index('project_id');
            $table->index('status_id');
            $table->index('verified_at');

            // Foreign Keys
            if (Schema::hasTable($projectTable)) {
                $table->foreign('project_id', 'project_deliverables_project_id_foreign')
                    ->references('id')->on($projectTable)
                    ->onDelete('cascade');
            }

            if (Schema::hasTable('statuses')) {
                $table->foreign('status_id', 'project_deliverables_status_id_foreign')
                    ->references('id')->on('statuses')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_deliverables');
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
};
