<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class SlaClock
{
    public static function dates(Model $item, string $type): array
    {
        $dayEnd = fn ($value) => $value ? Carbon::parse($value)->endOfDay()->setMicrosecond(0) : null;
        $target = match ($type) {
            'project' => $dayEnd($item->end_date),
            'task' => $item->due_at ? Carbon::parse($item->due_at) : null,
            default => $item->due_at ? Carbon::parse($item->due_at) : $dayEnd($item->due_date),
        };
        $actual = null;
        if (WorkflowStatus::normalize((string) $item->status) === WorkflowStatus::DONE) {
            $actual = match ($type) {
                'ticket' => $item->finish_at ?: ($dayEnd($item->finish_date) ?: $item->updated_at),
                'task' => $item->completed_at ?: $item->updated_at,
                default => $item->updated_at,
            };
        }

        return [$target, $actual ? Carbon::parse($actual) : null];
    }

    /** Same deadline/completion rules as dates(), usable for filtering and aggregation. */
    public static function expression(Builder $query, string $type): string
    {
        $table = $query->getModel()->getTable();
        $end = fn (string $column) => $query->getConnection()->getDriverName() === 'sqlite'
            ? "datetime({$table}.{$column}, 'start of day', '+1 day', '-1 second')"
            : "TIMESTAMP(DATE({$table}.{$column}), '23:59:59')";
        $target = match ($type) {
            'project' => $end('end_date'),
            'task' => "{$table}.due_at",
            default => "COALESCE({$table}.due_at, ".$end('due_date').')',
        };
        $finish = match ($type) {
            'ticket' => "COALESCE({$table}.finish_at, ".$end('finish_date').", {$table}.updated_at)",
            'task' => "COALESCE({$table}.completed_at, {$table}.updated_at)",
            default => "{$table}.updated_at",
        };
        $done = "LOWER({$table}.status) IN ('done', 'completed', 'complete', 'closed')";

        return "CASE WHEN {$target} IS NULL THEN 'missing' "
            ."WHEN {$done} AND {$finish} IS NOT NULL THEN CASE WHEN {$finish} <= {$target} THEN 'met' ELSE 'breached' END "
            ."WHEN {$target} < ? THEN 'breached' ELSE 'pending' END";
    }
}
