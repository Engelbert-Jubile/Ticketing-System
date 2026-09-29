<?php

namespace App\Http\Controllers\Main;

use App\Domains\Project\Models\Project;
use App\Domains\Task\Models\Task;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Support\RoleHelpers;
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
        $isSuperAdmin = RoleHelpers::userIsSuperAdmin($request->user());
        $usersCount = User::count();

        /* ===== Tickets ===== */
        $tCol = $this->pickCol('tickets');
        $lcT = $this->lcCast($tCol);
        $eq = fn (string $s) => "('".implode("','", \App\Support\WorkflowStatus::equivalents($s))."')";
        $ticketsBase = Ticket::query();
        if (! $isSuperAdmin && $viewerId > 0) {
            $ticketsBase->where(function (Builder $builder) use ($viewerId) {
                $builder->where('requester_id', $viewerId)
                    ->orWhere('agent_id', $viewerId)
                    ->orWhere('assigned_id', $viewerId)
                    ->orWhereHas('assignedUsers', fn (Builder $sub) => $sub->where('users.id', $viewerId));
            });
        } elseif (! $isSuperAdmin) {
            $ticketsBase->whereRaw('1=0');
        }

        $ticketAgg = $this->statusCounts($ticketsBase, $tCol);

        $ticketsNew = $ticketAgg[\App\Support\WorkflowStatus::NEW];
        $ticketsInProgress = $ticketAgg[\App\Support\WorkflowStatus::IN_PROGRESS];
        $ticketsConfirm = $ticketAgg[\App\Support\WorkflowStatus::CONFIRMATION];
        $ticketsRevision = $ticketAgg[\App\Support\WorkflowStatus::REVISION];
        $ticketsDone = $ticketAgg[\App\Support\WorkflowStatus::DONE];

        $ticketsLabels = ['New', 'In Progress', 'Confirmation', 'Revision', 'Done'];
        $ticketsValues = [$ticketsNew, $ticketsInProgress, $ticketsConfirm, $ticketsRevision, $ticketsDone];

        /* ===== Tasks ===== */
        $taskCol = $this->pickCol('tasks');
        $lcTask = $this->lcCast($taskCol);

        $tasksBase = Task::query();
        if (! $isSuperAdmin && $viewerId > 0) {
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
        } elseif (! $isSuperAdmin) {
            $tasksBase->whereRaw('1=0');
        }

        $taskAgg = $this->statusCounts($tasksBase, $taskCol);
        $tasksDone = $taskAgg[\App\Support\WorkflowStatus::DONE];

        $taskStatusLabels = ['New', 'In Progress', 'Confirmation', 'Revision', 'Done'];
        $taskStatusCounts = [
            $taskAgg[\App\Support\WorkflowStatus::NEW],
            $taskAgg[\App\Support\WorkflowStatus::IN_PROGRESS],
            $taskAgg[\App\Support\WorkflowStatus::CONFIRMATION],
            $taskAgg[\App\Support\WorkflowStatus::REVISION],
            $taskAgg[\App\Support\WorkflowStatus::DONE],
        ];

        /* ===== Projects ===== */
        $projCol = $this->pickCol('projects');
        $lcProj = $this->lcCast($projCol);

        $projectsBase = Project::query();
        if (! $isSuperAdmin && $viewerId > 0) {
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
        } elseif (! $isSuperAdmin) {
            $projectsBase->whereRaw('1=0');
        }

        $projectAgg = $this->statusCounts($projectsBase, $projCol);
        $projectsCompleted = $projectAgg[\App\Support\WorkflowStatus::DONE];

        $projectStatusLabels = ['New', 'In Progress', 'Confirmation', 'Revision', 'Done'];
        $projectStatusCounts = [
            $projectAgg[\App\Support\WorkflowStatus::NEW],
            $projectAgg[\App\Support\WorkflowStatus::IN_PROGRESS],
            $projectAgg[\App\Support\WorkflowStatus::CONFIRMATION],
            $projectAgg[\App\Support\WorkflowStatus::REVISION],
            $projectAgg[\App\Support\WorkflowStatus::DONE],
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

        /** @var \Illuminate\Database\Connection $connection */
        $connection = $builder->getConnection();
        $driver = $connection->getDriverName();
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

    /** @return array<string,int> */
    private function statusCounts(Builder $query, string $column): array
    {
        $counts = array_fill_keys([
            \App\Support\WorkflowStatus::NEW,
            \App\Support\WorkflowStatus::IN_PROGRESS,
            \App\Support\WorkflowStatus::CONFIRMATION,
            \App\Support\WorkflowStatus::REVISION,
            \App\Support\WorkflowStatus::DONE,
        ], 0);

        foreach ((clone $query)->pluck($column) as $status) {
            $normalized = \App\Support\WorkflowStatus::normalize(is_scalar($status) ? (string) $status : null);
            $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;
        }

        return $counts;
    }
}
