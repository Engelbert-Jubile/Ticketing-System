<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Support\UnitVisibility;
use App\Support\WorkflowStatus;
use Illuminate\Support\Facades\DB;

class WorkListService
{
    public function fetch(User $viewer, array $filters, bool $personal = false)
    {
        $queries = [];
        foreach (['ticket', 'task', 'project'] as $type) {
            if (($filters['type'] ?? '') && $filters['type'] !== $type) {
                continue;
            }
            $query = match ($type) {
                'ticket' => UnitVisibility::scopeTickets(Ticket::query(), $viewer),
                'task' => UnitVisibility::scopeTasks(Task::query(), $viewer),
                'project' => UnitVisibility::scopeProjects(Project::query(), $viewer),
            };
            $id = $viewer->id;
            if ($personal) {
                if ($type === 'ticket') {
                    $query->where(fn ($q) => $q->where('requester_id', $id)->orWhere('agent_id', $id)->orWhere('assigned_id', $id)->orWhereHas('assignedUsers', fn ($u) => $u->where('users.id', $id)));
                } elseif ($type === 'task') {
                    $query->where(fn ($q) => $q->where('created_by', $id)->orWhere('assignee_id', $id)->orWhere(fn ($a) => $a->whereRaw('JSON_VALID(assigned_to)')->whereJsonContains('assigned_to', (int) $id))->orWhereHas('ticket', fn ($t) => $t->where('requester_id', $id)));
                } else {
                    $query->where(fn ($q) => $q->where('created_by', $id)->orWhere('requester_id', $id)->orWhere('agent_id', $id)->orWhere('assigned_id', $id)->orWhereHas('pics', fn ($p) => $p->where('user_id', $id)));
                }
            }
            $number = $type.'_no';
            $q = trim($filters['query'] ?? '');
            if ($q !== '') {
                $query->where(fn ($b) => $b->where($number, 'like', '%'.$q.'%')->orWhere('title', 'like', '%'.$q.'%')->orWhere('description', 'like', '%'.$q.'%'));
            }
            if ($filters['status'] ?? '') {
                $query->whereIn('status', WorkflowStatus::equivalents($filters['status']));
            } elseif ($filters['active_work'] ?? false) {
                $query->whereIn('status', array_merge(WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS), WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION)));
            } elseif ($personal) {
                $query->whereNotIn('status', ['done', 'completed', 'complete', 'closed', 'cancelled', 'cancel', 'canc']);
            }
            $view = $filters['view'] ?? 'mine';
            if ($personal && $view === 'waiting') {
                $query->whereIn('status', WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION));
                $query->where(function ($b) use ($id, $type) {
                    $b->where($type === 'ticket' ? 'requester_id' : 'created_by', $id);
                    if ($type === 'project') {
                        $b->orWhere('requester_id', $id);
                    }
                    if ($type !== 'ticket') {
                        $b->orWhereHas('ticket', fn ($t) => $t->where('requester_id', $id));
                    }
                });
            }
            $day = fn ($column) => $query->getConnection()->getDriverName() === 'sqlite'
                ? "datetime({$column}, 'start of day', '+1 day', '-1 second')"
                : "TIMESTAMP(DATE({$column}), '23:59:59')";
            $due = match ($type) {
                'ticket' => 'COALESCE(due_at, '.$day('due_date').')',
                'project' => $day('end_date'),
                default => 'due_at',
            };
            if ($personal && in_array($view, ['due', 'overdue'], true)) {
                $query->whereRaw($view === 'overdue' ? "{$due} < ?" : "{$due} BETWEEN ? AND ?",
                    $view === 'overdue' ? [now()->toDateTimeString()] : [now()->toDateTimeString(), now()->addDay()->toDateTimeString()]);
            }
            $slug = $type === 'ticket' ? 'NULL' : 'public_slug';
            $priority = $type === 'project' ? 'NULL' : 'priority';
            $queries[] = $query->selectRaw("id, '{$type}' AS type, {$number} AS number, title, status, {$priority} AS priority, {$due} AS due_at, updated_at, {$slug} AS slug")->toBase();
        }
        $union = array_shift($queries);
        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        return DB::query()->fromSub($union, 'work_items')
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')->orderBy('due_at')
            ->orderByDesc('updated_at')->orderBy('type')->orderBy('id')
            ->paginate(20)->withQueryString()->through(function ($row) {
                $row->status = WorkflowStatus::normalize($row->status);
                $row->status_label = WorkflowStatus::label($row->status);
                $locale = app()->getLocale();
                $row->url = match ($row->type) {
                    'task' => $row->slug ? route('tasks.show', ['locale' => $locale, 'taskSlug' => $row->slug]) : route('tasks.show.legacy', ['locale' => $locale, 'task' => $row->id]),
                    'project' => $row->slug ? route('projects.show', ['locale' => $locale, 'project' => $row->slug]) : route('projects.show.legacy', ['locale' => $locale, 'project' => $row->id]),
                    default => route('tickets.show', ['locale' => $locale, 'ticket' => $row->id]),
                };
                $row->overdue = $row->due_at && $row->due_at < now()->toDateTimeString() && ! in_array($row->status, [WorkflowStatus::DONE, WorkflowStatus::CANCELLED], true);
                $row->due_at = $row->due_at ? \Illuminate\Support\Carbon::parse($row->due_at)->toIso8601String() : null;

                return $row;
            });
    }
}
