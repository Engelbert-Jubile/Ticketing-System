<?php

namespace App\Domains\Task\UseCases;

use App\Domains\Task\DTO\TaskData;
use App\Models\Task;

class UpdateTask
{
    public function execute(Task $task, TaskData $data): bool
    {
        return $task->update([
            'title' => $data->title,
            'description' => $data->description,
            'status' => $data->status,
        ]);
    }
}
