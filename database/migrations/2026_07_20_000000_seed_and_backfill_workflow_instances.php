<?php

use Database\Seeders\WorkflowSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('workflows', 'slug')) {
            Schema::table('workflows', fn (Blueprint $table) => $table->string('slug', 160)->nullable()->after('uuid'));
        }

        app(WorkflowSeeder::class)->run();
    }

    public function down(): void
    {
        // Workflow definitions and instances are application data and must be preserved.
    }
};
