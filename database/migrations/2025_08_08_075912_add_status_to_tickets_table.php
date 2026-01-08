<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Hanya tambahkan jika belum ada
            if (! Schema::hasColumn('tickets', 'status')) {
                $table->enum('status', ['new', 'on_progress', 'done'])
                    ->default('new')
                    ->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
