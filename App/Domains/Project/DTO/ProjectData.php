<?php

namespace App\Domains\Project\DTO;

use Illuminate\Http\Request;

class ProjectData
{
    public string $title;

    public ?string $description;

    public ?string $status;

    public ?int $ticket_id;

    public ?string $status_id;

    public ?string $start_date;

    public ?string $end_date;

    public ?int $created_by;

    public function __construct(
        string $title,
        ?string $description = null,
        ?string $status = null,
        ?int $ticket_id = null,
        ?string $status_id = null,
        ?string $start_date = null,
        ?string $end_date = null,
        ?int $created_by = null,
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->ticket_id = $ticket_id;
        $this->status_id = $status_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->created_by = $created_by;
    }

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'ticket_id' => ['nullable', 'integer', 'exists:tickets,id'],
            'status_id' => ['nullable', 'string', 'max:64'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        return new self(
            $validated['title'],
            $validated['description'] ?? null,
            $validated['status'] ?? null,
            $validated['ticket_id'] ?? null,
            $validated['status_id'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            $validated['created_by'] ?? null,
        );
    }
}
