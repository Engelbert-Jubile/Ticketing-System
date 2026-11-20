<?php

use App\Support\WorkflowStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets') || ! Schema::hasColumns('tickets', ['status', 'status_id'])) {
            return;
        }

        foreach (DB::table('tickets')->distinct()->pluck('status') as $status) {
            $code = WorkflowStatus::code(WorkflowStatus::normalize($status));
            DB::table('tickets')->where('status', $status)
                ->update(['status_id' => $code]);
        }
    }

    public function down(): void
    {
        // Restoring stale status codes would reintroduce inconsistent ticket reports.
    }
};
