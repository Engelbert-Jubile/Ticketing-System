<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The original relationship columns have already been removed on deployed databases.
        $confirmedCopies = [
            'TSK20260001' => 'Pengembangan Soft Skill Anak Magang #1',
            'TSK20260002' => 'Pengembangan Soft Skill Anak Magang #2',
            'TSK20260003' => 'Pengembangan Soft Skill Anak Magang #3',
            'TSK20260004' => 'Instalasi QAD',
        ];

        DB::transaction(function () use ($confirmedCopies): void {
            $tasks = DB::table('tasks')->whereNull('legacy_archived_at');
            $ids = (clone $tasks)->whereIn('task_no', array_keys($confirmedCopies))
                ->lockForUpdate()
                ->get(['id', 'task_no', 'title'])
                ->filter(fn ($task) => isset($confirmedCopies[$task->task_no])
                    && $confirmedCopies[$task->task_no] === $task->title)
                ->pluck('id');

            $tasks->whereIn('id', $ids)->update(['legacy_archived_at' => now()]);
        });
    }

    public function down(): void
    {
        // Use tasks:archive-legacy --restore for selective restoration.
    }
};
