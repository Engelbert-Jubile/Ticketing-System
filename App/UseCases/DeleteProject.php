<?php

namespace App\Domains\Project\UseCases;

use App\Domains\Project\Interfaces\ProjectRepositoryInterface;
use App\Domains\Project\Models\Project;

final class DeleteProject
{
    private readonly ProjectRepositoryInterface $projectRepository;

    public function __construct(ProjectRepositoryInterface $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    public function execute(Project $project): void
    {
        // Pastikan metode delete() ada di ProjectRepositoryInterface
        $this->projectRepository->delete($project);
    }
}
