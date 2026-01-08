<?php

namespace App\Domains\Project\UseCases;

use App\Domains\Project\Models\Project;

class DeleteProject
{
    public function execute(Project $project): bool
    {
        return $project->delete();
    }
}
