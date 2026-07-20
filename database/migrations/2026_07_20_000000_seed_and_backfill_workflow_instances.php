<?php

use Database\Seeders\WorkflowSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(WorkflowSeeder::class)->run();
    }

    public function down(): void
    {
        // Workflow definitions and instances are application data and must be preserved.
    }
};
