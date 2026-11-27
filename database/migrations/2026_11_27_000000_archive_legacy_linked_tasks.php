<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'legacy_archived_at')) {
            Schema::table('tasks', fn (Blueprint $table) => $table->timestamp('legacy_archived_at')->nullable()->index());
        }

        // Capture linked rows before the legacy columns are dropped.
        $links = array_intersect(['ticket_id', 'project_id'], Schema::getColumnListing('tasks'));
        if ($links !== []) {
            DB::table('tasks')->whereNull('legacy_archived_at')
                ->where(function ($query) use ($links): void {
                    foreach ($links as $column) {
                        $query->orWhereNotNull($column);
                    }
                })
                ->update(['legacy_archived_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(['legacy_archived_at']);
            $table->dropColumn('legacy_archived_at');
        });
    }
};
