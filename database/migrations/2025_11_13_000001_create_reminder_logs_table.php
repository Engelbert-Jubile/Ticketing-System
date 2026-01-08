<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 40);
            $table->unsignedBigInteger('item_id');
            $table->string('event')->default('reminder');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
            $table->index(['user_id', 'item_type', 'item_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};
