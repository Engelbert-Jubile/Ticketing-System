<?php

namespace App\Domains\Project\UseCases;

use App\Domains\Project\DTO\ProjectData;
use App\Domains\Project\Interfaces\ProjectRepositoryInterface;
use App\Domains\Project\Models\Project;

class UpdateProject
{
    public function __construct(protected ProjectRepositoryInterface $repository) {}

    public function execute(Project $project, ProjectData $data): Project
    {
        return $this->repository->update($project, $data);
    }
}
