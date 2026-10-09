<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('internal')->default(false);
            $table->json('attachment_ids')->nullable();
            $table->timestamps();
            $table->index(['ticket_id', 'created_at']);
        });
        Schema::create('ticket_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->boolean('done')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('ticket_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('reopen_requested')->default(false);
            $table->timestamps();
        });
        Schema::create('knowledge_entries', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16)->default('article');
            $table->string('title');
            $table->string('category', 100)->nullable();
            $table->text('body');
            $table->json('checklist')->nullable();
            $table->foreignId('default_assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('unit')->nullable();
            $table->boolean('published')->default(false);
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['unit', 'published', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_entries');
        Schema::dropIfExists('ticket_feedback');
        Schema::dropIfExists('ticket_checklist_items');
        Schema::dropIfExists('ticket_messages');
    }
};
