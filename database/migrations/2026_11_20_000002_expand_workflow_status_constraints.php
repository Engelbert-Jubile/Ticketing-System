<?php

use App\Support\WorkflowStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Preserve statuses accepted by older deployments while enabling all current statuses.
        $statuses = array_unique(array_merge(WorkflowStatus::all(), [
            'open', 'closed', 'complete', 'completed', 'pending', 'on_progress',
        ]));
        $connection = DB::connection();
        $grammar = $connection->getQueryGrammar();
        $allowed = implode(', ', array_map(fn (string $status) => $connection->getPdo()->quote($status), $statuses));

        foreach (['tickets', 'tasks'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status')) {
                continue;
            }

            $constraint = 'chk_'.$table.'_status';
            $existing = DB::selectOne(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
                [$connection->getTablePrefix().$table, $constraint, 'CHECK']
            );

            $changes = [];
            if ($existing) {
                $changes[] = ($connection->isMaria() ? 'DROP CONSTRAINT ' : 'DROP CHECK ').$grammar->wrap($constraint);
            }
            $changes[] = "MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'new'";
            $changes[] = 'ADD CONSTRAINT '.$grammar->wrap($constraint).' CHECK (status IN ('.$allowed.'))';

            // One ALTER replaces the old restriction and adds the new one together.
            DB::statement('ALTER TABLE '.$grammar->wrapTable($table).' '.implode(', ', $changes));
        }
    }

    public function down(): void
    {
        // A narrower constraint would reject already saved On Hold and Cancelled work.
    }
};
