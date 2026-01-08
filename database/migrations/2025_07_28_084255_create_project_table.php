<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jika sebelumnya sempat membuat tabel 'project' (singular), rename ke 'projects'
        if (Schema::hasTable('project') && ! Schema::hasTable('projects')) {
            Schema::rename('project', 'projects');
        }

        // Buat tabel 'projects' jika belum ada
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                // gunakan big integer auto-increment agar serasi dengan model Project yang umum
                $table->bigIncrements('id');

                // relasi ke tickets (dipakai auto-create/sync dari Ticket type=project)
                $table->unsignedBigInteger('ticket_id')->unique()->nullable()
                    ->comment('FK to tickets.id (nullable utk proyek mandiri)');

                // nomor proyek opsional namun unique bila diisi
                $table->string('project_no', 40)->nullable()->unique();

                // status_id tetap ada (FK bisa ditambahkan di migration lain)
                $table->string('status_id', 64)->nullable();

                // kolom utama
                $table->string('title', 150);
                $table->text('description')->nullable();

                // status proyek (selaraskan dengan yang dipakai di UI: new/on_progress/done, dll)
                $table->string('status', 32)->default('in_progress');

                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();

                // pencatat pembuat
                $table->unsignedBigInteger('created_by')->nullable();

                $table->timestamps();

                // index & FK
                $table->index('ticket_id', 'projects_ticket_id_idx');
                $table->foreign('ticket_id')
                    ->references('id')->on('tickets')
                    ->onUpdate('cascade')->onDelete('cascade');

                $table->foreign('created_by', 'fk_projects_created_by')
                    ->references('id')->on('users')
                    ->onDelete('cascade');
            });
        } else {
            // Jika 'projects' sudah ada, pastikan kolom penting tersedia
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
        // Langsung drop tabel 'projects' (FK internal akan ikut terhapus)
        Schema::dropIfExists('projects');
    }
};
