<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        Schema::table('app_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('app_settings', 'cast_type')) {
                $table->string('cast_type')->nullable()->after('value');
            }
        });

        if (Schema::hasColumn('app_settings', 'type') && Schema::hasColumn('app_settings', 'cast_type')) {
            DB::table('app_settings')
                ->whereNull('cast_type')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('app_settings')
                            ->where('id', $row->id)
                            ->update(['cast_type' => $row->type]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        Schema::table('app_settings', function (Blueprint $table) {
            if (Schema::hasColumn('app_settings', 'cast_type')) {
                $table->dropColumn('cast_type');
            }
        });
    }
};
