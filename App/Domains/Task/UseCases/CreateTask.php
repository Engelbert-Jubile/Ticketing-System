<?php

namespace App\Domains\Task\UseCases;

use App\Domains\Task\DTO\TaskData;
use App\Models\Task;

class CreateTask
{
    public function execute(TaskData $data): Task
    {
        return Task::create([
            'title' => $data->title,
            'description' => $data->description,
            // kalau $data->status kosong, default ke 'new'
            'status' => $data->status ?: 'new',
        ]);
    }
}
