<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 14px 0; color: #1e3a8a; }
        h2 { font-size: 15px; margin: 18px 0 10px 0; text-transform: uppercase; color: #1e293b; letter-spacing: 0.03em; }
        p { margin: 0 0 8px 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px 18px; margin-bottom: 12px; }
        .card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; background: #f8fafc; }
        .label { font-size: 10px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .value { font-size: 12px; color: #0f172a; }
        .muted { color: #64748b; font-size: 11px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #dbeafe; color: #1d4ed8; text-transform: uppercase; }
        .description { border: 1px solid #cbd5f5; border-radius: 10px; padding: 14px 16px; background: #fff; line-height: 1.5; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5f5; padding: 8px 10px; text-align: left; vertical-align: top; }
        thead { background: #e2e8f0; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; color: #1e293b; }
        tbody tr:nth-child(odd) { background: #f8fafc; }
        ul { margin: 4px 0 10px 18px; padding: 0; }
        li { margin-bottom: 6px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    <div class="grid">
        <div class="card">
            <p class="label">Nomor Task</p>
            <p class="value">{{ $task['task_no'] ?? '—' }}</p>
        </div>
        <div class="card">
            <p class="label">Status</p>
            <p class="value"><span class="badge">{{ $task['status_label'] ?? 'Tidak diketahui' }}</span></p>
        </div>
        <div class="card">
            <p class="label">Prioritas</p>
            <p class="value">{{ $task['priority_label'] ?? ucfirst($task['priority'] ?? '—') }}</p>
        </div>
        <div class="card">
            <p class="label">Due Date</p>
            <p class="value">{{ $timeline['due'] ?? '—' }}</p>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <p class="label">Assignee</p>
            <p class="value">{{ $task['assignee']['name'] ?? '—' }}</p>
            @if(!empty($task['assignee']['email']))
                <p class="muted">{{ $task['assignee']['email'] }}</p>
            @endif
        </div>
        <div class="card">
            <p class="label">Requester</p>
            <p class="value">{{ $task['requester']['name'] ?? '—' }}</p>
            @if(!empty($task['requester']['email']))
                <p class="muted">{{ $task['requester']['email'] }}</p>
            @endif
        </div>
        <div class="card">
            <p class="label">Ticket Terkait</p>
            <p class="value">{{ $task['ticket']['title'] ?? '—' }}</p>
            @if(!empty($task['ticket']['ticket_no']))
                <p class="muted">{{ $task['ticket']['ticket_no'] }}</p>
            @endif
        </div>
        <div class="card">
            <p class="label">Project Terkait</p>
            <p class="value">{{ $task['project']['title'] ?? '—' }}</p>
        </div>
    </div>

    <div class="card">
        <div class="grid" style="margin-bottom: 0;">
            <div>
                <p class="label">Mulai</p>
                <p class="value">{{ $timeline['start'] ?? '—' }}</p>
            </div>
            <div>
                <p class="label">Jatuh Tempo</p>
                <p class="value">{{ $timeline['due'] ?? '—' }}</p>
            </div>
            <div>
                <p class="label">Selesai</p>
                <p class="value">{{ $timeline['end'] ?? '—' }}</p>
            </div>
        </div>
    </div>

    <h2>Deskripsi</h2>
    <div class="description">{!! $description !!}</div>

    @if(!empty($assigned))
        <h2>Anggota Terlibat</h2>
        <ul>
            @foreach($assigned as $member)
                <li>{{ $member['name'] ?? '—' }} @if(!empty($member['email']))<span class="muted">({{ $member['email'] }})</span>@endif</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($attachments))
        <h2>Lampiran</h2>
        <table>
            <thead>
                <tr>
                    <th>Nama File</th>
                    <th>Ukuran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attachments as $attachment)
                    <tr>
                        <td>{{ $attachment['name'] ?? '—' }}</td>
                        <td>{{ $attachment['size'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="muted" style="margin-top: 20px;">Dokumen dibuat otomatis pada {{ now()->timezone(config('app.timezone'))->format('d M Y H:i') }}.</p>
</body>
</html>
