<?php

namespace App\Domains\Task\UseCases;

use App\Models\Task;
use App\Models\User;
use App\Support\UnitVisibility;
use Illuminate\Support\Collection;

class GetAllTasks
{
    public function execute(?string $status = null, ?User $viewer = null): Collection
    {
        $query = Task::with('attachments')->when($status, function ($q) use ($status) {
            $s = strtolower($status);
            $aliases = [
                'done' => ['done', 'completed'],
                'in_progress' => ['in_progress', 'pending'],
                'confirmation' => ['confirmation'],
                'revision' => ['revision'],
                'new' => ['new'],
            ];
            $list = $aliases[$s] ?? [$s];
            $q->whereIn('status', $list);
        });

        $query = UnitVisibility::scopeTasks($query, $viewer);

        return $query->get();
    }
}
