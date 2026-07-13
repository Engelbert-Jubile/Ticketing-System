<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Ticket;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class WorkflowRuntimeService
{
    public function sync(Model $subject): void
    {
        if (! $this->available()) {
            return;
        }

        $type = $subject instanceof Ticket ? 'ticket' : ($subject instanceof Task ? 'task' : null);
        if (! $type) {
            return;
        }

        $instance = WorkflowInstance::query()
            ->where('subject_type', $subject::class)->where('subject_id', $subject->getKey())
            ->with('workflow.stages')->first();

        if (! $instance) {
            $workflow = $this->resolveWorkflow($type, $subject);
            $first = $workflow?->stages->first();
            if (! $workflow || ! $first) {
                return;
            }

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

        $status = WorkflowStatus::normalize((string) ($subject->status ?? ''));
        $stage = $instance->workflow->stages->first(
            fn ($item) => WorkflowStatus::normalize($item->status_key) === $status
        );
        if (! $stage) {
            return;
        }

        $completed = $status === WorkflowStatus::DONE;
        $cancelled = $status === WorkflowStatus::CANCELLED;
        $instance->update([
            'current_stage_id' => $stage->id,
            'status' => $completed ? 'completed' : ($cancelled ? 'cancelled' : 'running'),
            'completed_at' => $completed ? ($instance->completed_at ?? now()) : null,
        ]);
    }

    public function syncExisting(): array
    {
        $counts = ['ticket' => 0, 'task' => 0];
        if (! $this->available()) {
            return $counts;
        }

        foreach ([Ticket::class => 'ticket', Task::class => 'task'] as $model => $type) {
            if (! Schema::hasTable((new $model)->getTable())) {
                continue;
            }

            $model::query()->chunkById(250, function ($subjects) use (&$counts, $type): void {
                foreach ($subjects as $subject) {
                    $this->sync($subject);
                    $counts[$type]++;
                }
            });
        }

        return $counts;
    }

    public function syncWorkflow(Workflow $workflow): void
    {
        if (! $this->available()) {
            return;
        }

        $workflow->instances()->with('subject')->chunkById(250, function ($instances): void {
            foreach ($instances as $instance) {
                if ($instance->subject instanceof Ticket || $instance->subject instanceof Task) {
                    $this->sync($instance->subject);
                }
            }
        });
    }

    private function resolveWorkflow(string $type, Model $subject): ?Workflow
    {
        $defaultCode = strtoupper($type).'_DEFAULT';

        return Workflow::query()
            ->where('entity_type', $type)
            ->where('is_active', true)
            ->with('stages')
            ->latest('updated_at')
            ->get()
            ->filter(fn (Workflow $candidate) => $this->matches($candidate, $subject))
            ->sort(function (Workflow $left, Workflow $right) use ($defaultCode): int {
                $conditionOrder = count($right->trigger_conditions ?? []) <=> count($left->trigger_conditions ?? []);
                if ($conditionOrder !== 0) {
                    return $conditionOrder;
                }

                $leftDefault = $left->code === $defaultCode;
                $rightDefault = $right->code === $defaultCode;
                if ($leftDefault !== $rightDefault) {
                    return $leftDefault ? -1 : 1;
                }

                return $right->updated_at <=> $left->updated_at;
            })
            ->first();
    }

    private function available(): bool
    {
        return Schema::hasTable('workflows')
            && Schema::hasTable('workflow_stages')
            && Schema::hasTable('workflow_instances');
    }

    private function matches(Workflow $workflow, Model $subject): bool
    {
        foreach ($workflow->trigger_conditions ?? [] as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $expected = (string) ($condition['value'] ?? '');
            if (! in_array($field, ['priority', 'status', 'type'], true)) {
                return false;
            }
            $actual = (string) ($subject->getAttribute($field) ?? '');
            $passes = match ($operator) {
                'not_equals' => strcasecmp($actual, $expected) !== 0,
                'contains' => str_contains(strtolower($actual), strtolower($expected)),
                default => strcasecmp($actual, $expected) === 0,
            };
            if (! $passes) {
                return false;
            }
        }

        return true;
    }
}
