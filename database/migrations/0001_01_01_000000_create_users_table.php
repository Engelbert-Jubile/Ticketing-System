<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT
            $table->string('username', 10)->unique(); // Tambahkan unique untuk username
            $table->string('first_name', 255);
            $table->string('last_name', 255)->nullable();
            $table->string('position', 255)->nullable();
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('verification_token', 100)->nullable();
            $table->string('phone_wa', 255)->nullable();
            $table->string('photo', 255)->nullable();
            $table->string('ip_session', 255)->nullable();
            $table->timestamp('last_access')->nullable();
            $table->timestamps(); // created_at & updated_at
            $table->softDeletes(); // deleted_at
        });
    }

    /**
     * Rollback migrasi.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();
    }
};
