<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_room_temperature_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspector', 511);
            $table->string('unit')->nullable();
            $table->string('room', 120);
            $table->dateTime('checked_at')->index();
            $table->decimal('temperature_c', 4, 1);
            $table->boolean('needs_follow_up');
            $table->text('notes')->nullable();
            $table->string('evidence_path');
            $table->timestamps();

            $table->index(['unit', 'checked_at']);
            $table->index(['recorded_by', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_room_temperature_checks');
    }
};
