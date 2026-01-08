<?php

namespace App\Domains\Project\Repositories;

use App\Domains\Project\DTO\ProjectData;
use App\Domains\Project\Interfaces\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Support\Collection;

final class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function all(): Collection
    {
        return Project::all();
    }

    public function findById(int $id): ?Project
    {
        return Project::find($id);
    }

    public function create(ProjectData $data): Project
    {
        return Project::create([
            'title' => $data->title,
            'description' => $data->description,
            'status' => $data->status,
            'status_id' => $data->status_id,
            'ticket_id' => $data->ticket_id,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'created_by' => $data->created_by,
        ]);
    }

    public function update(Project $project, ProjectData $data): Project
    {
        $project->update([
            'title' => $data->title,
            'description' => $data->description,
            'status' => $data->status,
            'status_id' => $data->status_id,
            'ticket_id' => $data->ticket_id,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'created_by' => $data->created_by,
        ]);

        return $project->refresh();
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
