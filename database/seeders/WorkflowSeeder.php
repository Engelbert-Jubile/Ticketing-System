<?php

namespace Database\Seeders;

use App\Models\Workflow;
use App\Services\WorkflowRuntimeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->defaults() as $definition) {
                $stages = $definition['stages'];
                unset($definition['stages']);
                $workflow = Workflow::withTrashed()->firstOrNew(['code' => $definition['code']]);
                $workflow->fill($definition);
                $workflow->deleted_at = null;
                $workflow->save();

                $workflow->stages()->update(['position' => DB::raw('position + 100')]);
                $stageIds = [];
                foreach ($stages as $stage) {
                    $model = $workflow->stages()->updateOrCreate(
                        ['status_key' => $stage['status_key']],
                        $stage
                    );
                    $stageIds[] = $model->id;
                }
                $workflow->stages()->whereNotIn('id', $stageIds)->delete();
            }
        });

        app(WorkflowRuntimeService::class)->syncExisting();
    }

    private function defaults(): array
    {
        return [
            [
                'name' => 'Ticket Default Workflow',
                'code' => 'TICKET_DEFAULT',
                'entity_type' => 'ticket',
                'description' => 'Alur standar penanganan ticket dari penerimaan sampai selesai.',
                'trigger_conditions' => [],
                'is_active' => true,
                'version' => 1,
                'stages' => [
                    ['position' => 1, 'name' => 'Diterima', 'status_key' => 'new', 'responsible_role' => 'admin', 'action_label' => 'Mulai proses'],
                    ['position' => 2, 'name' => 'Diproses', 'status_key' => 'in_progress', 'responsible_role' => 'admin', 'action_label' => 'Ajukan konfirmasi'],
                    ['position' => 3, 'name' => 'Konfirmasi', 'status_key' => 'confirmation', 'responsible_role' => 'user', 'action_label' => 'Selesaikan'],
                    ['position' => 4, 'name' => 'Revisi', 'status_key' => 'revision', 'responsible_role' => 'admin', 'action_label' => 'Proses revisi'],
                    ['position' => 5, 'name' => 'Selesai', 'status_key' => 'done', 'responsible_role' => null, 'action_label' => null],
                    ['position' => 6, 'name' => 'Ditahan', 'status_key' => 'on_hold', 'responsible_role' => 'admin', 'action_label' => 'Lanjutkan'],
                    ['position' => 7, 'name' => 'Dibatalkan', 'status_key' => 'cancelled', 'responsible_role' => null, 'action_label' => null],
                ],
            ],
            [
                'name' => 'Task Default Workflow',
                'code' => 'TASK_DEFAULT',
                'entity_type' => 'task',
                'description' => 'Alur standar task dari pembuatan sampai selesai.',
                'trigger_conditions' => [],
                'is_active' => true,
                'version' => 1,
                'stages' => [
                    ['position' => 1, 'name' => 'Baru', 'status_key' => 'new', 'responsible_role' => 'admin', 'action_label' => 'Mulai task'],
                    ['position' => 2, 'name' => 'Dikerjakan', 'status_key' => 'in_progress', 'responsible_role' => 'admin', 'action_label' => 'Selesaikan'],
                    ['position' => 3, 'name' => 'Konfirmasi', 'status_key' => 'confirmation', 'responsible_role' => 'user', 'action_label' => 'Konfirmasi hasil'],
                    ['position' => 4, 'name' => 'Revisi', 'status_key' => 'revision', 'responsible_role' => 'admin', 'action_label' => 'Proses revisi'],
                    ['position' => 5, 'name' => 'Selesai', 'status_key' => 'done', 'responsible_role' => null, 'action_label' => null],
                    ['position' => 6, 'name' => 'Ditahan', 'status_key' => 'on_hold', 'responsible_role' => 'admin', 'action_label' => 'Lanjutkan'],
                    ['position' => 7, 'name' => 'Dibatalkan', 'status_key' => 'cancelled', 'responsible_role' => null, 'action_label' => null],
                ],
            ],
        ];
    }
}
