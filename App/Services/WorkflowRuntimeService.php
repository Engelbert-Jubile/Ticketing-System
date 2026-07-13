<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Ticket;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class WorkflowRuntimeService
{
    public function sync(Model $subject): void
    {
        if (! Schema::hasTable('workflows') || ! Schema::hasTable('workflow_instances')) return;

        $type = $subject instanceof Ticket ? 'ticket' : ($subject instanceof Task ? 'task' : null);
        if (! $type) return;

        $instance = WorkflowInstance::query()
            ->where('subject_type', $subject::class)->where('subject_id', $subject->getKey())
            ->with('workflow.stages')->first();

        if (! $instance) {
            $workflow = Workflow::query()->where('entity_type', $type)->where('is_active', true)
                ->with('stages')->latest('updated_at')->get()
                ->first(fn (Workflow $candidate) => $this->matches($candidate, $subject));
            $first = $workflow?->stages->first();
            if (! $workflow || ! $first) return;

            $instance = WorkflowInstance::create([
                'workflow_id' => $workflow->id,
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
                'current_stage_id' => $first->id,
                'status' => 'running',
                'started_at' => now(),
            ]);
            $instance->setRelation('workflow', $workflow);
        }

        $status = strtolower((string) ($subject->status ?? ''));
        $stage = $instance->workflow->stages->first(fn ($item) => strtolower($item->status_key) === $status);
        if (! $stage) return;

        $last = $instance->workflow->stages->last();
        $completed = $last && $last->id === $stage->id;
        $instance->update([
            'current_stage_id' => $stage->id,
            'status' => $completed ? 'completed' : 'running',
            'completed_at' => $completed ? ($instance->completed_at ?? now()) : null,
        ]);
    }

    private function matches(Workflow $workflow, Model $subject): bool
    {
        foreach ($workflow->trigger_conditions ?? [] as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $expected = (string) ($condition['value'] ?? '');
            if (! in_array($field, ['priority', 'status', 'type'], true)) return false;
            $actual = (string) ($subject->getAttribute($field) ?? '');
            $passes = match ($operator) {
                'not_equals' => strcasecmp($actual, $expected) !== 0,
                'contains' => str_contains(strtolower($actual), strtolower($expected)),
                default => strcasecmp($actual, $expected) === 0,
            };
            if (! $passes) return false;
        }
        return true;
    }
}
