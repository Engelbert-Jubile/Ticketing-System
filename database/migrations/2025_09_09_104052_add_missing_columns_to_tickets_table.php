<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'letter_no')) {
                $table->string('letter_no')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('tickets', 'priority')) {
                $table->enum('priority', ['high', 'medium', 'low'])->default('medium')->after('letter_no');
            }
            if (! Schema::hasColumn('tickets', 'type')) {
                $table->enum('type', ['task', 'project'])->default('task')->after('priority');
            }
            if (! Schema::hasColumn('tickets', 'status_id')) {
                $table->string('status_id', 64)->nullable()->after('status');
            }
            if (! Schema::hasColumn('tickets', 'requester_id')) {
                $table->unsignedBigInteger('requester_id')->nullable()->after('status_id');
            }
            if (! Schema::hasColumn('tickets', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable()->after('requester_id');
            }
            if (! Schema::hasColumn('tickets', 'assigned_id')) {
                $table->unsignedBigInteger('assigned_id')->nullable()->after('agent_id');
            }
            if (! Schema::hasColumn('tickets', 'due_date')) {
                $table->date('due_date')->nullable()->after('assigned_id');
            }
            if (! Schema::hasColumn('tickets', 'finish_date')) {
                $table->date('finish_date')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('tickets', 'sla')) {
                $table->enum('sla', ['ontime', 'late'])->nullable()->after('finish_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'letter_no', 'priority', 'type', 'status_id',
                'requester_id', 'agent_id', 'assigned_id',
                'due_date', 'finish_date', 'sla',
            ]);
        });
    }
};
