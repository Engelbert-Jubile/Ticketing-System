<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'superadmin_guard')) {
                $table->char('superadmin_guard', 1)->nullable()->after('id');
            }
        });

        // Kalau pakai spatie/laravel-permission, isi nilai berdasarkan role "superadmin"
        // Sesuaikan model_type jika namespace User Anda berbeda
        DB::statement("
            UPDATE users u
            JOIN model_has_roles mhr 
                ON mhr.model_id = u.id AND mhr.model_type = 'App\\\\Models\\\\User'
            JOIN roles r 
                ON r.id = mhr.role_id
            SET u.superadmin_guard = 'Y'
            WHERE r.name = 'superadmin'
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'superadmin_guard')) {
                $table->dropColumn('superadmin_guard');
            }
        });
    }
};
