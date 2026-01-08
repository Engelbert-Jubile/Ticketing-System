<?php

use App\Support\WorkflowStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var string[] */
    private array $statusTables = [
        'projects',
        'project_actions',
        'project_subactions',
        'project_deliverables',
        'project_risk_analyses',
        'tickets',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('statuses')) {
            return;
        }

        $statusMap = collect(WorkflowStatus::labels())
            ->mapWithKeys(fn ($label, $status) => [
                $status => [
                    'code' => WorkflowStatus::code($status),
                    'label' => $label,
                ],
            ]);

        foreach ($statusMap as $legacy => $data) {
            $code = $data['code'];
            $label = $data['label'];

            if ($legacy !== $code) {
                $this->migrateStatusId($legacy, $code);
            }

            $this->upsertStatus($code, $label);
        }
    }

    public function down(): void
    {
        // Tidak perlu rollback; perubahan ini hanya menormalkan data.
    }

    private function migrateStatusId(string $from, string $to): void
    {
        if (! DB::table('statuses')->where('id', $from)->exists()) {
            return;
        }

        foreach ($this->statusTables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status_id')) {
                continue;
            }

            DB::table($table)
                ->where('status_id', $from)
                ->update(['status_id' => $to]);
        }

        DB::table('statuses')->where('id', $from)->delete();
    }

    private function upsertStatus(string $id, string $name): void
    {
        $now = now();
        $query = DB::table('statuses')->where('id', $id);

        if ($query->exists()) {
            $query->update([
                'name' => $name,
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('statuses')->insert([
            'id' => $id,
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
