<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyStatusEnumOnTicketsTable extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Ubah enum, tambahkan 'on_progress'
            $table->enum('status', ['new', 'on_progress', 'done'])
                ->default('new')
                ->comment('Ticket workflow status')
                ->change();
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Kembalikan seperti semula (jika enum semula cuma new & done)
            $table->enum('status', ['new', 'done'])
                ->default('new')
                ->comment('Ticket workflow status')
                ->change();
        });
    }
}
