<?php

namespace App\Http\Controllers\Main;

use App\Domains\Project\Models\Project;
use App\Domains\Task\Models\Task;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private function pickCol(string $table, array $candidates = ['status', 'state', 'status_id']): string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) {
                return $c;
            }
        }
        try {
            foreach (Schema::getColumnListing($table) as $col) {
                $lc = Str::lower($col);
                if (Str::contains($lc, ['status', 'state'])) {
                    return $col;
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return $candidates[0];
    }

    private function lcCast(string $col): string
    {
        return "LOWER(CAST($col AS CHAR))";
    }

    public function index(Request $request): Response
    {
        $viewerId = (int) ($request->user()?->id ?? 0);
        $usersCount = User::count();

        /* ===== Tickets ===== */
        $tCol = $this->pickCol('tickets');
        $lcT = $this->lcCast($tCol);
        $eq = fn (string $s) => "('".implode("','", \App\Support\WorkflowStatus::equivalents($s))."')";
        $ticketsBase = Ticket::query();
        if ($viewerId > 0) {
            $ticketsBase->where(function (Builder $builder) use ($viewerId) {
                $builder->where('requester_id', $viewerId)
                    ->orWhere('agent_id', $viewerId)
                    ->orWhere('assigned_id', $viewerId)
                    ->orWhereHas('assignedUsers', fn (Builder $sub) => $sub->where('users.id', $viewerId));
            });
        } else {
            $ticketsBase->whereRaw('1=0');
        }

        $ticketAgg = (clone $ticketsBase)->selectRaw("
            CASE
              WHEN $lcT IN ".$eq(\App\Support\WorkflowStatus::NEW)." THEN 'New'
              WHEN $lcT IN ".$eq(\App\Support\WorkflowStatus::IN_PROGRESS)." THEN 'In Progress'
              WHEN $lcT IN ".$eq(\App\Support\WorkflowStatus::CONFIRMATION)." THEN 'Confirmation'
              WHEN $lcT IN ".$eq(\App\Support\WorkflowStatus::REVISION)." THEN 'Revision'
              WHEN $lcT IN ".$eq(\App\Support\WorkflowStatus::DONE)." THEN 'Done'
              ELSE 'New'
            END AS s, COUNT(*) AS c
        ")->groupBy('s')->pluck('c', 's');

        $ticketsNew = (int) ($ticketAgg['New'] ?? 0);
        $ticketsInProgress = (int) ($ticketAgg['In Progress'] ?? 0);
        $ticketsConfirm = (int) ($ticketAgg['Confirmation'] ?? 0);
        $ticketsRevision = (int) ($ticketAgg['Revision'] ?? 0);
        $ticketsDone = (int) ($ticketAgg['Done'] ?? 0);

        $ticketsLabels = ['New', 'In Progress', 'Confirmation', 'Revision', 'Done'];
        $ticketsValues = [$ticketsNew, $ticketsInProgress, $ticketsConfirm, $ticketsRevision, $ticketsDone];

        /* ===== Tasks ===== */
        $taskCol = $this->pickCol('tasks');
        $lcTask = $this->lcCast($taskCol);

        $tasksBase = Task::query();
        if ($viewerId > 0) {
            $tasksBase->where(function (Builder $builder) use ($viewerId) {
                $builder->where('assignee_id', $viewerId)
                    ->orWhere('created_by', $viewerId)
                    ->orWhereHas('ticket', function (Builder $ticket) use ($viewerId) {
                        $ticket->where('requester_id', $viewerId)
                            ->orWhere('agent_id', $viewerId)
                            ->orWhere('assigned_id', $viewerId)
                            ->orWhereHas('assignedUsers', fn (Builder $sub) => $sub->where('users.id', $viewerId));
                    });
                $this->orWhereJsonAssignmentContains($builder, 'assigned_to', $viewerId);
            });
        } else {
            $tasksBase->whereRaw('1=0');
        }

        $tasksDone = (int) (clone $tasksBase)
            ->whereIn($taskCol, \App\Support\WorkflowStatus::equivalents(\App\Support\WorkflowStatus::DONE))
            ->count();

        $taskAgg = (clone $tasksBase)->selectRaw("
            CASE
              WHEN $lcTask IN ".$eq(\App\Support\WorkflowStatus::NEW)." THEN 'New'
              WHEN $lcTask IN ".$eq(\App\Support\WorkflowStatus::IN_PROGRESS)." THEN 'In Progress'
              WHEN $lcTask IN ".$eq(\App\Support\WorkflowStatus::CONFIRMATION)." THEN 'Confirmation'
              WHEN $lcTask IN ".$eq(\App\Support\WorkflowStatus::REVISION)." THEN 'Revision'
              WHEN $lcTask IN ".$eq(\App\Support\WorkflowStatus::DONE)." THEN 'Done'
              ELSE 'New'
            END AS s, COUNT(*) AS cnt
        ")->groupBy('s')->pluck('cnt', 's');

        $taskStatusLabels = ['New', 'In Progress', 'Confirmation', 'Revision', 'Done'];
        $taskStatusCounts = [
            (int) ($taskAgg['New'] ?? 0),
            (int) ($taskAgg['In Progress'] ?? 0),
            (int) ($taskAgg['Confirmation'] ?? 0),
            (int) ($taskAgg['Revision'] ?? 0),
            (int) ($taskAgg['Done'] ?? 0),
        ];

        /* ===== Projects ===== */
        $projCol = $this->pickCol('projects');
        $lcProj = $this->lcCast($projCol);

        $projectsBase = Project::query();
        if ($viewerId > 0) {
            $projectsBase->where(function (Builder $builder) use ($viewerId) {
                $builder->where('requester_id', $viewerId)
                    ->orWhere('agent_id', $viewerId)
                    ->orWhere('assigned_id', $viewerId)
                    ->orWhere('created_by', $viewerId)
                    ->orWhereHas('pics', fn (Builder $sub) => $sub->where('user_id', $viewerId))
                    ->orWhereHas('ticket', function (Builder $ticketQuery) use ($viewerId) {
                        $ticketQuery->where('requester_id', $viewerId)
                            ->orWhere('agent_id', $viewerId)
                            ->orWhere('assigned_id', $viewerId)
                            ->orWhereHas('assignedUsers', fn (Builder $sub) => $sub->where('users.id', $viewerId));
                    });
            });
        } else {
            $projectsBase->whereRaw('1=0');
        }

        $projectsCompleted = (int) (clone $projectsBase)
            ->whereIn($projCol, \App\Support\WorkflowStatus::equivalents(\App\Support\WorkflowStatus::DONE))
            ->count();

        $projectAgg = (clone $projectsBase)->selectRaw("
            CASE
              WHEN $lcProj IN ".$eq(\App\Support\WorkflowStatus::NEW)." THEN 'New'
              WHEN $lcProj IN ".$eq(\App\Support\WorkflowStatus::IN_PROGRESS)." THEN 'In Progress'
              WHEN $lcProj IN ".$eq(\App\Support\WorkflowStatus::CONFIRMATION)." THEN 'Confirmation'
              WHEN $lcProj IN ".$eq(\App\Support\WorkflowStatus::REVISION)." THEN 'Revision'
              WHEN $lcProj IN ".$eq(\App\Support\WorkflowStatus::DONE)." THEN 'Done'
              ELSE 'New'
            END AS s, COUNT(*) AS cnt
        ")->groupBy('s')->pluck('cnt', 's');

        $projectStatusLabels = ['New', 'In Progress', 'Confirmation', 'Revision', 'Done'];
        $projectStatusCounts = [
            (int) ($projectAgg['New'] ?? 0),
            (int) ($projectAgg['In Progress'] ?? 0),
            (int) ($projectAgg['Confirmation'] ?? 0),
            (int) ($projectAgg['Revision'] ?? 0),
            (int) ($projectAgg['Done'] ?? 0),
        ];

        /* ===== Tasks monthly (selaras dengan Project Report) ===== */
        $taskMonths = max(3, min(12, (int) env('DASHBOARD_TASK_MONTHS', env('DASHBOARD_PROJECT_MONTHS', 6))));
        $taskMonthStart = Carbon::now()->startOfMonth()->subMonths($taskMonths - 1);

        $taskDoneRows = (clone $tasksBase)
            ->selectRaw('DATE_FORMAT(COALESCE(completed_at, updated_at, created_at), "%Y-%m") AS ym, COUNT(*) AS c')
            ->whereRaw("$lcTask REGEXP '(done|completed|complete|finished|selesai|tuntas|2)'")
            ->whereRaw('COALESCE(completed_at, updated_at, created_at) >= ?', [$taskMonthStart->copy()->startOfDay()])
            ->groupBy('ym')
            ->pluck('c', 'ym');

        if ($taskDoneRows->isEmpty() && Schema::hasColumn('tasks', 'completed_at')) {
            $taskDoneRows = (clone $tasksBase)
                ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") AS ym, COUNT(*) AS c')
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $taskMonthStart)
                ->whereRaw("$lcTask REGEXP '(done|completed|complete|finished|selesai|tuntas|2)'")
                ->groupBy('ym')
                ->pluck('c', 'ym');
        }

        $taskActivePattern = '(progress|proses|in[_ ]?progress|on[_ ]?progress|active|open|new|baru|pending|revision|confirmation|review|1)';
        $taskProgressRows = (clone $tasksBase)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") AS ym, COUNT(*) AS c')
            ->where('created_at', '>=', $taskMonthStart)
            ->whereRaw("$lcTask REGEXP '$taskActivePattern'")
            ->groupBy('ym')
            ->pluck('c', 'ym');

        $taskReportLabels = [];
        $taskReportDoneCounts = [];
        $taskReportProgressCounts = [];
        for ($i = 0; $i < $taskMonths; $i++) {
            $month = (clone $taskMonthStart)->addMonths($i);
            $key = $month->format('Y-m');
            $label = $month->format('M');
            $taskReportLabels[] = $label;
            $taskReportDoneCounts[] = (int) ($taskDoneRows[$key] ?? 0);
            $taskReportProgressCounts[] = (int) ($taskProgressRows[$key] ?? 0);
        }

        $tasksPeriod = $taskMonthStart->format('M Y').' – '.Carbon::now()->format('M Y');

        /* ===== Projects monthly ===== */
        $projectMonths = max(3, min(12, (int) env('DASHBOARD_PROJECT_MONTHS', 6)));
        $monthStart = Carbon::now()->startOfMonth()->subMonths($projectMonths - 1);

        $projCreatedRows = (clone $projectsBase)->selectRaw('DATE_FORMAT(created_at,"%Y-%m") AS ym, COUNT(*) AS c')
            ->where('created_at', '>=', $monthStart)->groupBy('ym')->pluck('c', 'ym');

        $projCompletedRows = (clone $projectsBase)->selectRaw('DATE_FORMAT(COALESCE(updated_at, created_at),"%Y-%m") AS ym, COUNT(*) AS c')
            ->whereRaw("$lcProj REGEXP '(done|completed|complete|finished|selesai|tuntas|2)'")
            ->whereRaw('COALESCE(updated_at, created_at) >= ?', [$monthStart->copy()->startOfDay()])
            ->groupBy('ym')->pluck('c', 'ym');

        if ($projCompletedRows->isEmpty() && Schema::hasColumn('projects', 'completed_at')) {
            $projCompletedRows = (clone $projectsBase)->selectRaw('DATE_FORMAT(completed_at,"%Y-%m") AS ym, COUNT(*) AS c')
                ->whereRaw("$lcProj REGEXP '(done|completed|complete|finished|selesai|tuntas|2)'")
                ->where('completed_at', '>=', $monthStart)->groupBy('ym')->pluck('c', 'ym');
        }

        $projProgressPattern = '(progress|proses|in[_ ]?progress|on[_ ]?progress|active|open|new|baru|pending|revision|confirmation|1)';
        $projProgressRows = (clone $projectsBase)->selectRaw('DATE_FORMAT(created_at,"%Y-%m") AS ym, COUNT(*) AS c')
            ->where('created_at', '>=', $monthStart)
            ->whereRaw("$lcProj REGEXP '$projProgressPattern'")
            ->groupBy('ym')->pluck('c', 'ym');

        $projCreatedLabels = [];
        $projCreatedCounts = [];
        $projReportLabels = [];
        $projReportDoneCounts = [];
        $projReportProgressCounts = [];
        for ($i = 0; $i < $projectMonths; $i++) {
            $m = (clone $monthStart)->addMonths($i);
            $key = $m->format('Y-m');
            $lab = $m->format('M');
            $projCreatedLabels[] = $lab;
            $projCreatedCounts[] = (int) ($projCreatedRows[$key] ?? 0);
            $projReportLabels[] = $lab;
            $projReportDoneCounts[] = (int) ($projCompletedRows[$key] ?? 0);
            $projReportProgressCounts[] = (int) ($projProgressRows[$key] ?? 0);
        }

        $projectsPeriod = $monthStart->format('M Y').' – '.Carbon::now()->format('M Y');

        return Inertia::render('Dashboard/Index', [
            'pageTitle' => 'Overview',
            'pageSubtitle' => 'Ringkasan tiket, task, dan project anda.',
            'dateLabel' => Carbon::now()->format('D, d M Y'),
            'ticketsNew' => $ticketsNew,
            'ticketsInProgress' => $ticketsInProgress,
            'ticketsDone' => $ticketsDone,
            'usersCount' => $usersCount,
            'tasksDone' => $tasksDone,
            'projectsCompleted' => $projectsCompleted,
            'ticketsLabels' => $ticketsLabels,
            'ticketsValues' => $ticketsValues,
            'taskStatusLabels' => $taskStatusLabels,
            'taskStatusCounts' => $taskStatusCounts,
            'projectStatusLabels' => $projectStatusLabels,
            'projectStatusCounts' => $projectStatusCounts,
            'taskReportLabels' => $taskReportLabels,
            'taskReportDoneCounts' => $taskReportDoneCounts,
            'taskReportProgressCounts' => $taskReportProgressCounts,
            'projCreatedLabels' => $projCreatedLabels,
            'projCreatedCounts' => $projCreatedCounts,
            'projReportLabels' => $projReportLabels,
            'projReportDoneCounts' => $projReportDoneCounts,
            'projReportProgressCounts' => $projReportProgressCounts,
            'tasksPeriod' => $tasksPeriod,
            'projectsPeriod' => $projectsPeriod,
        ]);
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
}
