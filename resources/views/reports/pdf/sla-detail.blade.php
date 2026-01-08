<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 21px; margin: 0 0 16px 0; color: #1e3a8a; }
        h2 { font-size: 15px; margin: 20px 0 10px 0; text-transform: uppercase; letter-spacing: 0.03em; color: #1e293b; }
        p { margin: 0 0 8px 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px 18px; margin-bottom: 12px; }
        .card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; background: #f8fafc; }
        .label { font-size: 10px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .value { font-size: 12px; color: #0f172a; }
        .muted { color: #64748b; font-size: 11px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #dbeafe; color: #1d4ed8; text-transform: uppercase; }
        .description { border: 1px solid #cbd5f5; border-radius: 10px; padding: 14px 16px; background: #fff; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5f5; padding: 8px 10px; text-align: left; vertical-align: top; }
        thead { background: #e2e8f0; }
        th { font-size: 11px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.03em; }
        tbody tr:nth-child(odd) { background: #f8fafc; }
        ul { margin: 6px 0 12px 18px; padding: 0; }
        li { margin-bottom: 6px; }
    </style>
</head>
<body>
@php
    $primary = $summary;
    if ($type === 'ticket_work') {
        $primary = $summary['ticket'] ?? [];
    }
    $sla = $primary['sla'] ?? null;
@endphp

    <h1>{{ $title }}</h1>

    <div class="grid">
        <div class="card">
            <p class="label">Nomor</p>
            <p class="value">{{ $primary['number'] ?? '—' }}</p>
        </div>
        <div class="card">
            <p class="label">Judul</p>
            <p class="value">{{ $primary['title'] ?? '—' }}</p>
        </div>
        <div class="card">
            <p class="label">Status</p>
            <p class="value"><span class="badge">{{ $primary['status'] ?? '—' }}</span></p>
        </div>
        <div class="card">
            <p class="label">Durasi</p>
            <p class="value">{{ $primary['duration'] ?? '—' }}</p>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <p class="label">Deadline</p>
            <p class="value">{{ data_get($primary, 'deadline.display', '—') }}</p>
        </div>
        <div class="card">
            <p class="label">Selesai</p>
            <p class="value">{{ data_get($primary, 'completed_at.display', '—') }}</p>
        </div>
        <div class="card">
            <p class="label">SLA</p>
            <p class="value">
                {{ $sla['label'] ?? '—' }}
                @if(!empty($sla['delta_human']))
                    <span class="muted">({{ $sla['delta_human'] }})</span>
                @endif
            </p>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <p class="label">Target SLA</p>
            <p class="value">{{ data_get($sla, 'target.display', '—') }}</p>
        </div>
        <div class="card">
            <p class="label">Realisasi</p>
            <p class="value">{{ data_get($sla, 'actual.display', '—') }}</p>
        </div>
    </div>

    @if($type === 'ticket')
        <div class="grid">
            <div class="card">
                <p class="label">Requester</p>
                <p class="value">{{ $detail['requester'] ?? '—' }}</p>
            </div>
            <div class="card">
                <p class="label">Assignee</p>
                <p class="value">{{ $detail['assignee'] ?? ($primary['assignee'] ?? '—') }}</p>
            </div>
            <div class="card">
                <p class="label">Prioritas</p>
                <p class="value">{{ $primary['priority'] ?? '—' }}</p>
            </div>
        </div>
    @endif

    @if(!empty($description))
        <h2>Deskripsi</h2>
        <div class="description">{!! $description !!}</div>
    @endif

    @if($type === 'ticket')
        @if(!empty($detail['assigned']))
            <h2>Tim Terlibat</h2>
            <ul>
                @foreach($detail['assigned'] as $member)
                    <li>{{ $member }}</li>
                @endforeach
            </ul>
        @endif

        @if(!empty($detail['project']))
            <h2>Project Terkait</h2>
            <div class="grid">
                <div class="card">
                    <p class="label">Project</p>
                    <p class="value">{{ $detail['project']['number'] ?? '—' }} · {{ $detail['project']['title'] ?? '—' }}</p>
                </div>
                <div class="card">
                    <p class="label">Status</p>
                    <p class="value">{{ $detail['project']['status'] ?? '—' }}</p>
                </div>
            </div>
        @endif

        @if(!empty($detail['tasks']))
            <h2>Daftar Task</h2>
            <table>
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Status</th>
                        <th>Assignee</th>
                        <th>Deadline</th>
                        <th>SLA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detail['tasks'] as $task)
                        <tr>
                            <td>{{ $task['number'] ?? '—' }}</td>
                            <td>{{ $task['status'] ?? '—' }}</td>
                            <td>{{ $task['assignee'] ?? '—' }}</td>
                            <td>{{ data_get($task, 'deadline.display', '—') }}</td>
                            <td>{{ $task['sla']['label'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    @if($type === 'task')
        <h2>Relasi</h2>
        <div class="grid">
            <div class="card">
                <p class="label">Ticket</p>
                <p class="value">{{ $detail['ticket']['number'] ?? '—' }} · {{ $detail['ticket']['title'] ?? '—' }}</p>
            </div>
            <div class="card">
                <p class="label">Project</p>
                <p class="value">{{ $detail['project']['number'] ?? '—' }} · {{ $detail['project']['title'] ?? '—' }}</p>
            </div>
            <div class="card">
                <p class="label">Assignee</p>
                <p class="value">{{ $summary['assignee'] ?? '—' }}</p>
            </div>
        </div>
    @endif

    @if($type === 'project')
        <h2>Ticket Terkait</h2>
        <div class="card">
            <p class="label">Ticket</p>
            <p class="value">{{ $detail['ticket']['number'] ?? '—' }} · {{ $detail['ticket']['title'] ?? '—' }}</p>
            <p class="muted">SLA Ticket: {{ $detail['ticket']['sla']['label'] ?? '—' }}</p>
        </div>
    @endif

    @if($type === 'ticket_work')
        @php
            $ticket = $summary['ticket'] ?? [];
            $tasks = $summary['tasks']['items'] ?? [];
            $project = $summary['project'] ?? null;
        @endphp
        <h2>Detail Ticket</h2>
        <div class="grid">
            <div class="card">
                <p class="label">Nomor</p>
                <p class="value">{{ $ticket['number'] ?? '—' }}</p>
            </div>
            <div class="card">
                <p class="label">Assignee</p>
                <p class="value">{{ $ticket['assignee'] ?? '—' }}</p>
            </div>
        </div>

        @if(!empty($tasks))
            <h2>Task Terkait</h2>
            <table>
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Status</th>
                        <th>Assignee</th>
                        <th>Deadline</th>
                        <th>SLA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                        <tr>
                            <td>{{ $task['number'] ?? '—' }}</td>
                            <td>{{ $task['status'] ?? '—' }}</td>
                            <td>{{ $task['assignee'] ?? '—' }}</td>
                            <td>{{ data_get($task, 'deadline.display', '—') }}</td>
                            <td>{{ $task['sla']['label'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(!empty($project))
            <h2>Project Terkait</h2>
            <div class="grid">
                <div class="card">
                    <p class="label">Project</p>
                    <p class="value">{{ $project['number'] ?? '—' }} · {{ $project['title'] ?? '—' }}</p>
                </div>
                <div class="card">
                    <p class="label">SLA Project</p>
                    <p class="value">{{ $project['sla']['label'] ?? '—' }}</p>
                </div>
            </div>
        @endif
    @endif

    <p class="muted" style="margin-top: 20px;">Dokumen ini digenerasi otomatis pada {{ now()->timezone(config('app.timezone'))->format('d M Y H:i') }}.</p>
</body>
</html>
