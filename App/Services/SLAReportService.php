<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Support\WorkflowStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SLAReportService
{
    private const DEFAULT_PER_PAGE = 25;

    private const MAX_PER_PAGE = 100;

    private const EXPORT_LIMIT = 5000;

    /**
     * @return array{records: LengthAwarePaginator|Collection, stats: array<string,int|float>}
     */
    public function fetch(string $type, array $filters, bool $paginate = true): array
    {
        return match ($type) {
            'task' => $this->buildTaskDataset($filters, $paginate),
            'project' => $this->buildProjectDataset($filters, $paginate),
            'ticket_work' => $this->buildTicketWorkDataset($filters, $paginate),
            default => $this->buildTicketDataset($filters, $paginate),
        };
    }

    public function findDetail(string $type, int $id): ?array
    {
        return match ($type) {
            'task' => $this->detailTask($id),
            'project' => $this->detailProject($id),
            'ticket_work' => $this->detailTicketWork($id),
            default => $this->detailTicket($id),
        };
    }

    /**
     * @return array{records: LengthAwarePaginator|Collection, stats: array<string,int|float>}
     */
    protected function buildTicketDataset(array $filters, bool $paginate): array
    {
        $query = Ticket::query()
            ->with([
                'requester:id,first_name,last_name,username,email',
                'assignee:id,first_name,last_name,username,email',
                'assignedUsers:id,first_name,last_name,username,email',
                'project' => function ($query) {
                    $query->select([
                        'projects.id',
                        'projects.public_slug',
                        'projects.ticket_id',
                        'projects.project_no',
                        'projects.status',
                        'projects.end_date',
                        'projects.updated_at',
                        'projects.status_id',
                        'projects.title',
                        'projects.created_at',
                        'projects.created_by',
                    ]);
                },
            ])
            ->withCount(['tasks']);

        $this->applyUnitFilter($query, $filters, 'tickets');
        $this->applyCommonFilters($query, $filters, 'created_at', ['title', 'description', 'ticket_no']);
        $this->applyStatusFilter($query, $filters, 'tickets');

        $records = $paginate
            ? $query->orderByDesc('created_at')->paginate($this->perPage($filters))->withQueryString()
            : $query->orderByDesc('created_at')->limit(self::EXPORT_LIMIT)->get();

        if ($paginate) {
            $records->through(fn (Ticket $ticket) => $this->mapTicket($ticket));
        } else {
            $records = $records->map(fn (Ticket $ticket) => $this->mapTicket($ticket));
        }

        $stats = $this->summarizeCollection(
            (clone $query)->limit(self::EXPORT_LIMIT)->get()->map(fn (Ticket $ticket) => $this->mapTicket($ticket))
        );

        return [
            'records' => $records,
            'stats' => $stats,
        ];
    }

    /**
     * @return array{records: LengthAwarePaginator|Collection, stats: array<string,int|float>}
     */
    protected function buildTaskDataset(array $filters, bool $paginate): array
    {
        $query = Task::query()
            ->with([
                'ticket:id,ticket_no,title,status,due_at,due_date,finish_at,finish_date,created_at',
                'project:id,public_slug,project_no,title,status,end_date,updated_at',
                'assignee:id,first_name,last_name,username,email',
            ]);

        $this->applyUnitFilter($query, $filters, 'tasks');
        $this->applyCommonFilters($query, $filters, 'created_at', ['title', 'description', 'task_no']);
        $this->applyStatusFilter($query, $filters, 'tasks');

        $records = $paginate
            ? $query->orderByDesc('created_at')->paginate($this->perPage($filters))->withQueryString()
            : $query->orderByDesc('created_at')->limit(self::EXPORT_LIMIT)->get();

        if ($paginate) {
            $records->through(fn (Task $task) => $this->mapTask($task));
        } else {
            $records = $records->map(fn (Task $task) => $this->mapTask($task));
        }

        $stats = $this->summarizeCollection(
            (clone $query)->limit(self::EXPORT_LIMIT)->get()->map(fn (Task $task) => $this->mapTask($task))
        );

        return [
            'records' => $records,
            'stats' => $stats,
        ];
    }

    /**
     * @return array{records: LengthAwarePaginator|Collection, stats: array<string,int|float>}
     */
    protected function buildProjectDataset(array $filters, bool $paginate): array
    {
        $query = Project::query()
            ->with([
                'ticket:id,ticket_no,title,status,due_at,due_date,finish_at,finish_date',
                'user:id,first_name,last_name,username,email',
                'tasks:id,project_id,status,due_at,completed_at,created_at,title',
                'actions:id,project_id,end_date',
            ]);

        $this->applyUnitFilter($query, $filters, 'projects');
        $this->applyCommonFilters($query, $filters, 'created_at', ['title', 'description', 'project_no']);
        $this->applyStatusFilter($query, $filters, 'projects');

        $records = $paginate
            ? $query->orderByDesc('created_at')->paginate($this->perPage($filters))->withQueryString()
            : $query->orderByDesc('created_at')->limit(self::EXPORT_LIMIT)->get();

        if ($paginate) {
            $records->through(fn ($project) => $this->mapProject($project));
        } else {
            $records = $records->map(fn ($project) => $this->mapProject($project));
        }

        $stats = $this->summarizeCollection(
            (clone $query)->limit(self::EXPORT_LIMIT)->get()->map(fn ($project) => $this->mapProject($project))
        );

        return [
            'records' => $records,
            'stats' => $stats,
        ];
    }

    /**
     * @return array{records: LengthAwarePaginator|Collection, stats: array<string,int|float>}
     */
    protected function buildTicketWorkDataset(array $filters, bool $paginate): array
    {
        $query = Ticket::query()
            ->with([
                'requester:id,first_name,last_name,username,email',
                'assignee:id,first_name,last_name,username,email',
                'assignedUsers:id,first_name,last_name,username,email',
                'project' => function ($query) {
                    $query->select([
                        'projects.id',
                        'projects.public_slug',
                        'projects.ticket_id',
                        'projects.project_no',
                        'projects.status',
                        'projects.end_date',
                        'projects.updated_at',
                        'projects.status_id',
                        'projects.title',
                        'projects.created_at',
                        'projects.created_by',
                    ]);
                },
                'project.user:id,first_name,last_name,username,email',
                'project.actions:id,project_id,end_date',
                'project.tasks:id,project_id,status,due_at,completed_at,created_at,title',
                'tasks:id,task_no,ticket_id,project_id,status,due_at,completed_at,created_at,title',
                'tasks.assignee:id,first_name,last_name,username,email',
                'tasks.project:id,public_slug,project_no,title',
            ])
            ->withCount(['tasks']);

        $this->applyUnitFilter($query, $filters, 'ticket_work');
        $this->applyCommonFilters($query, $filters, 'created_at', ['title', 'description', 'ticket_no']);
        $this->applyStatusFilter($query, $filters, 'tickets');

        $records = $paginate
            ? $query->orderByDesc('created_at')->paginate($this->perPage($filters))->withQueryString()
            : $query->orderByDesc('created_at')->limit(self::EXPORT_LIMIT)->get();

        if ($paginate) {
            $records->through(fn (Ticket $ticket) => $this->mapTicketWork($ticket));
        } else {
            $records = $records->map(fn (Ticket $ticket) => $this->mapTicketWork($ticket));
        }

        $stats = $this->summarizeCollection(
            (clone $query)->limit(self::EXPORT_LIMIT)->get()->map(fn (Ticket $ticket) => $this->mapTicketWork($ticket)['ticket'])
        );

        return [
            'records' => $records,
            'stats' => $stats,
        ];
    }

    protected function applyUnitFilter(Builder $query, array $filters, string $context): void
    {
        $unit = trim((string) ($filters['unit'] ?? ''));
        $viewerId = (int) ($filters['viewer_id'] ?? 0);

        if ($unit === '' && $viewerId <= 0) {
            return;
        }

        if (in_array($context, ['tickets', 'ticket_work'], true)) {
            $query->where(function (Builder $builder) use ($unit, $viewerId) {
                if ($unit !== '') {
                    $builder->whereHas('requester', fn (Builder $sub) => $sub->where('unit', $unit));
                }

                if ($viewerId > 0) {
                    $builder->orWhere(function (Builder $sub) use ($viewerId) {
                        $sub->where('requester_id', $viewerId)
                            ->orWhere('agent_id', $viewerId)
                            ->orWhere('assigned_id', $viewerId)
                            ->orWhereHas('assignedUsers', fn (Builder $q) => $q->where('users.id', $viewerId));
                    });
                }
            });

            return;
        }

        if ($context === 'tasks') {
            $query->where(function (Builder $builder) use ($unit, $viewerId) {
                if ($unit !== '') {
                    $builder->whereHas('ticket.requester', fn (Builder $sub) => $sub->where('unit', $unit))
                        ->orWhereHas('requester', fn (Builder $sub) => $sub->where('unit', $unit));
                }

                if ($viewerId > 0) {
                    $builder->orWhere(function (Builder $sub) use ($viewerId) {
                        $sub->where('assignee_id', $viewerId)
                            ->orWhere('created_by', $viewerId)
                            ->orWhere(function (Builder $b) use ($viewerId) {
                                $this->orWhereJsonAssignmentContains($b, 'assigned_to', $viewerId);
                            })
                            ->orWhereHas('ticket', function (Builder $ticket) use ($viewerId) {
                                $ticket->where('requester_id', $viewerId)
                                    ->orWhere('agent_id', $viewerId)
                                    ->orWhere('assigned_id', $viewerId)
                                    ->orWhereHas('assignedUsers', fn (Builder $q) => $q->where('users.id', $viewerId));
                            });
                    });
                }
            });

            return;
        }

        if ($context === 'projects') {
            $query->where(function (Builder $builder) use ($unit, $viewerId) {
                if ($unit !== '') {
                    $builder->whereHas('ticket.requester', fn (Builder $sub) => $sub->where('unit', $unit))
                        ->orWhereHas('requester', fn (Builder $sub) => $sub->where('unit', $unit));
                }

                if ($viewerId > 0) {
                    $builder->orWhere(function (Builder $sub) use ($viewerId) {
                        $sub->where('requester_id', $viewerId)
                            ->orWhere('agent_id', $viewerId)
                            ->orWhere('assigned_id', $viewerId)
                            ->orWhere('created_by', $viewerId)
                            ->orWhereHas('pics', fn (Builder $q) => $q->where('user_id', $viewerId))
                            ->orWhereHas('ticket', function (Builder $ticket) use ($viewerId) {
                                $ticket->where('requester_id', $viewerId)
                                    ->orWhere('agent_id', $viewerId)
                                    ->orWhere('assigned_id', $viewerId)
                                    ->orWhereHas('assignedUsers', fn (Builder $q) => $q->where('users.id', $viewerId));
                            });
                    });
                }
            });
        }
    }

    private function orWhereJsonAssignmentContains(Builder $builder, string $column, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $driver = $builder->getConnection()->getDriverName();
        $qualified = $builder->qualifyColumn($column);
        $jsonValue = json_encode($userId);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $builder->orWhereRaw("JSON_VALID({$qualified}) AND JSON_CONTAINS({$qualified}, ?, '$')", [$jsonValue]);

            return;
        }

        if ($driver === 'sqlite') {
            $builder->orWhereRaw("json_valid({$qualified}) AND EXISTS (SELECT 1 FROM json_each({$qualified}) WHERE json_each.value = ?)", [$userId]);

            return;
        }

        $builder->orWhere($column, 'like', '%\"'.$userId.'\"%');
    }

    protected function applyCommonFilters(Builder $query, array $filters, string $column = 'created_at', array $searchColumns = []): void
    {
        if (! empty($filters['from'])) {
            $from = $this->parseDate($filters['from'], true);
            if ($from) {
                $query->whereDate($column, '>=', $from);
            }
        }

        if (! empty($filters['to'])) {
            $to = $this->parseDate($filters['to'], false);
            if ($to) {
                $query->whereDate($column, '<=', $to);
            }
        }

        if ($searchColumns && ! empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function (Builder $q) use ($search, $searchColumns) {
                foreach ($searchColumns as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $q->{$method}($column, 'like', "%{$search}%");
                }
            });
        }
    }

    protected function applyStatusFilter(Builder $query, array $filters, string $context): void
    {
        if (empty($filters['sla_status'])) {
            return;
        }

        $status = $filters['sla_status'];
        $now = now();

        if ($context === 'projects') {
            $query->where(function (Builder $builder) use ($status, $now) {
                if ($status === 'missing') {
                    $builder->whereNull('end_date');

                    return;
                }

                if ($status === 'met') {
                    $builder->whereNotNull('end_date')
                        ->whereIn('status', WorkflowStatus::equivalents(WorkflowStatus::DONE))
                        ->whereRaw('DATE(updated_at) <= end_date');

                    return;
                }

                if ($status === 'pending') {
                    $builder->whereNotNull('end_date')
                        ->whereNotIn('status', WorkflowStatus::equivalents(WorkflowStatus::DONE))
                        ->whereDate('end_date', '>=', $now->toDateString());

                    return;
                }

                if ($status === 'breached') {
                    $builder->whereNotNull('end_date')
                        ->where(function (Builder $inner) use ($now) {
                            $inner->whereNotIn('status', WorkflowStatus::equivalents(WorkflowStatus::DONE))
                                ->whereDate('end_date', '<', $now->toDateString())
                                ->orWhere(function (Builder $finished) {
                                    $finished->whereIn('status', WorkflowStatus::equivalents(WorkflowStatus::DONE))
                                        ->whereRaw('DATE(updated_at) > end_date');
                                });
                        });

                    return;
                }

                $builder->whereNotNull('end_date');
            });

            return;
        }

        $query->where(function (Builder $q) use ($status, $context, $now) {
            switch ($context) {
                case 'tasks':
                    $dueColumn = 'due_at';
                    $finishColumn = 'completed_at';
                    break;
                case 'projects':
                    $dueColumn = 'end_date';
                    $finishColumn = 'updated_at';
                    break;
                default:
                    $dueColumn = 'due_at';
                    $finishColumn = 'finish_at';
                    break;
            }

            if ($status === 'missing') {
                $q->whereNull($dueColumn);

                return;
            }

            if ($status === 'pending') {
                $q->whereNotNull($dueColumn)
                    ->where(function (Builder $inner) use ($dueColumn, $finishColumn, $now) {
                        $inner->when($finishColumn, fn (Builder $w) => $w->whereNull($finishColumn))
                            ->where(function (Builder $w) use ($dueColumn, $now) {
                                $w->whereDate($dueColumn, '>=', $now->toDateString())
                                    ->orWhere($dueColumn, '>=', $now);
                            });
                    });

                return;
            }

            if ($status === 'met') {
                $q->whereNotNull($dueColumn)
                    ->when($finishColumn, function (Builder $builder) use ($finishColumn, $dueColumn) {
                        $builder->whereNotNull($finishColumn)
                            ->whereColumn($finishColumn, '<=', $dueColumn);
                    });

                return;
            }

            if ($status === 'breached') {
                $q->whereNotNull($dueColumn)
                    ->where(function (Builder $inner) use ($dueColumn, $finishColumn, $now) {
                        $inner->where(function (Builder $unfinished) use ($dueColumn, $now, $finishColumn) {
                            $unfinished->when($finishColumn, fn (Builder $w) => $w->whereNull($finishColumn))
                                ->where(function (Builder $w) use ($dueColumn, $now) {
                                    $w->whereDate($dueColumn, '<', $now->toDateString())
                                        ->orWhere($dueColumn, '<', $now);
                                });
                        })
                            ->orWhere(function (Builder $done) use ($finishColumn, $dueColumn) {
                                $done->when($finishColumn, function (Builder $builder) use ($finishColumn, $dueColumn) {
                                    $builder->whereNotNull($finishColumn)
                                        ->whereColumn($finishColumn, '>', $dueColumn);
                                });
                            });
                    });
            }
        });
    }

    protected function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE);

        return max(1, min(self::MAX_PER_PAGE, $perPage));
    }

    /** @return array<string,int|float> */
    protected function summarizeCollection(Collection $items): array
    {
        $summary = [
            'total' => 0,
            'met' => 0,
            'pending' => 0,
            'breached' => 0,
            'missing' => 0,
        ];

        foreach ($items as $item) {
            if (isset($item['ticket'])) {
                $sla = $item['ticket']['sla'] ?? null;
            } else {
                $sla = $item['sla'] ?? null;
            }

            $summary['total']++;
            $status = $sla['status'] ?? 'missing';

            if (! array_key_exists($status, $summary)) {
                $summary['missing']++;

                continue;
            }

            $summary[$status]++;
        }

        $summary['met_percent'] = $summary['total'] > 0
            ? round(($summary['met'] / $summary['total']) * 100, 1)
            : 0.0;

        return $summary;
    }

    /**
     * @return array<string,mixed>
     */
    protected function mapTicket(Ticket $ticket): array
    {
        $status = WorkflowStatus::normalize((string) $ticket->status);
        $due = $this->resolveTicketDue($ticket);
        $finished = $this->resolveTicketFinish($ticket, $status);
        $sla = $this->evaluateSla($due, $finished, $ticket->created_at, $status);

        return [
            'id' => $ticket->id,
            'number' => $ticket->ticket_no,
            'title' => $ticket->title,
            'status' => WorkflowStatus::label($status),
            'status_code' => WorkflowStatus::code($status),
            'priority' => $ticket->priority,
            'assignee' => $ticket->assignees_label ?: $this->displayUserName($ticket->assignee),
            'requester' => $this->displayUserName($ticket->requester),
            'tasks_count' => $ticket->tasks_count ?? $ticket->tasks()->count(),
            'project_no' => optional($ticket->project)->project_no,
            'created_at' => $this->presentDateTime($ticket->created_at),
            'deadline' => $this->presentDateTime($due),
            'completed_at' => $this->presentDateTime($finished),
            'duration' => $this->formatDurationMinutes($this->calculateDurationMinutes($ticket->created_at, $finished)),
            'sla' => $sla,
            'links' => [
                'view' => route('tickets.show', ['ticket' => $ticket->id]),
                'edit' => route('tickets.edit', ['ticket' => $ticket->id]),
            ],
            'detail_pdf_url' => route('sla.detail.download', ['type' => 'ticket', 'id' => $ticket->id]),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function mapTask(Task $task): array
    {
        $status = WorkflowStatus::normalize((string) $task->status);
        $due = $task->due_at ? $task->due_at->copy() : null;
        $finished = $task->completed_at ? Carbon::parse($task->completed_at) : ($status === WorkflowStatus::DONE ? $task->updated_at : null);
        $sla = $this->evaluateSla($due, $finished, $task->created_at, $status);

        return [
            'id' => $task->id,
            'number' => $task->task_no,
            'title' => $task->title,
            'status' => WorkflowStatus::label($status),
            'status_code' => WorkflowStatus::code($status),
            'assignee' => $this->displayUserName($task->assignee),
            'ticket_no' => optional($task->ticket)->ticket_no,
            'project_no' => optional($task->project)->project_no,
            'created_at' => $this->presentDateTime($task->created_at),
            'deadline' => $this->presentDateTime($due),
            'completed_at' => $this->presentDateTime($finished),
            'duration' => $this->formatDurationMinutes($this->calculateDurationMinutes($task->created_at, $finished)),
            'sla' => $sla,
            'links' => [
                'view' => route('tasks.view', ['task' => $task->id]),
                'edit' => route('tasks.edit', ['task' => $task->id]),
            ],
            'detail_pdf_url' => route('sla.detail.download', ['type' => 'task', 'id' => $task->id]),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function mapProject($project): array
    {
        $status = WorkflowStatus::normalize((string) $project->status);
        $due = $this->resolveProjectDue($project);
        $finished = $this->resolveProjectFinish($project, $status);
        $sla = $this->evaluateSla($due, $finished, $project->created_at, $status);

        return [
            'id' => $project->id,
            'number' => $project->project_no,
            'title' => $project->title,
            'status' => WorkflowStatus::label($status),
            'status_code' => WorkflowStatus::code($status),
            'owner' => $this->displayUserName($project->user),
            'ticket_no' => optional($project->ticket)->ticket_no,
            'tasks_total' => $project->tasks?->count() ?? 0,
            'created_at' => $this->presentDateTime($project->created_at),
            'deadline' => $this->presentDateTime($due),
            'completed_at' => $this->presentDateTime($finished),
            'duration' => $this->formatDurationMinutes($this->calculateDurationMinutes($project->created_at, $finished)),
            'sla' => $sla,
            'links' => [
                'view' => route('projects.show', ['project' => $project->public_slug]),
                'edit' => route('projects.edit', ['project' => $project->public_slug]),
            ],
            'detail_pdf_url' => route('sla.detail.download', ['type' => 'project', 'id' => $project->id]),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function mapTicketWork(Ticket $ticket): array
    {
        $base = $this->mapTicket($ticket);

        $tasks = $ticket->tasks
            ? $ticket->tasks->map(fn (Task $task) => $this->mapTask($task))
            : collect();

        $project = $ticket->project ? $this->mapProject($ticket->project) : null;

        return [
            'ticket' => $base,
            'tasks' => [
                'items' => $tasks,
                'stats' => $this->summarizeCollection($tasks),
            ],
            'project' => $project,
            'detail_pdf_url' => route('sla.detail.download', ['type' => 'ticket_work', 'id' => $ticket->id]),
        ];
    }

    protected function detailTicket(int $id): ?array
    {
        $ticket = Ticket::query()
            ->with([
                'requester',
                'assignee',
                'assignedUsers',
                'project.user',
                'tasks',
                'tasks.assignee',
            ])
            ->find($id);

        if (! $ticket) {
            return null;
        }

        $summary = $this->mapTicket($ticket);

        return [
            'type' => 'ticket',
            'summary' => $summary,
            'description' => $ticket->description,
            'requester' => $this->displayUserName($ticket->requester),
            'assignee' => $summary['assignee'] ?? null,
            'assigned' => $ticket->assignedUsers->map(fn ($user) => $this->displayUserName($user))->values()->all(),
            'project' => $ticket->project ? $this->mapProject($ticket->project) : null,
            'tasks' => $ticket->tasks ? $ticket->tasks->map(fn (Task $task) => $this->mapTask($task))->values()->all() : [],
        ];
    }

    protected function detailTask(int $id): ?array
    {
        $task = Task::query()
            ->with([
                'ticket.requester',
                'ticket.assignee',
                'ticket.assignedUsers',
                'ticket.project',
                'project.user',
                'assignee',
            ])
            ->find($id);

        if (! $task) {
            return null;
        }

        $summary = $this->mapTask($task);

        return [
            'type' => 'task',
            'summary' => $summary,
            'description' => $task->description,
            'ticket' => $task->ticket ? $this->mapTicket($task->ticket) : null,
            'project' => $task->project ? $this->mapProject($task->project) : null,
        ];
    }

    protected function detailProject(int $id): ?array
    {
        $project = Project::query()
            ->with(['ticket.requester', 'ticket.assignee', 'ticket.assignedUsers', 'user'])
            ->find($id);

        if (! $project) {
            return null;
        }

        $summary = $this->mapProject($project);

        return [
            'type' => 'project',
            'summary' => $summary,
            'description' => $project->description,
            'ticket' => $project->ticket ? $this->mapTicket($project->ticket) : null,
        ];
    }

    protected function detailTicketWork(int $id): ?array
    {
        $ticket = Ticket::query()
            ->with([
                'tasks',
                'tasks.assignee',
                'project',
                'project.user',
            ])
            ->find($id);

        if (! $ticket) {
            return null;
        }

        $summary = $this->mapTicketWork($ticket);

        return [
            'type' => 'ticket_work',
            'summary' => $summary,
        ];
    }

    protected function resolveTicketDue(Ticket $ticket): ?Carbon
    {
        if ($ticket->due_at) {
            return $ticket->due_at->copy();
        }

        if ($ticket->due_date) {
            return $ticket->due_date->endOfDay();
        }

        return null;
    }

    protected function resolveTicketFinish(Ticket $ticket, string $status): ?Carbon
    {
        if ($ticket->finish_at) {
            return $ticket->finish_at->copy();
        }

        if ($ticket->finish_date) {
            return $ticket->finish_date->endOfDay();
        }

        if ($status === WorkflowStatus::DONE) {
            return $ticket->updated_at?->copy();
        }

        return null;
    }

    protected function resolveProjectDue($project): ?Carbon
    {
        if ($project->end_date) {
            return Carbon::parse($project->end_date)->endOfDay();
        }

        if (is_array($project->planning)) {
            $end = $project->planning['end'] ?? null;
            if ($end) {
                try {
                    return Carbon::parse($end)->endOfDay();
                } catch (\Throwable) {
                    // ignore
                }
            }
        }

        return null;
    }

    protected function resolveProjectFinish($project, string $status): ?Carbon
    {
        $actionEnd = $project->actions?->max(fn ($action) => $action->end_date ? Carbon::parse($action->end_date)->endOfDay() : null);
        if ($actionEnd) {
            return $actionEnd;
        }

        if ($status === WorkflowStatus::DONE) {
            return $project->updated_at?->copy();
        }

        return null;
    }

    protected function evaluateSla(?Carbon $target, ?Carbon $actual, ?Carbon $started, string $status): array
    {
        $now = now();

        $result = [
            'status' => 'missing',
            'label' => 'SLA tidak ditentukan',
            'delta_minutes' => null,
            'delta_human' => '—',
            'target' => $this->presentDateTime($target),
            'actual' => $this->presentDateTime($actual),
            'duration' => $this->formatDurationMinutes($this->calculateDurationMinutes($started, $actual ?? $now)),
        ];

        if (! $target) {
            return $result;
        }

        $effectiveActual = $actual ?? ($status === WorkflowStatus::DONE ? $now : null);

        if ($effectiveActual) {
            $diff = $effectiveActual->diffInMinutes($target, false);
            if ($diff >= 0) {
                $result['status'] = 'met';
                $result['label'] = 'SLA tercapai';
                $result['delta_minutes'] = $diff;
                $result['delta_human'] = 'Lebih cepat '.$this->formatDurationMinutes($diff);
            } else {
                $result['status'] = 'breached';
                $result['label'] = 'Lewat '.$this->formatDurationMinutes(abs($diff));
                $result['delta_minutes'] = $diff;
                $result['delta_human'] = 'Lewat '.$this->formatDurationMinutes(abs($diff));
            }

            return $result;
        }

        $diff = $now->diffInMinutes($target, false);
        if ($diff >= 0) {
            $result['status'] = 'pending';
            $result['label'] = 'Sisa '.$this->formatDurationMinutes($diff);
            $result['delta_minutes'] = $diff;
            $result['delta_human'] = 'Sisa '.$this->formatDurationMinutes($diff);
        } else {
            $result['status'] = 'breached';
            $result['label'] = 'Lewat '.$this->formatDurationMinutes(abs($diff));
            $result['delta_minutes'] = $diff;
            $result['delta_human'] = 'Lewat '.$this->formatDurationMinutes(abs($diff));
        }

        return $result;
    }

    protected function displayUserName($user): ?string
    {
        if (! $user) {
            return null;
        }

        $candidates = [
            $user->display_name ?? null,
            trim(implode(' ', array_filter([$user->first_name ?? null, $user->last_name ?? null]))),
            $user->name ?? null,
            $user->full_name ?? null,
            $user->username ?? null,
            $user->email ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return 'User #'.($user->id ?? '');
    }

    protected function presentDateTime(?Carbon $value): array
    {
        if (! $value) {
            return [
                'raw' => null,
                'display' => '—',
                'diff' => null,
            ];
        }

        return [
            'raw' => $value->toDateTimeString(),
            'display' => $value->translatedFormat('d M Y H:i'),
            'diff' => $value->diffForHumans(),
        ];
    }

    protected function formatDurationMinutes(?float $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        $minutes = (int) round(abs($minutes));

        $days = intdiv($minutes, 1440);
        $minutes %= 1440;
        $hours = intdiv($minutes, 60);
        $minutes %= 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days.' hari';
        }
        if ($hours > 0) {
            $parts[] = $hours.' jam';
        }
        if ($minutes > 0 || empty($parts)) {
            $parts[] = $minutes.' menit';
        }

        return implode(' ', $parts);
    }

    protected function calculateDurationMinutes(?Carbon $start, ?Carbon $end): ?int
    {
        if (! $start || ! $end) {
            return null;
        }

        return (int) round($start->diffInMinutes($end));
    }

    protected function parseDate(?string $value, bool $startOfDay): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $carbon = Carbon::parse($value);
        } catch (\Throwable) {
            try {
                $carbon = Carbon::createFromFormat('d/m/Y', $value);
            } catch (\Throwable) {
                return null;
            }
        }

        return $startOfDay ? $carbon->startOfDay()->toDateString() : $carbon->endOfDay()->toDateString();
    }
}
