<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;

final class TicketStatusSync
{
    /**
     * Penanda agar sinkronisasi dari ticket tidak memicu loop ke task/project.
     */
    private static bool $propagatingFromTicket = false;

    public static function handleTaskSaved(Task $task): void
    {
        // Status task tidak lagi mensinkronkan status ticket.
        return;
    }

    public static function handleProjectSaved(Project $project): void
    {
        // Status project tidak lagi mensinkronkan status ticket.
        return;
    }

    public static function handleTicketSaved(Ticket $ticket): void
    {
        // Status ticket tidak lagi dipaksakan ke task/project.
        return;
    }
}
