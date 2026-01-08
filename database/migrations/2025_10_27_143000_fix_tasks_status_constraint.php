<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropConstraintIfExists();
        $this->addConstraint([
            'pending',
            'in_progress',
            'completed',
            'new',
            'confirmation',
            'revision',
            'done',
        ]);
    }

    public function down(): void
    {
        $this->dropConstraintIfExists();
        $this->addConstraint([
            'pending',
            'in_progress',
            'completed',
        ]);
    }

    private function dropConstraintIfExists(): void
    {
        $exists = DB::selectOne(<<<'SQL'
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'tasks'
              AND CONSTRAINT_NAME = 'chk_tasks_status'
        SQL);

        if ($exists) {
            DB::statement('ALTER TABLE tasks DROP CONSTRAINT chk_tasks_status');
        }
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private function addConstraint(array $statuses): void
    {
        $allowed = collect($statuses)
            ->map(fn ($status) => "'".addslashes($status)."'")
            ->implode(',');

        DB::statement(
            <<<SQL
            ALTER TABLE tasks
            ADD CONSTRAINT chk_tasks_status
            CHECK (status IN ({$allowed}))
            SQL
        );
    }
};
