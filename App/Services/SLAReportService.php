<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Support\SlaClock;
use App\Support\UnitVisibility;
use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SLAReportService
{
    private const DEFAULT_PER_PAGE = 25;

    private const MAX_PER_PAGE = 100;

    private const EXPORT_LIMIT = 5000;

    public function fetch(string $type, array $filters, bool $paginate = true): array
    {
        $viewer = User::find($filters['viewer_id'] ?? 0);
        abort_unless($viewer, 403);
        $entity = $type === 'ticket_work' ? 'ticket' : $type;
        $query = $this->visibleQuery($entity, $viewer);
        $number = $entity.'_no';
        $this->applyCommonFilters($query, $filters, 'created_at', ['title', 'description', $number]);
        $expression = SlaClock::expression($query, $entity);
        $clock = now()->toDateTimeString();
        $status = $filters['sla_status'] ?? '';
        if (in_array($status, ['missing', 'met', 'pending', 'breached'], true)) {
            $query->whereRaw("({$expression}) = ?", [$clock, $status]);
        }

        // Aggregate the complete filtered dataset before pagination or export limits.
        $aggregate = (clone $query)->withoutEagerLoads()->selectRaw("{$expression} AS sla_result", [$clock]);
        $counts = DB::query()->fromSub($aggregate->toBase(), 'sla_items')
            ->selectRaw('sla_result, COUNT(*) AS aggregate')->groupBy('sla_result')->pluck('aggregate', 'sla_result');
        $stats = ['total' => 0, 'met' => 0, 'pending' => 0, 'breached' => 0, 'missing' => 0];
        foreach ($counts as $key => $count) {
            $stats[$key] = (int) $count;
            $stats['total'] += (int) $count;
        }
        $stats['met_percent'] = $stats['total'] ? round($stats['met'] / $stats['total'] * 100, 1) : 0;
        $stats['export_limit'] = self::EXPORT_LIMIT;

        $recordsQuery = (clone $query)->orderByDesc('created_at')->orderByDesc('id');
        $mapper = fn ($item) => $type === 'ticket_work' ? $this->mapTicketWork($item) : $this->mapItem($item, $entity);
        $records = $paginate
            ? $recordsQuery->paginate($this->perPage($filters))->withQueryString()->through($mapper)
            : $recordsQuery->limit(self::EXPORT_LIMIT)->get()->map($mapper);

        return ['records' => $records, 'stats' => $stats];
    }

    public function findDetail(string $type, int $id, User $viewer): ?array
    {
        $entity = $type === 'ticket_work' ? 'ticket' : $type;
        $item = $this->visibleQuery($entity, $viewer)->find($id);
        if (! $item) {
            return null;
        }
        if ($type === 'ticket_work') {
            return ['type' => $type, 'summary' => $this->mapTicketWork($item)];
        }
        $detail = ['type' => $entity, 'summary' => $this->mapItem($item, $entity), 'description' => $item->description];
        if ($entity === 'ticket') {
            return $detail + [
                'requester' => $this->displayUserName($item->requester),
                'assignee' => $detail['summary']['assignee'],
                'assigned' => $item->assignedUsers->map(fn ($user) => $this->displayUserName($user))->all(),
                'tasks' => $item->tasks->map(fn ($task) => $this->mapTask($task))->all(),
                'project' => $item->project ? $this->mapProject($item->project) : null,
            ];
        }
        $detail['ticket'] = $item->ticket ? $this->mapTicket($item->ticket) : null;
        if ($entity === 'task') {
            $detail['project'] = $item->project ? $this->mapProject($item->project) : null;
        }

        return $detail;
    }

    private function mapItem($item, string $type): array
    {
        return match ($type) {
            'task' => $this->mapTask($item),
            'project' => $this->mapProject($item),
            default => $this->mapTicket($item),
        };
    }

    private function visibleQuery(string $type, User $viewer): Builder
    {
        $projectRelations = ['user',
            'tasks' => fn ($q) => UnitVisibility::scopeTasks($q->getQuery(), $viewer),
            'ticket' => fn ($q) => UnitVisibility::scopeTickets($q->getQuery(), $viewer),
        ];
        $ticketRelations = ['requester', 'assignee', 'assignedUsers',
            'project' => fn ($q) => UnitVisibility::scopeProjects($q->getQuery(), $viewer)->with($projectRelations),
        ];

        return match ($type) {
            'task' => UnitVisibility::scopeTasks(Task::query(), $viewer)->with([
                'assignee',
                'ticket' => fn ($q) => UnitVisibility::scopeTickets($q->getQuery(), $viewer)->with($ticketRelations),
                'project' => fn ($q) => UnitVisibility::scopeProjects($q->getQuery(), $viewer)->with($projectRelations),
            ]),
            'project' => UnitVisibility::scopeProjects(Project::query(), $viewer)->with($projectRelations)->with([
                'ticket' => fn ($q) => UnitVisibility::scopeTickets($q->getQuery(), $viewer)->with($ticketRelations),
            ]),
            default => UnitVisibility::scopeTickets(Ticket::query(), $viewer)->with($ticketRelations)->with([
                'project' => fn ($q) => UnitVisibility::scopeProjects($q->getQuery(), $viewer)->with($projectRelations),
                'tasks' => fn ($q) => UnitVisibility::scopeTasks($q->getQuery(), $viewer)->with([
                    'assignee',
                    'ticket' => fn ($t) => UnitVisibility::scopeTickets($t->getQuery(), $viewer),
                    'project' => fn ($p) => UnitVisibility::scopeProjects($p->getQuery(), $viewer)->with($projectRelations),
                ]),
            ])->withCount(['tasks' => fn ($q) => UnitVisibility::scopeTasks($q, $viewer)]),
        };
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
                foreach ($searchColumns as $index => $col) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $q->{$method}($col, 'like', "%{$search}%");
                }
            });
        }
    }

    protected function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE);

        return max(1, min(self::MAX_PER_PAGE, $perPage));
    }

    protected function summarizeCollection(Collection $items): array
    {
        $summary = ['total' => 0, 'met' => 0, 'pending' => 0, 'breached' => 0, 'missing' => 0];
        foreach ($items as $item) {
            $sla = $item['ticket']['sla'] ?? $item['sla'] ?? null;
            $summary['total']++;
            $status = $sla['status'] ?? 'missing';
            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            } else {
                $summary['missing']++;
            }
        }
        $summary['met_percent'] = $summary['total'] > 0 ? round(($summary['met'] / $summary['total']) * 100, 1) : 0.0;

        return $summary;
    }

    protected function mapTicket(Ticket $ticket): array
    {
        $status = WorkflowStatus::normalize((string) $ticket->status);
        [$due, $finished] = SlaClock::dates($ticket, 'ticket');
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
        [$due, $finished] = SlaClock::dates($task, 'task');
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
        [$due, $finished] = SlaClock::dates($project, 'project');
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
        $tasks = $ticket->tasks ? $ticket->tasks->map(fn (Task $t) => $this->mapTask($t)) : collect();
        $project = $ticket->project ? $this->mapProject($ticket->project) : null;

        return ['ticket' => $base, 'tasks' => ['items' => $tasks, 'stats' => $this->summarizeCollection($tasks)], 'project' => $project, 'detail_pdf_url' => route('sla.detail.download', ['locale' => app()->getLocale(), 'type' => 'ticket_work', 'id' => $ticket->id])];
    }

    protected function evaluateSla(?Carbon $target, ?Carbon $actual, ?Carbon $started, string $status): array
    {
        $now = now();
        $res = ['status' => 'missing', 'label' => 'SLA tidak ditentukan', 'delta_minutes' => null, 'delta_human' => '—', 'target' => $this->presentDateTime($target), 'actual' => $this->presentDateTime($actual), 'duration' => $this->formatDurationMinutes($this->calculateDurationMinutes($started, $actual ?: $now))];
        if (! $target) {
            return $res;
        }
        $effActual = $actual;
        if ($effActual) {
            $diff = $effActual->diffInMinutes($target, false);
            $res['status'] = $diff >= 0 ? 'met' : 'breached';
            $res['label'] = $diff >= 0 ? 'SLA tercapai' : 'Lewat '.$this->formatDurationMinutes(abs($diff));
            $res['delta_minutes'] = $diff;
            $res['delta_human'] = $diff >= 0 ? 'Lebih cepat '.$this->formatDurationMinutes($diff) : 'Lewat '.$this->formatDurationMinutes(abs($diff));

            return $res;
        }
        $diff = $now->diffInMinutes($target, false);
        $res['status'] = $diff >= 0 ? 'pending' : 'breached';
        $res['label'] = $diff >= 0 ? 'Sisa '.$this->formatDurationMinutes($diff) : 'Lewat '.$this->formatDurationMinutes(abs($diff));
        $res['delta_minutes'] = $diff;
        $res['delta_human'] = $res['label'];

        return $res;
    }

    protected function displayUserName($user): ?string
    {
        if (! $user) {
            return null;
        }

return trim($user->display_name ?: $user->first_name.' '.$user->last_name ?: $user->name ?: $user->username ?: $user->email) ?: 'User #'.$user->id;
    }

    protected function presentDateTime(?Carbon $v): array
    {
        if (! $v) {
            return ['raw' => null, 'display' => '—', 'diff' => null];
        }

return ['raw' => $v->toDateTimeString(), 'display' => $v->translatedFormat('d M Y H:i'), 'diff' => $v->diffForHumans()];
    }

    protected function formatDurationMinutes(?float $m): string
    {
        if ($m === null) {
            return '—';
        } $m = (int) round(abs($m));
        $d = intdiv($m, 1440);
        $m %= 1440;
        $h = intdiv($m, 60);
        $m %= 60;
        $p = [];
        if ($d > 0) {
            $p[] = $d.' hari';
        } if ($h > 0) {
            $p[] = $h.' jam';
        } if ($m > 0 || empty($p)) {
            $p[] = $m.' menit';
        }

return implode(' ', $p);
    }

    protected function calculateDurationMinutes(?Carbon $s, ?Carbon $e): ?int
    {
        if (! $s || ! $e) {
            return null;
        }

return (int) round($s->diffInMinutes($e));
    }

    protected function parseDate(?string $v, bool $start): ?string
    {
        if (empty($v)) {
            return null;
        } try {
            $c = Carbon::parse($v);
        } catch (\Throwable) {
            return null;
        }

return $start ? $c->startOfDay()->toDateString() : $c->endOfDay()->toDateString();
    }
}
