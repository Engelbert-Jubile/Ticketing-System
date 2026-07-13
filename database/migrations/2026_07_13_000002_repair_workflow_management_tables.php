<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $workflowMigration = require database_path(
            'migrations/2026_07_13_000000_create_workflow_management_tables.php'
        );

        $workflowMigration->up();
    }

    public function down(): void
    {
        // Intentionally left empty: this migration repairs an existing installation.
    }
};
