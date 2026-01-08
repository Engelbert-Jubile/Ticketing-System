<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Support\RoleHelpers;
use App\Support\UserUnitOptions;
use App\Support\WorkflowStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard via Inertia.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $allowed = false;

        if (RoleHelpers::userIsSuperAdmin($user)) {
            $allowed = true;
        } elseif ($user) {
            try {
                $allowed = $user->hasAnyRole(['admin', 'Admin']);
            } catch (\Throwable) {
                $allowed = false;
            }
        }

        abort_unless($allowed, 403, 'Menu Reports hanya untuk Admin.');

        $unitNames = UserUnitOptions::values();
        $ticketStats = $this->aggregateTickets();
        $taskStats = $this->aggregateTasks();
        $projectStats = $this->aggregateProjects();

        $extraUnits = collect(array_unique(array_merge(
            array_keys($ticketStats),
            array_keys($taskStats),
            array_keys($projectStats),
        )))->filter(fn ($unit) => $unit && ! in_array($unit, $unitNames, true));

        $orderedUnits = collect($unitNames)->merge($extraUnits)->values();

        $unitMetrics = $orderedUnits->map(function (string $unitName) use ($ticketStats, $taskStats, $projectStats) {
            $ticket = $ticketStats[$unitName] ?? ['total' => 0, 'active' => 0, 'done' => 0, 'pending' => 0];
            $task = $taskStats[$unitName] ?? ['total' => 0, 'active' => 0, 'done' => 0, 'pending' => 0];
            $project = $projectStats[$unitName] ?? ['total' => 0, 'active' => 0, 'done' => 0, 'pending' => 0];

            $activityScore = $this->computeActivityScore($ticket, $task, $project);
            $totalWork = $ticket['total'] + $task['total'] + $project['total'];

            return [
                'name' => $unitName,
                'tickets' => $ticket,
                'tasks' => $task,
                'projects' => $project,
                'activity_score' => $activityScore,
                'total_work' => $totalWork,
                'completion_rate' => $totalWork > 0 ? round((($ticket['done'] + $task['done'] + $project['done']) / $totalWork) * 100, 1) : 0,
            ];
        });

        $unitMetrics = $unitMetrics->sortByDesc('activity_score')->values();

        $overview = [
            'units' => $orderedUnits->count(),
            'tickets' => $this->sumStats($unitMetrics, 'tickets'),
            'tasks' => $this->sumStats($unitMetrics, 'tasks'),
            'projects' => $this->sumStats($unitMetrics, 'projects'),
        ];

        $highlights = [
            'topUnits' => $unitMetrics->take(3)->values()->all(),
            'idleUnits' => $unitMetrics->filter(fn ($unit) => $unit['total_work'] === 0)->map(fn ($unit) => ['name' => $unit['name']])->values()->all(),
        ];

        $sla = $this->aggregateSla();

        $range = $this->normalizeRange((string) $request->query('range', '90'));
        $now = Carbon::now();
        $windowStart = $now->copy()->subDays($range['value'] - 1)->startOfDay();
        $windowEnd = $now->copy()->endOfDay();

        $trend = $this->buildTrend($windowStart, $windowEnd);
        $statusMatrix = $this->buildStatusMatrix($windowStart, $windowEnd);
        $leaders = $this->buildLeaderboards($windowStart, $windowEnd);
        $alerts = $this->buildAlerts($overview, $sla, $windowStart, $windowEnd);

        return Inertia::render('Reports/Index', [
            'overview' => $overview,
            'units' => $unitMetrics->values()->all(),
            'highlights' => $highlights,
            'sla' => $sla,
            'filters' => [
                'range' => $range['value'],
                'options' => $range['options'],
                'window' => [
                    'start' => $windowStart->toDateString(),
                    'end' => $windowEnd->toDateString(),
                    'label' => sprintf('%s – %s', $windowStart->translatedFormat('d M Y'), $windowEnd->translatedFormat('d M Y')),
                ],
            ],
            'trend' => $trend,
            'statusMatrix' => $statusMatrix,
            'leaders' => $leaders,
            'alerts' => $alerts,
        ]);
    }

    /**
     * @return array<string,array{total:int,active:int,done:int,pending:int}>
     */
    private function aggregateTickets(): array
    {
        $activeStatuses = array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS),
            WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION),
            WorkflowStatus::equivalents(WorkflowStatus::REVISION)
        ));
        if (empty($activeStatuses)) {
            $activeStatuses = [WorkflowStatus::IN_PROGRESS];
        }

        $rows = Ticket::query()
            ->selectRaw('COALESCE(u.unit, "UNKNOWN") as unit')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN tickets.status IN ('.$this->placeholders(count($activeStatuses)).') THEN 1 ELSE 0 END) as active_total',
                $activeStatuses
            )
            ->selectRaw('SUM(CASE WHEN tickets.status = ? THEN 1 ELSE 0 END) as done_total', [WorkflowStatus::DONE])
            ->leftJoin('users as u', 'tickets.requester_id', '=', 'u.id')
            ->whereNotNull('u.unit')
            ->groupBy('unit')
            ->get();

        return $this->mapStats($rows);
    }

    /**
     * @return array<string,array{total:int,active:int,done:int,pending:int}>
     */
    private function aggregateTasks(): array
    {
        $activeStatuses = array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS),
            WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION)
        ));
        if (empty($activeStatuses)) {
            $activeStatuses = [WorkflowStatus::IN_PROGRESS];
        }

        $rows = Task::query()
            ->selectRaw('COALESCE(u.unit, "UNKNOWN") as unit')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN tasks.status IN ('.$this->placeholders(count($activeStatuses)).') THEN 1 ELSE 0 END) as active_total',
                $activeStatuses
            )
            ->selectRaw('SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) as done_total', [WorkflowStatus::DONE])
            ->leftJoin('users as u', 'tasks.created_by', '=', 'u.id')
            ->whereNotNull('u.unit')
            ->groupBy('unit')
            ->get();

        return $this->mapStats($rows);
    }

    /**
     * @return array<string,array{total:int,active:int,done:int,pending:int}>
     */
    private function aggregateProjects(): array
    {
        $activeStatuses = array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::IN_PROGRESS),
            WorkflowStatus::equivalents(WorkflowStatus::CONFIRMATION),
            WorkflowStatus::equivalents(WorkflowStatus::REVISION)
        ));
        if (empty($activeStatuses)) {
            $activeStatuses = [WorkflowStatus::IN_PROGRESS];
        }

        $rows = Project::query()
            ->selectRaw('COALESCE(ru.unit, cu.unit) as unit')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN projects.status IN ('.$this->placeholders(count($activeStatuses)).') THEN 1 ELSE 0 END) as active_total',
                $activeStatuses
            )
            ->selectRaw('SUM(CASE WHEN projects.status = ? THEN 1 ELSE 0 END) as done_total', [WorkflowStatus::DONE])
            ->leftJoin('users as ru', 'projects.requester_id', '=', 'ru.id')
            ->leftJoin('users as cu', 'projects.created_by', '=', 'cu.id')
            ->where(function ($builder) {
                $builder->whereNotNull('ru.unit')->orWhereNotNull('cu.unit');
            })
            ->groupBy('unit')
            ->get();

        return $this->mapStats($rows);
    }

    private function mapStats(Collection $rows): array
    {
        return $rows->mapWithKeys(function ($row) {
            $unit = (string) $row->unit;
            if ($unit === 'UNKNOWN' || $unit === '') {
                $unit = 'LAINNYA';
            }
            $total = (int) $row->total;
            $active = (int) $row->active_total;
            $done = (int) $row->done_total;
            $pending = max($total - $active - $done, 0);

            return [$unit => [
                'total' => $total,
                'active' => $active,
                'done' => $done,
                'pending' => $pending,
            ]];
        })->all();
    }

    private function computeActivityScore(array $ticket, array $task, array $project): float
    {
        $score = ($ticket['active'] * 1.5) + ($ticket['done'] * 1)
            + ($task['active'] * 1.2) + ($task['done'] * 0.8)
            + ($project['active'] * 2.5) + ($project['done'] * 1.5);

        return round($score, 2);
    }

    private function sumStats(Collection $units, string $key): array
    {
        return [
            'total' => (int) $units->sum(fn ($unit) => $unit[$key]['total']),
            'active' => (int) $units->sum(fn ($unit) => $unit[$key]['active']),
            'done' => (int) $units->sum(fn ($unit) => $unit[$key]['done']),
            'pending' => (int) $units->sum(fn ($unit) => $unit[$key]['pending']),
        ];
    }

    private function normalizeRange(?string $input): array
    {
        $allowed = [30, 60, 90, 180, 365];
        $value = is_numeric($input) ? (int) $input : 90;
        if (! in_array($value, $allowed, true)) {
            $value = 90;
        }

        $options = collect($allowed)->map(fn (int $days) => [
            'value' => $days,
            'label' => match ($days) {
                30 => '30 Hari',
                60 => '60 Hari',
                90 => '90 Hari',
                180 => '6 Bulan',
                365 => '12 Bulan',
                default => $days.' Hari',
            },
        ])->values()->all();

        return [
            'value' => $value,
            'options' => $options,
        ];
    }

    private function generateWeeklyBuckets(Carbon $start, Carbon $end): array
    {
        $buckets = [];
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $limit = $end->copy()->endOfWeek(Carbon::SUNDAY);

        while ($cursor <= $limit) {
            $weekEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY);
            if ($weekEnd->greaterThan($end)) {
                $weekEnd = $end->copy();
            }

            $buckets[] = [
                'key' => $cursor->format('oW'),
                'label' => sprintf('%s – %s', $cursor->translatedFormat('d M'), $weekEnd->translatedFormat('d M')),
                'start' => $cursor->copy(),
                'end' => $weekEnd,
            ];

            $cursor->addWeek();
        }

        return $buckets;
    }

    private function timelineSeries(string $table, string $column, Carbon $start, Carbon $end, ?callable $constraint = null): array
    {
        $query = DB::table($table)
            ->selectRaw('YEARWEEK('.$column.', 3) as bucket')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull($column)
            ->whereBetween($column, [$start, $end]);

        if ($constraint) {
            $constraint($query);
        }

        return $query
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->bucket => (int) $row->total])
            ->all();
    }

    private function buildTrend(Carbon $start, Carbon $end): array
    {
        $buckets = $this->generateWeeklyBuckets($start, $end);

        $ticketCreated = $this->timelineSeries('tickets', 'created_at', $start, $end);
        $ticketCompleted = $this->timelineSeries('tickets', 'updated_at', $start, $end, function ($query) {
            $query->where('status', WorkflowStatus::DONE);
        });

        $doneStatuses = array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::DONE),
            ['done', 'completed']
        ));

        $taskCreated = $this->timelineSeries('tasks', 'created_at', $start, $end);
        $taskCompleted = $this->timelineSeries('tasks', 'updated_at', $start, $end, function ($query) use ($doneStatuses) {
            $query->whereIn('status', $doneStatuses);
        });

        $projectCreated = $this->timelineSeries('projects', 'created_at', $start, $end);
        $projectCompleted = $this->timelineSeries('projects', 'updated_at', $start, $end, function ($query) {
            $query->where('status', WorkflowStatus::DONE);
        });

        $labels = [];
        $ticketsCreatedSeries = [];
        $ticketsCompletedSeries = [];
        $tasksCreatedSeries = [];
        $tasksCompletedSeries = [];
        $projectsCreatedSeries = [];
        $projectsCompletedSeries = [];

        foreach ($buckets as $bucket) {
            $key = $bucket['key'];
            $labels[] = $bucket['label'];
            $ticketsCreatedSeries[] = $ticketCreated[$key] ?? 0;
            $ticketsCompletedSeries[] = $ticketCompleted[$key] ?? 0;
            $tasksCreatedSeries[] = $taskCreated[$key] ?? 0;
            $tasksCompletedSeries[] = $taskCompleted[$key] ?? 0;
            $projectsCreatedSeries[] = $projectCreated[$key] ?? 0;
            $projectsCompletedSeries[] = $projectCompleted[$key] ?? 0;
        }

        $summary = [
            'tickets' => [
                'created' => array_sum($ticketsCreatedSeries),
                'completed' => array_sum($ticketsCompletedSeries),
            ],
            'tasks' => [
                'created' => array_sum($tasksCreatedSeries),
                'completed' => array_sum($tasksCompletedSeries),
            ],
            'projects' => [
                'created' => array_sum($projectsCreatedSeries),
                'completed' => array_sum($projectsCompletedSeries),
            ],
        ];

        $peaks = $this->buildTrendPeaks($buckets, [
            ['label' => 'Tickets Created', 'series' => $ticketsCreatedSeries],
            ['label' => 'Tickets Completed', 'series' => $ticketsCompletedSeries],
            ['label' => 'Tasks Created', 'series' => $tasksCreatedSeries],
            ['label' => 'Tasks Completed', 'series' => $tasksCompletedSeries],
            ['label' => 'Projects Created', 'series' => $projectsCreatedSeries],
            ['label' => 'Projects Completed', 'series' => $projectsCompletedSeries],
        ]);

        return [
            'labels' => $labels,
            'tickets' => [
                'created' => $ticketsCreatedSeries,
                'completed' => $ticketsCompletedSeries,
            ],
            'tasks' => [
                'created' => $tasksCreatedSeries,
                'completed' => $tasksCompletedSeries,
            ],
            'projects' => [
                'created' => $projectsCreatedSeries,
                'completed' => $projectsCompletedSeries,
            ],
            'summary' => $summary,
            'peaks' => $peaks,
            'window' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
        ];
    }

    private function buildTrendPeaks(array $buckets, array $seriesList): array
    {
        $labels = array_map(fn ($bucket) => $bucket['label'], $buckets);

        return collect($seriesList)->map(function (array $entry) use ($labels) {
            $series = $entry['series'];
            $maxValue = 0;
            $maxIndex = null;

            foreach ($series as $index => $value) {
                if ($value > $maxValue) {
                    $maxValue = $value;
                    $maxIndex = $index;
                }
            }

            return [
                'label' => $entry['label'],
                'value' => $maxValue,
                'period' => $maxIndex !== null ? ($labels[$maxIndex] ?? null) : null,
            ];
        })->values()->all();
    }

    private function buildStatusMatrix(Carbon $start, Carbon $end): array
    {
        return [
            'tickets' => [
                'status' => $this->groupCounts('tickets', 'status', $start, $end),
                'priority' => $this->groupCounts('tickets', 'priority', $start, $end),
                'type' => $this->groupCounts('tickets', 'type', $start, $end),
            ],
            'tasks' => [
                'status' => $this->groupCounts('tasks', 'status', $start, $end),
            ],
            'projects' => [
                'status' => $this->groupCounts('projects', 'status', $start, $end),
            ],
        ];
    }

    private function groupCounts(string $table, string $column, Carbon $start, Carbon $end, ?callable $constraint = null): array
    {
        $query = DB::table($table)
            ->selectRaw($column.' as label')
            ->selectRaw('COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end]);

        if ($constraint) {
            $constraint($query);
        }

        $rows = $query
            ->groupBy($column)
            ->orderByDesc('total')
            ->get();

        $total = (int) $rows->sum('total');

        $items = $rows->map(function ($row) use ($total) {
            $label = $row->label ?? 'Tidak Diketahui';
            $value = (int) $row->total;

            return [
                'label' => $this->formatLabel((string) $label),
                'value' => $value,
                'percentage' => $total > 0 ? round(($value / $total) * 100, 1) : 0,
            ];
        })->values()->all();

        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    private function formatLabel(?string $label): string
    {
        if ($label === null || $label === '') {
            return 'Tidak Diketahui';
        }

        $normalized = strtolower($label);

        return match ($normalized) {
            'in_progress' => 'In Progress',
            'on_progress' => 'On Progress',
            'new' => 'New',
            'done' => 'Done',
            'revision' => 'Revision',
            'confirmation' => 'Confirmation',
            'pending' => 'Pending',
            default => ucwords(str_replace(['_', '-'], ' ', $label)),
        };
    }

    private function buildLeaderboards(Carbon $start, Carbon $end): array
    {
        return [
            'tickets' => [
                'agents' => $this->topTicketAgents($start, $end),
                'requesters' => $this->topTicketRequesters($start, $end),
            ],
            'tasks' => [
                'assignees' => $this->topTaskAssignees($start, $end),
            ],
            'projects' => [
                'owners' => $this->topProjectOwners($start, $end),
            ],
        ];
    }

    private function topTicketAgents(Carbon $start, Carbon $end): array
    {
        $rows = Ticket::query()
            ->selectRaw('agent_id as user_id')
            ->selectRaw('COUNT(*) as resolved_total')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_resolution_hours')
            ->whereNotNull('agent_id')
            ->where('status', WorkflowStatus::DONE)
            ->whereBetween('updated_at', [$start, $end])
            ->groupBy('agent_id')
            ->orderByDesc('resolved_total')
            ->limit(5)
            ->get();

        return $this->mapUsersWithMetrics($rows, 'resolved_total', 'avg_resolution_hours', 'jam');
    }

    private function topTicketRequesters(Carbon $start, Carbon $end): array
    {
        $rows = Ticket::query()
            ->selectRaw('requester_id as user_id')
            ->selectRaw('COUNT(*) as requested_total')
            ->whereNotNull('requester_id')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('requester_id')
            ->orderByDesc('requested_total')
            ->limit(5)
            ->get();

        return $this->mapUsersWithMetrics($rows, 'requested_total', null, null);
    }

    private function topTaskAssignees(Carbon $start, Carbon $end): array
    {
        $doneStatuses = array_unique(array_merge(
            WorkflowStatus::equivalents(WorkflowStatus::DONE),
            ['done', 'completed']
        ));

        $rows = Task::query()
            ->selectRaw('assignee_id as user_id')
            ->selectRaw('COUNT(*) as resolved_total')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_resolution_hours')
            ->whereNotNull('assignee_id')
            ->whereIn('status', $doneStatuses)
            ->whereBetween('updated_at', [$start, $end])
            ->groupBy('assignee_id')
            ->orderByDesc('resolved_total')
            ->limit(5)
            ->get();

        return $this->mapUsersWithMetrics($rows, 'resolved_total', 'avg_resolution_hours', 'jam');
    }

    private function topProjectOwners(Carbon $start, Carbon $end): array
    {
        $rows = Project::query()
            ->selectRaw('created_by as user_id')
            ->selectRaw('COUNT(*) as delivered_total')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_cycle_days')
            ->whereNotNull('created_by')
            ->where('status', WorkflowStatus::DONE)
            ->whereBetween('updated_at', [$start, $end])
            ->groupBy('created_by')
            ->orderByDesc('delivered_total')
            ->limit(5)
            ->get();

        return $this->mapUsersWithMetrics($rows, 'delivered_total', 'avg_cycle_days', 'hari');
    }

    private function mapUsersWithMetrics(Collection $rows, string $countKey, ?string $avgKey, ?string $avgUnit): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $userIds = $rows->pluck('user_id')->filter()->unique()->map(fn ($id) => (int) $id)->all();

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get($this->availableUserColumns())
            ->keyBy('id');

        return $rows->map(function ($row) use ($users, $countKey, $avgKey, $avgUnit) {
            $userId = (int) $row->user_id;
            $user = $users->get($userId);

            return [
                'id' => $userId,
                'name' => $this->formatUserName($user),
                'value' => (int) $row->{$countKey},
                'avg' => $avgKey !== null && $row->{$avgKey} !== null ? round((float) $row->{$avgKey}, 1) : null,
                'avg_unit' => $avgUnit,
            ];
        })->values()->all();
    }

    private function availableUserColumns(): array
    {
        static $columns;

        if ($columns !== null) {
            return $columns;
        }

        $table = (new User)->getTable();
        $columns = ['id'];
        $preferredColumns = ['first_name', 'last_name', 'username', 'email'];
        $fallbackColumns = ['username', 'email'];

        try {
            foreach ($preferredColumns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $columns[] = $column;
                }
            }
        } catch (\Throwable $exception) {
            // Abaikan kegagalan introspeksi schema, gunakan kolom fallback minimal.
        }

        foreach ($fallbackColumns as $column) {
            if (! in_array($column, $columns, true)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function formatUserName(?User $user): string
    {
        if (! $user) {
            return 'Tanpa Nama';
        }

        $parts = array_filter([
            $user->first_name ?? null,
            $user->last_name ?? null,
        ], fn ($value) => filled($value));

        if (! empty($parts)) {
            return trim(implode(' ', $parts));
        }

        foreach (['name', 'full_name', 'username', 'email'] as $attribute) {
            if (! empty($user->{$attribute})) {
                return (string) $user->{$attribute};
            }
        }

        return 'User #'.$user->id;
    }

    private function buildAlerts(array $overview, array $sla, Carbon $start, Carbon $end): array
    {
        $now = Carbon::now();
        $dueSoonThreshold = $now->copy()->addDays(3)->endOfDay();

        $dueSoonBase = Ticket::query()
            ->where('status', '!=', WorkflowStatus::DONE)
            ->where(function ($query) use ($now, $dueSoonThreshold) {
                $query->whereBetween('due_at', [$now, $dueSoonThreshold])
                    ->orWhereBetween('due_date', [$now->toDateString(), $dueSoonThreshold->toDateString()]);
            });

        $dueSoonCount = (clone $dueSoonBase)->count();

        $recentBreaches = Ticket::query()
            ->select(['id', 'ticket_no', 'title', 'due_at', 'due_date', 'status'])
            ->where('status', '!=', WorkflowStatus::DONE)
            ->where(function ($query) use ($now) {
                $query->where(function ($inner) use ($now) {
                    $inner->whereNotNull('due_at')->where('due_at', '<', $now);
                })->orWhere(function ($inner) use ($now) {
                    $inner->whereNotNull('due_date')->where('due_date', '<', $now->toDateString());
                });
            })
            ->orderByRaw('COALESCE(due_at, due_date) ASC')
            ->limit(5)
            ->get()
            ->map(function ($ticket) use ($now) {
                $dueDateTime = $ticket->due_at ? $ticket->due_at->copy() : ($ticket->due_date ? $ticket->due_date->copy()->endOfDay() : null);
                $overdueHours = null;
                if ($dueDateTime) {
                    $overdueHours = round($dueDateTime->diffInMinutes($now, false) / 60, 1);
                }

                return [
                    'id' => $ticket->id,
                    'title' => $ticket->title ?? ($ticket->ticket_no ?? ('Ticket #'.$ticket->id)),
                    'due_at' => $ticket->due_at?->toIso8601String(),
                    'due_date' => $ticket->due_date?->toDateString(),
                    'status' => $this->formatLabel($ticket->status ?? '-'),
                    'overdue_hours' => $overdueHours,
                    'link' => route('tickets.show', $ticket->id),
                ];
            })->values()->all();

        $avgDelay = Ticket::query()
            ->where('status', '!=', WorkflowStatus::DONE)
            ->where(function ($query) use ($now) {
                $query->where(function ($inner) use ($now) {
                    $inner->whereNotNull('due_at')->where('due_at', '<', $now);
                })->orWhere(function ($inner) use ($now) {
                    $inner->whereNotNull('due_date')->where('due_date', '<', $now->toDateString());
                });
            })
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, COALESCE(due_at, due_date), ?)) as avg_delay', [$now])
            ->value('avg_delay');

        $avgDelay = $avgDelay !== null ? round((float) $avgDelay, 1) : 0.0;

        $dueSoonList = $dueSoonBase->limit(5)->get()->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'title' => $ticket->title ?? ($ticket->ticket_no ?? ('Ticket #'.$ticket->id)),
                'due_at' => $ticket->due_at?->toIso8601String(),
                'due_date' => $ticket->due_date?->toDateString(),
                'link' => route('tickets.show', $ticket->id),
            ];
        })->values()->all();

        $tasksDueSoon = Task::query()
            ->select(['id', 'task_no', 'title', 'due_at', 'status'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$now, $dueSoonThreshold])
            ->orderBy('due_at')
            ->limit(5)
            ->get()
            ->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title ?? ($task->task_no ?? ('Task #'.$task->id)),
                'due_at' => $task->due_at?->toIso8601String(),
                'status' => $this->formatLabel($task->status ?? '-'),
                'link' => route('tasks.show', ['taskSlug' => $task->public_slug]),
            ])->values()->all();

        $projectsDueSoon = Project::query()
            ->select(['id', 'public_slug', 'project_no', 'title', 'end_date', 'status'])
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$now->toDateString(), $dueSoonThreshold->toDateString()])
            ->orderBy('end_date')
            ->limit(5)
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'title' => $project->title ?? ($project->project_no ?? ('Project #'.$project->id)),
                'due_date' => $project->end_date?->toDateString(),
                'status' => $this->formatLabel($project->status ?? '-'),
                'link' => route('projects.show', ['project' => $project->public_slug]),
            ])->values()->all();

        return [
            'sla' => [
                'metrics' => [
                    'breached' => $sla['breached'] ?? 0,
                    'dueSoon' => $dueSoonCount,
                    'avgDelayHours' => $avgDelay,
                    'onTrack' => $sla['on_track'] ?? 0,
                    'withDeadline' => $sla['with_deadline'] ?? 0,
                ],
                'dueSoon' => $dueSoonList,
                'breached' => $recentBreaches,
            ],
            'workload' => [
                'tickets' => $overview['tickets'] ?? ['total' => 0, 'active' => 0, 'pending' => 0],
                'tasks' => $overview['tasks'] ?? ['total' => 0, 'active' => 0, 'pending' => 0],
                'projects' => $overview['projects'] ?? ['total' => 0, 'active' => 0, 'pending' => 0],
            ],
            'focus' => [
                'tasks_due_soon' => $tasksDueSoon,
                'projects_due_soon' => $projectsDueSoon,
            ],
        ];
    }

    private function placeholders(int $count): string
    {
        $count = max($count, 1);

        return implode(', ', array_fill(0, $count, '?'));
    }

    private function aggregateSla(): array
    {
        $now = now();

        $totalTickets = Ticket::count();
        $completed = Ticket::where('status', WorkflowStatus::DONE)->count();
        $withDeadline = Ticket::query()
            ->whereNotNull('due_at')
            ->orWhereNotNull('due_date')
            ->count();

        $breached = Ticket::query()
            ->where(function ($query) use ($now) {
                $query->where(function ($inner) use ($now) {
                    $inner->whereNotNull('due_at')->where('due_at', '<', $now);
                })->orWhere(function ($inner) use ($now) {
                    $inner->whereNotNull('due_date')->where('due_date', '<', $now->toDateString());
                });
            })
            ->where('status', '!=', WorkflowStatus::DONE)
            ->count();

        $onTrack = max($withDeadline - $breached, 0);
        $pending = max($totalTickets - $completed, 0);

        return [
            'total' => $totalTickets,
            'with_deadline' => $withDeadline,
            'completed' => $completed,
            'pending' => $pending,
            'breached' => $breached,
            'on_track' => $onTrack,
            'compliance_rate' => $withDeadline > 0 ? round((($withDeadline - $breached) / $withDeadline) * 100, 1) : 100,
            'breach_rate' => $withDeadline > 0 ? round(($breached / $withDeadline) * 100, 1) : 0,
        ];
    }
}
