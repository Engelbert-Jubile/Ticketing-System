<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workflow_stages')) {
            return;
        }

        DB::table('workflow_stages')->distinct()->orderBy('workflow_id')->pluck('workflow_id')->each(function ($workflowId): void {
            DB::transaction(function () use ($workflowId): void {
                $stages = DB::table('workflow_stages')
                    ->where('workflow_id', $workflowId)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id', 'position']);
                if ($stages->isEmpty()) {
                    return;
                }

                $offset = ((int) $stages->max('position')) + $stages->count() + 100;
                DB::table('workflow_stages')
                    ->where('workflow_id', $workflowId)
                    ->update(['position' => DB::raw("position + {$offset}")]);
                foreach ($stages->values() as $index => $stage) {
                    DB::table('workflow_stages')->where('id', $stage->id)->update(['position' => $index + 1]);
                }

                $positions = DB::table('workflow_stages')
                    ->where('workflow_id', $workflowId)
                    ->orderBy('position')
                    ->pluck('position')
                    ->map(fn ($position) => (int) $position)
                    ->all();
                if ($positions !== range(1, $stages->count())) {
                    throw new RuntimeException("Workflow {$workflowId} gagal dinormalisasi.");
                }
            });
        });
    }

    public function down(): void
    {
        // Normalized ordering is the canonical representation and must not be reverted.
    }
};
