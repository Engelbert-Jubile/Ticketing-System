<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Services\WorkflowRuntimeService;
use Illuminate\Support\Facades\Log;
use Throwable;

final class TicketStatusSync
{
    /**
     * Penanda agar sinkronisasi dari ticket tidak memicu loop ke task/project.
     */
    private static bool $propagatingFromTicket = false;

    public static function handleTaskSaved(Task $task): void
    {
        self::syncWorkflowSafely($task);
    }

    public static function handleProjectSaved(Project $project): void
    {
        // Status project tidak lagi mensinkronkan status ticket.

    }

    public static function handleTicketSaved(Ticket $ticket): void
    {
        self::syncWorkflowSafely($ticket);
    }

    private static function syncWorkflowSafely(Ticket|Task $subject): void
    {
        try {
            app(WorkflowRuntimeService::class)->sync($subject);
        } catch (Throwable $exception) {
            // Workflow is an auxiliary runtime feature. A stale workflow schema
            // must not roll back creation or updates of the core work item.
            Log::error('workflow_runtime_sync_failed', [
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
