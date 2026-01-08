<?php

namespace App\Domains\Project\UseCases;

use App\Domains\Project\DTO\ProjectData;
use App\Domains\Project\Models\Project;

class CreateProject
{
    public function execute(ProjectData $data): Project
    {
        return Project::create([
            'name' => $data->name,
            'description' => $data->description,
            'status' => 'in_progress', // default status
        ]);
    }
}
