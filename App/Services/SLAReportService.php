<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Support\WorkflowStatus;
use App\Support\UnitVisibility;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SLAReportService
{
    private const DEFAULT_PER_PAGE = 25;
    private const MAX_PER_PAGE = 100;
    private const EXPORT_LIMIT = 5000;

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

    protected function buildTicketDataset(array $filters, bool $paginate): array
    {
        $query = Ticket::query()->with(['requester', 'assignee', 'assignedUsers', 'project'])->withCount(['tasks']);
        $this->applyUnitFilter($query, $filters, 'tickets');
        $this->applyCommonFilters($query, $filters, 'created_at', ['title', 'description', 'ticket_no']);
        $this->applyStatusFilter($query, $filters, 'tickets');
        $records = $paginate ? $query->orderByDesc('created_at')->paginate($this->perPage($filters))->withQueryString() : $query->orderByDesc('created_at')->limit(self::EXPORT_LIMIT)->get();
        if ($paginate) { $records->through(fn (Ticket $t) => $this->mapTicket($t)); } else { $records = $records->map(fn (Ticket $t) => $this->mapTicket($t)); }
        $stats = $this->summarizeCollection((clone $query)->limit(self::EXPORT_LIMIT)->get()->map(fn (Ticket $t) => $this->mapTicket($t)));
        return ['records' => $records, 'stats' => $stats];
    }

    protected function buildTaskDataset(array $filters, bool $paginate): array
    {
        $query = Task::query()->with(['ticket', 'project', 'assignee']);
        $this->applyUnitFilter($query, $filters, 'tasks');
        $this->applyCommonFilters($query, $filters, 'created_at', ['title', 'description', 'task_no']);
        $this->applyStatusFilter($query, $filters, 'tasks');
        $records = $paginate ? $query->orderByDesc('created_at')->paginate($this->perPage($filters))->withQueryString() : $query->orderByDesc('created_at')->limit(self::EXPORT_LIMIT)->get();
        if ($paginate) { $records->through(fn (Task $t) => $this->mapTask($t)); } else { $records = $records->map(fn (Task $t) => $this->mapTask($t)); }
        $stats = $this->summarizeCollection((clone $query)->limit(self::EXPORT_LIMIT)->get()->map(fn (Task $t) => $this->mapTask($t)));
        return ['records' => $records, 'stats' => $stats];
    }

    protected function buildProjectDataset(array $filters, bool $paginate): array
    {
        $query = Project::query()->with(['ticket', 'user', 'tasks', 'actions']);
        $this->applyUnitFilter($query, $filters, 'projects');
        $this->applyCommonFilters($query, $filters, 'created_at', ['title', 'description', 'project_no']);
        $this->applyStatusFilter($query, $filters, 'projects');
        $records = $paginate ? $query->orderByDesc('created_at')->paginate($this->perPage($filters))->withQueryString() : $query->orderByDesc('created_at')->limit(self::EXPORT_LIMIT)->get();
        if ($paginate) { $records->through(fn ($p) => $this->mapProject($p)); } else { $records = $records->map(fn ($p) => $this->mapProject($p)); }
        $stats = $this->summarizeCollection((clone $query)->limit(self::EXPORT_LIMIT)->get()->map(fn ($p) => $this->mapProject($p)));
        return ['records' => $records, 'stats' => $stats];
    }

    protected function buildTicketWorkDataset(array $filters, bool $paginate): array
    {
        $query = Ticket::query()->with(['requester', 'assignee', 'assignedUsers', 'project.user', 'project.actions', 'project.tasks', 'tasks.assignee', 'tasks.project'])->withCount(['tasks']);
        $this->applyUnitFilter($query, $filters, 'ticket_work');
        $this->applyCommonFilters($query, $filters, 'created_at', ['title', 'description', 'ticket_no']);
        $this->applyStatusFilter($query, $filters, 'tickets');
        $records = $paginate ? $query->orderByDesc('created_at')->paginate($this->perPage($filters))->withQueryString() : $query->orderByDesc('created_at')->limit(self::EXPORT_LIMIT)->get();
        if ($paginate) { $records->through(fn (Ticket $t) => $this->mapTicketWork($t)); } else { $records = $records->map(fn (Ticket $t) => $this->mapTicketWork($t)); }
        $stats = $this->summarizeCollection((clone $query)->limit(self::EXPORT_LIMIT)->get()->map(fn (Ticket $t) => $this->mapTicketWork($t)['ticket']));
        return ['records' => $records, 'stats' => $stats];
    }

    protected function applyUnitFilter(Builder $query, array $filters, string $context): void
    {
        $unit = trim((string) ($filters['unit'] ?? ''));
        $viewerId = (int) ($filters['viewer_id'] ?? 0);
        if ($viewerId > 0) {
            $viewer = User::find($viewerId);
            if ($viewer && !UnitVisibility::requiresRestriction($viewer)) { return; }
        }
        if ($unit === '' && $viewerId <= 0) { return; }
        if (in_array($context, ['tickets', 'ticket_work'], true)) {
            if ($unit !== '') { $query->whereHas('requester', fn ($sub) => $sub->where('unit', $unit)); }
            if ($viewerId > 0) {
                $query->where(function ($sub) use ($viewerId) {
                    $sub->where('requester_id', $viewerId)->orWhere('agent_id', $viewerId)->orWhere('assigned_id', $viewerId)->orWhereHas('assignedUsers', fn ($q) => $q->where('users.id', $viewerId));
                });
            }
        } elseif ($context === 'tasks') {
            if ($unit !== '') { $query->where(fn ($u) => $u->whereHas('ticket.requester', fn ($s) => $s->where('unit', $unit))->orWhereHas('requester', fn ($s) => $s->where('unit', $unit))); }
            if ($viewerId > 0) { $query->where('assignee_id', $viewerId); }
        } elseif ($context === 'projects') {
            if ($unit !== '') { $query->where(fn ($u) => $u->whereHas('ticket.requester', fn ($s) => $s->where('unit', $unit))->orWhereHas('requester', fn ($s) => $s->where('unit', $unit))); }
            if ($viewerId > 0) {
                $query->where(function ($sub) use ($viewerId) {
                    $sub->where('created_by', $viewerId)->orWhereHas('pics', fn ($q) => $q->where('user_id', $viewerId))->orWhereHas('ticket', fn ($q) => $q->where('agent_id', $viewerId)->orWhere('assigned_id', $viewerId));
                });
            }
        }
    }

    protected function applyCommonFilters(Builder $query, array $filters, string $column = 'created_at', array $searchColumns = []): void
    {
        if (!empty($filters['from'])) { $from = $this->parseDate($filters['from'], true); if ($from) $query->whereDate($column, '>=', $from); }
        if (!empty($filters['to'])) { $to = $this->parseDate($filters['to'], false); if ($to) $query->whereDate($column, '<=', $to); }
        if ($searchColumns && !empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function (Builder $q) use ($search, $searchColumns) {
                foreach ($searchColumns as $index => $col) { $method = $index === 0 ? 'where' : 'orWhere'; $q->{$method}($col, 'like', "%{$search}%"); }
            });
        }
    }

    protected function applyStatusFilter(Builder $query, array $filters, string $context): void
    {
        if (empty($filters['sla_status'])) return;
        $status = $filters['sla_status'];
        $now = now();
        $dueColumn = ($context === 'projects') ? 'end_date' : 'due_at';
        $finishColumn = ($context === 'tasks') ? 'completed_at' : (($context === 'projects') ? 'updated_at' : 'finish_at');
        if ($status === 'missing') { $query->whereNull($dueColumn); return; }
        if ($status === 'pending') { $query->whereNotNull($dueColumn)->whereNull($finishColumn)->whereDate($dueColumn, '>=', $now->toDateString()); return; }
        if ($status === 'met') { $query->whereNotNull($dueColumn)->whereNotNull($finishColumn)->whereColumn($finishColumn, '<=', $dueColumn); return; }
        if ($status === 'breached') {
            $query->whereNotNull($dueColumn)->where(function ($q) use ($dueColumn, $finishColumn, $now) {
                $q->where(fn($i) => $i->whereNull($finishColumn)->whereDate($dueColumn, '<', $now->toDateString()))->orWhere(fn($i) => $i->whereNotNull($finishColumn)->whereColumn($finishColumn, '>', $dueColumn));
            });
        }
    }

    protected function perPage(array $filters): int { $perPage = (int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE); return max(1, min(self::MAX_PER_PAGE, $perPage)); }

    protected function summarizeCollection(Collection $items): array
    {
        $summary = ['total' => 0, 'met' => 0, 'pending' => 0, 'breached' => 0, 'missing' => 0];
        foreach ($items as $item) {
            $sla = $item['ticket']['sla'] ?? $item['sla'] ?? null;
            $summary['total']++;
            $status = $sla['status'] ?? 'missing';
            if (array_key_exists($status, $summary)) { $summary[$status]++; } else { $summary['missing']++; }
        }
        $summary['met_percent'] = $summary['total'] > 0 ? round(($summary['met'] / $summary['total']) * 100, 1) : 0.0;
        return $summary;
    }

    protected function mapTicket(Ticket $ticket): array
    {
        $status = WorkflowStatus::normalize((string) $ticket->status);
        $due = $ticket->due_at ?: ($ticket->due_date ? $ticket->due_date->endOfDay() : null);
        $finished = $ticket->finish_at ?: ($ticket->finish_date ? $ticket->finish_date->endOfDay() : ($status === WorkflowStatus::DONE ? $ticket->updated_at : null));
        $sla = $this->evaluateSla($due, $finished, $ticket->created_at, $status);
        return [
            'id' => $ticket->id, 'number' => $ticket->ticket_no, 'title' => $ticket->title, 'status' => WorkflowStatus::label($status), 'status_code' => WorkflowStatus::code($status),
            'priority' => $ticket->priority, 'assignee' => $ticket->assignees_label ?: $this->displayUserName($ticket->assignee), 'requester' => $this->displayUserName($ticket->requester),
            'tasks_count' => $ticket->tasks_count, 'project_no' => optional($ticket->project)->project_no, 'created_at' => $this->presentDateTime($ticket->created_at),
            'deadline' => $this->presentDateTime($due), 'completed_at' => $this->presentDateTime($finished), 'duration' => $this->formatDurationMinutes($this->calculateDurationMinutes($ticket->created_at, $finished)), 'sla' => $sla,
            'detail_pdf_url' => route('sla.detail.download', ['locale' => app()->getLocale(), 'type' => 'ticket', 'id' => $ticket->id]),
        ];
    }

    protected function mapTask(Task $task): array
    {
        $status = WorkflowStatus::normalize((string) $task->status);
        $due = $task->due_at;
        $finished = $task->completed_at ? Carbon::parse($task->completed_at) : ($status === WorkflowStatus::DONE ? $task->updated_at : null);
        $sla = $this->evaluateSla($due, $finished, $task->created_at, $status);
        return [
            'id' => $task->id, 'number' => $task->task_no, 'title' => $task->title, 'status' => WorkflowStatus::label($status), 'status_code' => WorkflowStatus::code($status),
            'assignee' => $this->displayUserName($task->assignee), 'ticket_no' => optional($task->ticket)->ticket_no, 'project_no' => optional($task->project)->project_no,
            'created_at' => $this->presentDateTime($task->created_at), 'deadline' => $this->presentDateTime($due), 'completed_at' => $this->presentDateTime($finished),
            'duration' => $this->formatDurationMinutes($this->calculateDurationMinutes($task->created_at, $finished)), 'sla' => $sla,
            'detail_pdf_url' => route('sla.detail.download', ['locale' => app()->getLocale(), 'type' => 'task', 'id' => $task->id]),
        ];
    }

    protected function mapProject($project): array
    {
        $status = WorkflowStatus::normalize((string) $project->status);
        $due = $project->end_date ? Carbon::parse($project->end_date)->endOfDay() : null;
        $finished = $project->actions?->max(fn($a) => $a->end_date ? Carbon::parse($a->end_date)->endOfDay() : null) ?: ($status === WorkflowStatus::DONE ? $project->updated_at : null);
        $sla = $this->evaluateSla($due, $finished, $project->created_at, $status);
        return [
            'id' => $project->id, 'number' => $project->project_no, 'title' => $project->title, 'status' => WorkflowStatus::label($status), 'status_code' => WorkflowStatus::code($status),
            'owner' => $this->displayUserName($project->user), 'ticket_no' => optional($project->ticket)->ticket_no, 'tasks_total' => $project->tasks?->count() ?: 0,
            'created_at' => $this->presentDateTime($project->created_at), 'deadline' => $this->presentDateTime($due), 'completed_at' => $this->presentDateTime($finished),
            'duration' => $this->formatDurationMinutes($this->calculateDurationMinutes($project->created_at, $finished)), 'sla' => $sla,
            'detail_pdf_url' => route('sla.detail.download', ['locale' => app()->getLocale(), 'type' => 'project', 'id' => $project->id]),
        ];
    }

    protected function mapTicketWork(Ticket $ticket): array
    {
        $base = $this->mapTicket($ticket);
        $tasks = $ticket->tasks ? $ticket->tasks->map(fn(Task $t) => $this->mapTask($t)) : collect();
        $project = $ticket->project ? $this->mapProject($ticket->project) : null;
        return ['ticket' => $base, 'tasks' => ['items' => $tasks, 'stats' => $this->summarizeCollection($tasks)], 'project' => $project, 'detail_pdf_url' => route('sla.detail.download', ['locale' => app()->getLocale(), 'type' => 'ticket_work', 'id' => $ticket->id])];
    }

    protected function evaluateSla(?Carbon $target, ?Carbon $actual, ?Carbon $started, string $status): array
    {
        $now = now();
        $res = ['status' => 'missing', 'label' => 'SLA tidak ditentukan', 'delta_minutes' => null, 'delta_human' => '—', 'target' => $this->presentDateTime($target), 'actual' => $this->presentDateTime($actual), 'duration' => $this->formatDurationMinutes($this->calculateDurationMinutes($started, $actual ?: $now))];
        if (!$target) return $res;
        $effActual = $actual ?: ($status === WorkflowStatus::DONE ? $now : null);
        if ($effActual) {
            $diff = $effActual->diffInMinutes($target, false);
            $res['status'] = $diff >= 0 ? 'met' : 'breached';
            $res['label'] = $diff >= 0 ? 'SLA tercapai' : 'Lewat '.$this->formatDurationMinutes(abs($diff));
            $res['delta_minutes'] = $diff; $res['delta_human'] = $diff >= 0 ? 'Lebih cepat '.$this->formatDurationMinutes($diff) : 'Lewat '.$this->formatDurationMinutes(abs($diff));
            return $res;
        }
        $diff = $now->diffInMinutes($target, false);
        $res['status'] = $diff >= 0 ? 'pending' : 'breached';
        $res['label'] = $diff >= 0 ? 'Sisa '.$this->formatDurationMinutes($diff) : 'Lewat '.$this->formatDurationMinutes(abs($diff));
        $res['delta_minutes'] = $diff; $res['delta_human'] = $res['label'];
        return $res;
    }

    protected function displayUserName($user): ?string { if (!$user) return null; return trim($user->display_name ?: $user->first_name.' '.$user->last_name ?: $user->name ?: $user->username ?: $user->email) ?: 'User #'.$user->id; }

    protected function presentDateTime(?Carbon $v): array { if (!$v) return ['raw' => null, 'display' => '—', 'diff' => null]; return ['raw' => $v->toDateTimeString(), 'display' => $v->translatedFormat('d M Y H:i'), 'diff' => $v->diffForHumans()]; }

    protected function formatDurationMinutes(?float $m): string { if ($m === null) return '—'; $m = (int) round(abs($m)); $d = intdiv($m, 1440); $m %= 1440; $h = intdiv($m, 60); $m %= 60; $p = []; if ($d > 0) $p[] = $d.' hari'; if ($h > 0) $p[] = $h.' jam'; if ($m > 0 || empty($p)) $p[] = $m.' menit'; return implode(' ', $p); }

    protected function calculateDurationMinutes(?Carbon $s, ?Carbon $e): ?int { if (!$s || !$e) return null; return (int) round($s->diffInMinutes($e)); }

    protected function parseDate(?string $v, bool $start): ?string { if (empty($v)) return null; try { $c = Carbon::parse($v); } catch (\Throwable) { return null; } return $start ? $c->startOfDay()->toDateString() : $c->endOfDay()->toDateString(); }

    protected function detailTask(int $id): ?array { $t = Task::with(['ticket.requester', 'project.user', 'assignee'])->find($id); return $t ? ['type' => 'task', 'summary' => $this->mapTask($t), 'description' => $t->description, 'ticket' => $t->ticket ? $this->mapTicket($t->ticket) : null, 'project' => $t->project ? $this->mapProject($t->project) : null] : null; }
    protected function detailTicket(int $id): ?array { $t = Ticket::with(['requester', 'assignee', 'assignedUsers', 'project.user', 'tasks.assignee'])->find($id); return $t ? ['type' => 'ticket', 'summary' => $this->mapTicket($t), 'description' => $t->description, 'requester' => $this->displayUserName($t->requester), 'assignee' => $this->mapTicket($t)['assignee'], 'assigned' => $t->assignedUsers->map(fn($u) => $this->displayUserName($u))->all(), 'project' => $t->project ? $this->mapProject($t->project) : null, 'tasks' => $t->tasks->map(fn($tk) => $this->mapTask($tk))->all()] : null; }
    protected function detailProject(int $id): ?array { $p = Project::with(['ticket.requester', 'user'])->find($id); return $p ? ['type' => 'project', 'summary' => $this->mapProject($p), 'description' => $p->description, 'ticket' => $p->ticket ? $this->mapTicket($p->ticket) : null] : null; }
    protected function detailTicketWork(int $id): ?array { $t = Ticket::with(['tasks.assignee', 'project.user'])->find($id); return $t ? ['type' => 'ticket_work', 'summary' => $this->mapTicketWork($t)] : null; }
}
