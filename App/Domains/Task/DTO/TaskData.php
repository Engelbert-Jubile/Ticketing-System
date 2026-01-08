<?php

namespace App\Domains\Task\DTO;

use App\Domains\Task\Enums\TaskStatus;
use App\Support\WorkflowStatus;
use Illuminate\Http\Request;

class TaskData
{
    public string $title;

    public ?string $description;

    public string $status;

    public ?string $due_date;

    public function __construct(
        string $title,
        ?string $description,
        string $status,
        ?string $due_date = null
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->due_date = $due_date;
    }

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:'.implode(',', TaskStatus::values())],
            'due_date' => ['nullable', 'date_format:Y-m-d'], // format yyyy-mm-dd (standar DB)
        ]);

        $rawStatus = $validated['status'] ?? TaskStatus::New->value;
        $normalized = WorkflowStatus::normalize($rawStatus);

        return new self(
            $validated['title'],
            $validated['description'] ?? null,
            $normalized,
            $validated['due_date'] ?? null
        );
    }
}
