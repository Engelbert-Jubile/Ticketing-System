<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            // UUID id bila tabel kamu sudah pakai bigIncrements biarkan saja (skip)
            if (! Schema::hasColumn('tasks', 'task_no')) {
                $table->string('task_no', 20)->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('tasks', 'ticket_id')) {
                $table->unsignedBigInteger('ticket_id')->nullable()->index()->after('task_no');
                $table->foreign('ticket_id')->references('id')->on('tickets')
                    ->onDelete('set null')->onUpdate('cascade');
            }

            if (! Schema::hasColumn('tasks', 'title')) {
                $table->string('title', 255)->after('ticket_id');
            }

            if (! Schema::hasColumn('tasks', 'status')) {
                $table->string('status', 50)->default('new')->after('title');
            }

            if (! Schema::hasColumn('tasks', 'start_date')) {
                $table->date('start_date')->nullable()->after('status');
            }
            if (! Schema::hasColumn('tasks', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            if (! Schema::hasColumn('tasks', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('end_date');
                $table->foreign('created_by')->references('id')->on('users')
                    ->onDelete('set null');
            }

            if (! Schema::hasColumn('tasks', 'planning') && ! Schema::hasColumn('tasks', 'plannning')) {
                // gunakan 'planning' standard; mutator akan handle jika ada 'plannning'
                $table->longText('planning')->nullable()->after('created_by');
            }
        });
    }

    public function down(): void
    {
        // biasanya dibiarkan kosong; hindari drop kolom karena berisiko
    }
};
