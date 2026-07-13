<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Services\WorkflowRuntimeService;

final class TicketStatusSync
{
    /**
     * Penanda agar sinkronisasi dari ticket tidak memicu loop ke task/project.
     */
    private static bool $propagatingFromTicket = false;

    public static function handleTaskSaved(Task $task): void
    {
        app(WorkflowRuntimeService::class)->sync($task);
    }

    public static function handleProjectSaved(Project $project): void
    {
        // Status project tidak lagi mensinkronkan status ticket.

    }

    public static function handleTicketSaved(Ticket $ticket): void
    {
        app(WorkflowRuntimeService::class)->sync($ticket);
    }
}
