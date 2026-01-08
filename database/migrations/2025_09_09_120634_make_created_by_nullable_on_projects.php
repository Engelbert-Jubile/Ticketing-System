<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'created_by')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'created_by')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable(false)->change();
            });
        }
    }
};
