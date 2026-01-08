<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            if (Schema::hasColumn($tableName, 'title')) {
                $table->string('title', 200)->change();
            }
            if (Schema::hasColumn($tableName, 'start_date')) {
                $table->date('start_date')->nullable()->change();
            }
            if (Schema::hasColumn($tableName, 'end_date')) {
                $table->date('end_date')->nullable()->change();
            }
            if (Schema::hasColumn($tableName, 'status')) {
                try {
                    $table->index('status');
                } catch (\Throwable $e) {
                }
            }
        });
    }

    public function down(): void
    {
        // Tidak perlu rollback (aman dibiarkan)
    }
};
