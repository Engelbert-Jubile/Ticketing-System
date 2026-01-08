<?php

namespace App\Domains\Project\UseCases;

use App\Domains\Project\Models\Project;

class GetAllProjects
{
    public function execute(?string $status = null)
    {
        $query = Project::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }
}
