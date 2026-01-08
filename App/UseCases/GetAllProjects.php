<?php

namespace App\Domains\Project\UseCases;

use App\Domains\Project\Models\Project;
use Illuminate\Support\Collection;

class GetAllProjects
{
    public function execute(string $status = 'all'): Collection
    {
        if ($status === 'all') {
            return Project::all();
        }

        return Project::where('status', $status)->get();
    }
}
