<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Ubah enum -> string(32)
            $table->string('status', 32)->default('new')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Kembalikan ke enum lama kalau perlu rollback
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open')->change();
        });
    }
};
