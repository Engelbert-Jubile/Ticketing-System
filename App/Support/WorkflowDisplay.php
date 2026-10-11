<?php

namespace App\Support;

use App\Models\Workflow;

final class WorkflowDisplay
{
    public static function text(?Workflow $workflow, ?string $text): ?string
    {
        // Only translate shipped definitions. Custom workflow content stays intact.
        return $text !== null && in_array($workflow?->code, ['TICKET_DEFAULT', 'TASK_DEFAULT'], true)
            ? __($text)
            : $text;
    }
}
