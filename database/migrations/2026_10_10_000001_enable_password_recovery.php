<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
        Schema::table('tickets', fn (Blueprint $table) => $table->text('description')->nullable()->change());
    }

    public function down(): void
    {
        // Preserve recovery tokens and long descriptions on rollback to avoid deleting existing data.
    }
};
