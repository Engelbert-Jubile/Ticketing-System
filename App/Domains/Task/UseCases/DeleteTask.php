<?php

namespace App\Domains\Task\UseCases;

use App\Models\Task;

class DeleteTask
{
    public function execute(Task $task): bool
    {
        return $task->delete();
    }
}
