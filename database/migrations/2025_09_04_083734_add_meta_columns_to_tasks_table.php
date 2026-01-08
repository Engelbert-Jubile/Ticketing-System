<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // meta task baru
            $table->string('priority')->nullable()->after('status');      // low|normal|high|critical (bebas)
            $table->string('assigned_to')->nullable()->after('priority'); // nama/identifier assignee
            $table->dateTime('due_at')->nullable()->after('assigned_to'); // tenggat (pakai time)
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['priority', 'assigned_to', 'due_at']);
        });
    }
};
