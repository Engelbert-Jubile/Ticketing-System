<?php

namespace App\Domains\Project\Interfaces;

use App\Domains\Project\DTO\ProjectData;
use App\Models\Project;
use Illuminate\Support\Collection;

interface ProjectRepositoryInterface
{
    /** @return Collection<int, Project> */
    public function all(): Collection;

    public function create(ProjectData $data): Project;

    public function update(Project $project, ProjectData $data): Project;

    public function delete(Project $project): void;
}
