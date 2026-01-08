<?php

namespace App\Domains\Project\UseCases;

use App\Domains\Project\DTO\ProjectData;
use App\Domains\Project\Models\Project;

class UpdateProject
{
    public function execute(Project $project, ProjectData $data): bool
    {
        return $project->update([
            'name' => $data->name,
            'description' => $data->description,
            'status' => $data->status ?? $project->status, // fallback to current status
        ]);
    }
}
