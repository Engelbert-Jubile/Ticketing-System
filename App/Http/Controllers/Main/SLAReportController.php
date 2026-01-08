<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Services\ReportExportService;
use App\Services\SLAReportService;
use App\Support\UnitVisibility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SLAReportController extends Controller
{
    public function __construct(
        private readonly SLAReportService $service,
        private readonly ReportExportService $reportExport
    ) {}

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $result = $this->service->fetch($filters['type'], $filters, true);
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $records */
        $records = $result['records'];
        /** @var array<string, int|float> $stats */
        $stats = $result['stats'];

        return Inertia::render('Reports/Sla', [
            'type' => $filters['type'],
            'filters' => [
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'sla_status' => $filters['sla_status'] ?? '',
                'q' => $filters['q'] ?? '',
                'per_page' => $filters['per_page'],
            ],
            'stats' => $stats,
            'availableTypes' => collect($this->types())
                ->map(fn (string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])->values()->all(),
            'statusOptions' => $this->statusOptions(),
            'downloadParams' => $this->downloadParams($filters),
            'records' => $records,
        ]);
    }

    public function download(Request $request)
    {
        $filters = $this->filters($request);
        $format = $request->query('format', 'csv');
        $filters['per_page'] = 0; // fetch method ignores per_page when exporting

        $result = $this->service->fetch($filters['type'], $filters, false);
        /** @var \Illuminate\Support\Collection $records */
        $records = $result['records'];

        $filename = sprintf('sla-%s-%s.%s', $filters['type'], now()->format('Ymd-His'), $format);

        if ($format === 'pdf') {
            $columns = collect($this->csvHeaders($filters['type']))
                ->map(fn (string $label) => ['label' => $label])
                ->values()
                ->all();

            $rows = collect($records)
                ->map(function ($row) use ($filters) {
                    $rowArray = is_array($row) ? $row : (array) $row;
                    $mapped = $this->csvRow($filters['type'], $rowArray);

                    return array_map(function ($value) {
                        if (is_null($value) || $value === '') {
                            return '—';
                        }

                        if (is_array($value)) {
                            return implode(', ', array_filter(array_map('strval', $value)));
                        }

                        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                    }, $mapped);
                })
                ->values()
                ->all();

            $meta = [
                'filters' => [
                    'Tipe' => $this->types()[$filters['type']] ?? ucfirst($filters['type']),
                    'Rentang' => $this->formatRangeFilter($filters['from'] ?? null, $filters['to'] ?? null),
                    'Status SLA' => $this->resolveSlaStatusLabel($filters['sla_status'] ?? null),
                    'Pencarian' => ($filters['q'] ?? '') !== '' ? $filters['q'] : 'Semua',
                    'Total Data' => number_format(count($rows)),
                ],
            ];

            return $this->reportExport->downloadPdf('Laporan SLA', $columns, $rows, $meta, $filename);
        }

        return response()->streamDownload(function () use ($filters, $records) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, $this->csvHeaders($filters['type']));

            foreach ($records as $row) {
                $rowArray = is_array($row) ? $row : (array) $row;
                fputcsv($handle, $this->csvRow($filters['type'], $rowArray));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadDetail(Request $request, string $type, int $id)
    {
        $allowedTypes = array_keys($this->types());
        if (! in_array($type, $allowedTypes, true)) {
            $type = 'ticket';
        }

        $detail = $this->service->findDetail($type, $id);
        if (! $detail) {
            abort(404);
        }

        $summary = $detail['summary'] ?? [];
        $title = sprintf('Detail SLA %s', $this->types()[$type] ?? ucfirst($type));

        $filename = sprintf(
            'sla-%s-%s-detail.pdf',
            $type,
            Str::slug((string) ($summary['number'] ?? $id))
        );

        $description = $detail['description'] ?? null;
        if (is_string($description)) {
            $description = strip_tags($description, '<p><br><strong><em><ul><ol><li><b><i><u>');
            if (trim(strip_tags($description)) === '') {
                $description = null;
            }
        }

        return $this->reportExport->downloadDetailPdf('reports.pdf.sla-detail', [
            'title' => $title,
            'type' => $type,
            'summary' => $summary,
            'detail' => $detail,
            'description' => $description,
        ], $filename);
    }

    protected function filters(Request $request): array
    {
        $viewer = $request->user();
        $type = (string) $request->query('type', 'ticket');
        $allowed = array_keys($this->types());
        if (! in_array($type, $allowed, true)) {
            $type = 'ticket';
        }

        $filters = [
            'type' => $type,
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'sla_status' => $request->query('sla_status'),
            'q' => $request->query('q'),
            'per_page' => $this->resolvePerPage((int) $request->query('per_page', 25)),
            'viewer_id' => $viewer?->id,
        ];

        if (UnitVisibility::requiresRestriction($viewer)) {
            $unit = trim((string) ($viewer?->unit ?? ''));
            if ($unit !== '') {
                $filters['unit'] = $unit;
            }
        }

        return $filters;
    }

    /** @return array<string,string> */
    protected function types(): array
    {
        return [
            'ticket' => 'Tickets',
            'task' => 'Tasks',
            'project' => 'Projects',
            'ticket_work' => 'Ticket + Task/Project',
        ];
    }

    protected function csvHeaders(string $type): array
    {
        return match ($type) {
            'task' => ['Task No', 'Title', 'Status', 'Assignee', 'Ticket', 'Project', 'Deadline', 'Completed', 'SLA Status', 'SLA Detail'],
            'project' => ['Project No', 'Title', 'Status', 'Owner', 'Ticket', 'Deadline', 'Completed', 'SLA Status', 'SLA Detail'],
            'ticket_work' => ['Ticket No', 'Title', 'Status', 'Assignee', 'Deadline', 'Completed', 'SLA Status', 'Tasks (total/done)', 'Project SLA'],
            default => ['Ticket No', 'Title', 'Status', 'Priority', 'Assignee', 'Deadline', 'Completed', 'SLA Status', 'SLA Detail'],
        };
    }

    protected function csvRow(string $type, array $row): array
    {
        return match ($type) {
            'task' => [
                $row['number'] ?? null,
                $row['title'] ?? null,
                $row['status'] ?? null,
                $row['assignee'] ?? null,
                $row['ticket_no'] ?? null,
                $row['project_no'] ?? null,
                $row['deadline']['display'] ?? null,
                $row['completed_at']['display'] ?? null,
                $row['sla']['label'] ?? null,
                $row['sla']['delta_human'] ?? null,
            ],
            'project' => [
                $row['number'] ?? null,
                $row['title'] ?? null,
                $row['status'] ?? null,
                $row['owner'] ?? null,
                $row['ticket_no'] ?? null,
                $row['deadline']['display'] ?? null,
                $row['completed_at']['display'] ?? null,
                $row['sla']['label'] ?? null,
                $row['sla']['delta_human'] ?? null,
            ],
            'ticket_work' => [
                $row['ticket']['number'] ?? null,
                $row['ticket']['title'] ?? null,
                $row['ticket']['status'] ?? null,
                $row['ticket']['assignee'] ?? null,
                $row['ticket']['deadline']['display'] ?? null,
                $row['ticket']['completed_at']['display'] ?? null,
                $row['ticket']['sla']['label'] ?? null,
                sprintf('%d/%d', ($row['tasks']['stats']['total'] ?? 0) - ($row['tasks']['stats']['missing'] ?? 0), $row['tasks']['stats']['total'] ?? 0),
                $row['project']['sla']['label'] ?? '—',
            ],
            default => [
                $row['number'] ?? null,
                $row['title'] ?? null,
                $row['status'] ?? null,
                $row['priority'] ?? null,
                $row['assignee'] ?? null,
                $row['deadline']['display'] ?? null,
                $row['completed_at']['display'] ?? null,
                $row['sla']['label'] ?? null,
                $row['sla']['delta_human'] ?? null,
            ],
        };
    }

    /** @return array<int,array{value:string,label:string}> */
    private function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => 'Semua status'],
            ['value' => 'met', 'label' => 'Tercapai'],
            ['value' => 'pending', 'label' => 'Dalam Proses'],
            ['value' => 'breached', 'label' => 'Lewat SLA'],
            ['value' => 'missing', 'label' => 'SLA tidak diset'],
        ];
    }

    private function downloadParams(array $filters): array
    {
        return array_filter([
            'type' => $filters['type'] ?? null,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'sla_status' => $filters['sla_status'] ?? null,
            'q' => $filters['q'] ?? null,
        ], fn ($value) => filled($value));
    }

    private function resolvePerPage(int $perPage): int
    {
        if ($perPage < 1) {
            return 15;
        }

        return min($perPage, 100);
    }

    private function formatRangeFilter(?string $from, ?string $to): string
    {
        $parse = function (?string $value, bool $endOfDay = false): ?Carbon {
            if (! $value) {
                return null;
            }

            try {
                $dt = Carbon::parse($value);

                return $endOfDay ? $dt->endOfDay() : $dt->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        };

        $fromDate = $parse($from, false);
        $toDate = $parse($to, true);

        if (! $fromDate && ! $toDate) {
            return 'Semua';
        }

        $format = 'd M Y';
        $tz = config('app.timezone');

        if ($fromDate && $toDate) {
            return $fromDate->timezone($tz)->format($format).' - '.$toDate->timezone($tz)->format($format);
        }

        if ($fromDate) {
            return 'Mulai '.$fromDate->timezone($tz)->format($format);
        }

        return 'Sampai '.$toDate->timezone($tz)->format($format);
    }

    private function resolveSlaStatusLabel(?string $value): string
    {
        $options = array_column($this->statusOptions(), 'label', 'value');

        if ($value === null || $value === '' || ! array_key_exists($value, $options)) {
            return 'Semua';
        }

        return $options[$value];
    }
}
