<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings_audit_logs') && ! Schema::hasTable('audit_logs')) {
            Schema::rename('settings_audit_logs', 'audit_logs');
            return;
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 60)->default('update');
                $table->string('group', 60)->nullable();
                $table->string('key', 120)->nullable();
                $table->longText('old_value')->nullable();
                $table->longText('new_value')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->index(['group', 'key']);
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audit_logs') && ! Schema::hasTable('settings_audit_logs')) {
            Schema::rename('audit_logs', 'settings_audit_logs');
        }
    }
};
