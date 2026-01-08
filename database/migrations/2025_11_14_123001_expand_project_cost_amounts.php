<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_costs')) {
            return;
        }

        Schema::table('project_costs', function (Blueprint $table) {
            $table->decimal('estimated_cost', 14, 2)->change();
            $table->decimal('actual_cost', 14, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_costs')) {
            return;
        }

        Schema::table('project_costs', function (Blueprint $table) {
            $table->decimal('estimated_cost', 10, 2)->change();
            $table->decimal('actual_cost', 10, 2)->nullable()->change();
        });
    }
};
