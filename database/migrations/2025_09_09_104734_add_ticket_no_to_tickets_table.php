<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'ticket_no')) {
                $table->string('ticket_no', 40)->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'ticket_no')) {
                $table->dropUnique(['ticket_no']);
                $table->dropColumn('ticket_no');
            }
        });
    }
};
