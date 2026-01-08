<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects') && ! Schema::hasColumn('projects', 'requester_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('requester_id')->nullable()->after('created_by')
                    ->comment('FK to users.id - requester/pengguna yang meminta proyek');

                $table->foreign('requester_id')
                    ->references('id')->on('users')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'requester_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropForeign(['requester_id']);
                $table->dropColumn('requester_id');
            });
        }
    }
};
