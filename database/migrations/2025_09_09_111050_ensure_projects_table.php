<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kalau sudah terlanjur bikin 'project' (singular), ubah ke 'projects'
        if (Schema::hasTable('project') && ! Schema::hasTable('projects')) {
            Schema::rename('project', 'projects');
        }

        // Kalau 'projects' belum ada, buat dari nol
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->bigIncrements('id');

                // relasi ke tickets (dipakai auto-create/sync dari Ticket type=project)
                $table->unsignedBigInteger('ticket_id')->unique()->nullable()
                    ->comment('FK to tickets.id (nullable untuk proyek mandiri)');

                // nomor proyek opsional namun unique bila diisi
                $table->string('project_no', 40)->nullable()->unique();

                // optional status_id (FK bisa ditambah di migration lain)
                $table->string('status_id', 64)->nullable();

                // kolom utama
                $table->string('title', 150);
                $table->text('description')->nullable();

                // status proyek (selaraskan dengan UI: new/on_progress/done)
                $table->string('status', 32)->default('in_progress');

                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();

                // pencatat pembuat
                $table->unsignedBigInteger('created_by')->nullable();

                $table->timestamps();

                // Index & FK
                $table->index('ticket_id', 'projects_ticket_id_idx');
                $table->foreign('ticket_id')
                    ->references('id')->on('tickets')
                    ->onUpdate('cascade')->onDelete('cascade');

                $table->foreign('created_by', 'fk_projects_created_by')
                    ->references('id')->on('users')
                    ->onDelete('cascade');
            });
        } else {
            // Jika sudah ada 'projects', pastikan kolom kunci tersedia
            Schema::table('projects', function (Blueprint $table) {
                if (! Schema::hasColumn('projects', 'ticket_id')) {
                    $table->unsignedBigInteger('ticket_id')->nullable()->after('id');
                    $table->index('ticket_id', 'projects_ticket_id_idx');
                    $table->foreign('ticket_id')
                        ->references('id')->on('tickets')
                        ->onUpdate('cascade')->onDelete('cascade');
                }
                if (! Schema::hasColumn('projects', 'project_no')) {
                    $table->string('project_no', 40)->nullable()->unique()->after('ticket_id');
                }
                if (! Schema::hasColumn('projects', 'status_id')) {
                    $table->string('status_id', 64)->nullable();
                }
                if (! Schema::hasColumn('projects', 'title')) {
                    $table->string('title', 150);
                }
                if (! Schema::hasColumn('projects', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('projects', 'status')) {
                    $table->string('status', 32)->default('in_progress');
                }
                if (! Schema::hasColumn('projects', 'start_date')) {
                    $table->date('start_date')->nullable();
                }
                if (! Schema::hasColumn('projects', 'end_date')) {
                    $table->date('end_date')->nullable();
                }
                if (! Schema::hasColumn('projects', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->foreign('created_by', 'fk_projects_created_by')
                        ->references('id')->on('users')
                        ->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
        // kalau kamu mau balikin ke 'project' singular saat rollback, tambahkan rename di sini.
    }
};
