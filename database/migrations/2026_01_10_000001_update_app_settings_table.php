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
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('group', 60)->index();
                $table->string('key', 120);
                $table->longText('value')->nullable();
                $table->string('type')->nullable();
                $table->boolean('is_secret')->default(false);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['group', 'key']);
            });

            return;
        }

        Schema::table('app_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('app_settings', 'type')) {
                $table->string('type')->nullable()->after('value');
            }
            if (! Schema::hasColumn('app_settings', 'is_secret')) {
                $table->boolean('is_secret')->default(false)->after('type');
            }
            if (! Schema::hasColumn('app_settings', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('is_secret')->constrained('users')->nullOnDelete();
            }
        });

        try {
            Schema::table('app_settings', function (Blueprint $table) {
                $table->unique(['group', 'key']);
            });
        } catch (\Throwable) {
            // ignore if unique index already exists
        }

        try {
            Schema::table('app_settings', function (Blueprint $table) {
                $table->index('group');
            });
        } catch (\Throwable) {
            // ignore if index already exists
        }

        if (Schema::hasColumn('app_settings', 'is_encrypted') && Schema::hasColumn('app_settings', 'is_secret')) {
            try {
                DB::table('app_settings')
                    ->where('is_encrypted', 1)
                    ->update(['is_secret' => 1]);
            } catch (\Throwable) {
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        Schema::table('app_settings', function (Blueprint $table) {
            if (Schema::hasColumn('app_settings', 'updated_by')) {
                $table->dropConstrainedForeignId('updated_by');
            }
            if (Schema::hasColumn('app_settings', 'is_secret')) {
                $table->dropColumn('is_secret');
            }
            if (Schema::hasColumn('app_settings', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
