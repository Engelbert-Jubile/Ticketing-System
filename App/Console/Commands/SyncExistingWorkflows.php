<?php

namespace App\Console\Commands;

use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Services\WorkflowRuntimeService;
use App\Support\WorkflowStatus;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncExistingWorkflows extends Command
{
    protected $signature = 'workflows:sync-existing';

    protected $description = 'Backfill and synchronize existing Tickets, Tasks and Projects with workflow instances';

    public function handle(WorkflowRuntimeService $runtime): int
    {
        if (! $this->workflowTablesAvailable()) {
            $this->error('Tabel Workflow Management belum tersedia. Jalankan migration terlebih dahulu.');

            return self::FAILURE;
        }

        if (! $this->defaultWorkflowsReady()) {
            $this->call('db:seed', [
                '--class' => WorkflowSeeder::class,
                '--force' => true,
            ]);
        }

        app(WorkflowSeeder::class)->seedProjects();
        $processed = $runtime->syncExisting();
        $actual = $synchronized = $rows = [];
        foreach (Workflow::SUBJECTS as $type => $model) {
            $exists = Schema::hasTable((new $model)->getTable());
            $actual[$type] = $exists ? $model::query()->count() : 0;
            $synchronized[$type] = $exists ? $this->instanceCount($model) : 0;
            $rows[] = [ucfirst($type), $processed[$type], $actual[$type], $synchronized[$type]];
        }

        $this->table(
            ['Tipe', 'Diproses', 'Data aktual', 'Workflow instances'],
            $rows
        );

        if ($actual !== $synchronized) {
            $this->error('Sinkronisasi belum lengkap. Periksa workflow aktif dan konfigurasi tahap.');

            return self::FAILURE;
        }

        $this->info('Sinkronisasi Workflow Ticket, Task, dan Project selesai.');

        return self::SUCCESS;
    }

    private function workflowTablesAvailable(): bool
    {
        return Schema::hasTable('workflows')
            && Schema::hasTable('workflow_stages')
            && Schema::hasTable('workflow_instances');
    }

    private function defaultWorkflowsReady(): bool
    {
        $requiredStages = count(WorkflowStatus::all());
        $workflows = Workflow::query()
            ->whereIn('code', ['TICKET_DEFAULT', 'TASK_DEFAULT'])
            ->withCount('stages')
            ->get()
            ->keyBy('code');

        foreach (['TICKET_DEFAULT', 'TASK_DEFAULT'] as $code) {
            $workflow = $workflows->get($code);
            if (! $workflow || ! $workflow->is_active || $workflow->stages_count < $requiredStages) {
                return false;
            }
        }

        return true;
    }

    private function instanceCount(string $subjectType): int
    {
        return WorkflowInstance::query()
            ->where('subject_type', $subjectType)
            ->whereHasMorph('subject', [$subjectType])
            ->count();
    }
}
