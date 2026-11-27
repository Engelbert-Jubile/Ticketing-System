<?php

use App\Services\WorkflowRuntimeService;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(WorkflowSeeder::class)->seedProjects();
        app(WorkflowRuntimeService::class)->syncExisting('project');
    }

    public function down(): void
    {
        // Keep workflow definitions and their audit history on rollback.
    }
};
