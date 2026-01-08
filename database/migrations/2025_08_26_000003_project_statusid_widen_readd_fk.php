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
            if ($this->foreignKeyExists($tableName, 'fk_project_status')) {
                $table->dropForeign('fk_project_status');
            }
        });

        try {
            DB::statement(sprintf('ALTER TABLE `%s` MODIFY `status_id` VARCHAR(255)', $tableName));
        } catch (\Throwable $e) {
        }

        if (! Schema::hasTable('statuses')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->foreign('status_id', 'fk_project_status')
                ->references('id')->on('statuses')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('project') && ! Schema::hasTable('projects') ? 'project' : 'projects';
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if ($this->foreignKeyExists($tableName, 'fk_project_status')) {
                $table->dropForeign('fk_project_status');
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
};
