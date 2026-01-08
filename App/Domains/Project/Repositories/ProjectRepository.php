<?php

namespace App\Domains\Project\Repositories;

use App\Domains\Project\DTO\ProjectData;
use App\Domains\Project\Interfaces\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class ProjectRepository implements ProjectRepositoryInterface
{
    /** @return Collection<int, Project> */
    public function all(): Collection
    {
        return Project::query()->latest('created_at')->get();
    }

    public function create(ProjectData $data): Project
    {
        $payload = [
            'title' => $data->title,
            'description' => $data->description,
            'status' => $data->status ?? 'in_progress',
            'ticket_id' => $data->ticket_id,
            'status_id' => $data->status_id,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'created_by' => $data->created_by ?? (Auth::id() ?: null),
        ];

        // buang null supaya tidak menimpa default DB
        $payload = array_filter($payload, fn ($v) => ! is_null($v));

        return Project::create($payload);
    }

    public function update(Project $project, ProjectData $data): Project
    {
        $payload = [
            'title' => $data->title ?? $project->title,
            'description' => $data->description ?? $project->description,
            'status' => $data->status ?? $project->status,
            'status_id' => $data->status_id ?? $project->status_id,
            'start_date' => $data->start_date ?? $project->start_date,
            'end_date' => $data->end_date ?? $project->end_date,
            // biasanya tidak diubah; tetap disinkron bila DTO mengirim
            'ticket_id' => $data->ticket_id ?? $project->ticket_id,
        ];

        $project->update($payload);

        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
