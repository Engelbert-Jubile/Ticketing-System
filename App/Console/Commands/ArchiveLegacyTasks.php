<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveLegacyTasks extends Command
{
    protected $signature = 'tasks:archive-legacy
        {task_numbers* : Exact task numbers to archive}
        {--restore : Restore the selected tasks}';

    protected $description = 'Archive identified copies from the legacy linked-task system';

    public function handle(): int
    {
        if (! Schema::hasColumn('tasks', 'legacy_archived_at')) {
            $this->error('Run the pending migrations before archiving legacy tasks.');

            return self::FAILURE;
        }

        $numbers = array_unique($this->argument('task_numbers'));
        sort($numbers);
        $restore = $this->option('restore');
        $changed = DB::transaction(function () use ($numbers, $restore): ?int {
            $tasks = DB::table('tasks')->whereIn('task_no', $numbers);
            $found = (clone $tasks)->lockForUpdate()->pluck('task_no')->all();
            sort($found);
            if ($found !== $numbers) {
                return null;
            }

            if ($restore) {
                $tasks->whereNotNull('legacy_archived_at');
            } else {
                $tasks->whereNull('legacy_archived_at');
            }

            return $tasks->update(['legacy_archived_at' => $restore ? null : now()]);
        });

        if ($changed === null) {
            $this->error('One or more task numbers could not be matched uniquely. No tasks were updated.');

            return self::FAILURE;
        }

        $this->info($restore ? "Restored {$changed} task(s)." : "Archived {$changed} legacy task(s).");

        return self::SUCCESS;
    }
}
