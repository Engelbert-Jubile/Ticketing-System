<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workflows')) {
            Schema::create('workflows', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name', 120);
                $table->string('code', 50)->unique();
                $table->string('entity_type', 20)->index();
                $table->text('description')->nullable();
                $table->json('trigger_conditions')->nullable();
                $table->boolean('is_active')->default(false)->index();
                $table->unsignedInteger('version')->default(1);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('workflow_stages')) {
            Schema::create('workflow_stages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('position');
                $table->string('name', 100);
                $table->string('status_key', 40);
                $table->string('responsible_role', 80)->nullable();
                $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action_label', 100)->nullable();
                $table->text('instructions')->nullable();
                $table->timestamps();
                $table->unique(['workflow_id', 'position']);
            });
        }

        if (! Schema::hasTable('workflow_instances')) {
            Schema::create('workflow_instances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_id')->constrained()->restrictOnDelete();
                $table->morphs('subject');
                $table->foreignId('current_stage_id')->nullable()->constrained('workflow_stages')->nullOnDelete();
                $table->string('status', 20)->default('running')->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique(['workflow_id', 'subject_type', 'subject_id'], 'workflow_subject_unique');
            });
        }

        if (! Schema::hasTable('workflow_histories')) {
            Schema::create('workflow_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event', 40);
                $table->json('changes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_histories');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_stages');
        Schema::dropIfExists('workflows');
    }
};
