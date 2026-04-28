<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_actions', function (Blueprint $table): void {
            if (Schema::hasColumn('project_actions', 'start_date')) {
                $table->dateTime('start_date')->nullable()->change();
            }
            if (Schema::hasColumn('project_actions', 'end_date')) {
                $table->dateTime('end_date')->nullable()->change();
            }
        });

        Schema::table('project_subactions', function (Blueprint $table): void {
            if (Schema::hasColumn('project_subactions', 'start_date')) {
                $table->dateTime('start_date')->nullable()->change();
            }
            if (Schema::hasColumn('project_subactions', 'end_date')) {
                $table->dateTime('end_date')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_actions', function (Blueprint $table): void {
            if (Schema::hasColumn('project_actions', 'start_date')) {
                $table->date('start_date')->nullable()->change();
            }
            if (Schema::hasColumn('project_actions', 'end_date')) {
                $table->date('end_date')->nullable()->change();
            }
        });

        Schema::table('project_subactions', function (Blueprint $table): void {
            if (Schema::hasColumn('project_subactions', 'start_date')) {
                $table->date('start_date')->nullable()->change();
            }
            if (Schema::hasColumn('project_subactions', 'end_date')) {
                $table->date('end_date')->nullable()->change();
            }
        });
    }
};
