<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_stages', function (Blueprint $table) {
            if (! Schema::hasColumn('workflow_stages', 'is_required')) {
                $table->boolean('is_required')->default(true)->after('responsible_user_id');
            }
        });

        Schema::table('workflow_instances', function (Blueprint $table) {
            if (! Schema::hasColumn('workflow_instances', 'workflow_version')) {
                $table->unsignedInteger('workflow_version')->default(1)->after('workflow_id');
            }
            if (! Schema::hasColumn('workflow_instances', 'stage_started_at')) {
                $table->timestamp('stage_started_at')->nullable()->after('started_at');
            }
        });

        if (! Schema::hasTable('workflow_instance_histories')) {
            Schema::create('workflow_instance_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_id')->constrained()->restrictOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event', 40);
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40)->nullable();
                $table->string('from_stage_name', 100)->nullable();
                $table->string('to_stage_name', 100)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['workflow_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instance_histories');

        Schema::table('workflow_instances', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['workflow_version', 'stage_started_at'],
                fn (string $column) => Schema::hasColumn('workflow_instances', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('workflow_stages', function (Blueprint $table) {
            if (Schema::hasColumn('workflow_stages', 'is_required')) {
                $table->dropColumn('is_required');
            }
        });
    }
};
