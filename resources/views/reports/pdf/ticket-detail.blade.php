<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 22px; margin: 0 0 16px 0; color: #1e3a8a; }
        h2 { font-size: 15px; margin: 18px 0 10px 0; color: #1e293b; letter-spacing: 0.02em; text-transform: uppercase; }
        p { margin: 0 0 8px 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px 18px; margin-bottom: 12px; }
        .card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; background: #f8fafc; }
        .label { font-size: 11px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
        .value { font-size: 12px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #cbd5f5; padding: 8px 10px; text-align: left; vertical-align: top; }
        thead { background: #e2e8f0; }
        th { font-size: 11px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.03em; }
        tbody tr:nth-child(odd) { background: #f8fafc; }
        .muted { color: #64748b; font-size: 11px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #dbeafe; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.05em; }
        .section { margin-top: 20px; }
        .description { border: 1px solid #cbd5f5; border-radius: 10px; padding: 14px 16px; background: #fff; line-height: 1.5; }
        ul { margin: 4px 0 10px 18px; padding: 0; }
        li { margin: 0 0 6px 0; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    <div class="grid">
        <div class="card">
            <p class="label">Nomor Ticket</p>
            <p class="value">{{ $ticket['ticket_no'] ?? '—' }}</p>
        </div>
        <div class="card">
            <p class="label">Status</p>
            <p class="value"><span class="badge">{{ $ticket['status_label'] ?? 'Tidak diketahui' }}</span></p>
        </div>
        <div class="card">
            <p class="label">Prioritas</p>
            <p class="value">{{ ucfirst($ticket['priority'] ?? '—') }}</p>
        </div>
        <div class="card">
            <p class="label">Jenis</p>
            <p class="value">{{ ucfirst($ticket['type'] ?? '—') }}</p>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <p class="label">Requester</p>
            <p class="value">{{ $ticket['requester']['name'] ?? '—' }}</p>
            @if(!empty($ticket['requester']['email']))
                <p class="muted">{{ $ticket['requester']['email'] }}</p>
            @endif
        </div>
        <div class="card">
            <p class="label">Agent</p>
            <p class="value">{{ $ticket['agent']['name'] ?? '—' }}</p>
            @if(!empty($ticket['agent']['email']))
                <p class="muted">{{ $ticket['agent']['email'] }}</p>
            @endif
        </div>
        <div class="card">
            <p class="label">SLA</p>
            <p class="value">{{ ucfirst($ticket['sla'] ?? '—') }}</p>
        </div>
        <div class="card">
            <p class="label">Terakhir Diperbarui</p>
            <p class="value">{{ $timeline['updated_at'] ?? '—' }}</p>
        </div>
    </div>

    <div class="card">
        <div class="grid" style="margin-bottom: 0;">
            <div>
                <p class="label">Dibuat</p>
                <p class="value">{{ $timeline['created_at'] ?? '—' }}</p>
            </div>
            <div>
                <p class="label">Batas Waktu</p>
                <p class="value">{{ $timeline['due_at'] ?? '—' }}</p>
            </div>
            <div>
                <p class="label">Selesai</p>
                <p class="value">{{ $timeline['finish_at'] ?? '—' }}</p>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Deskripsi</h2>
        <div class="description">{!! $description !!}</div>
    </div>

    @if(!empty($assignedUsers))
        <div class="section">
            <h2>Tim yang Ditugaskan</h2>
            <ul>
                @foreach($assignedUsers as $user)
                    <li>{{ $user['name'] ?? '—' }} @if(!empty($user['email']))<span class="muted">({{ $user['email'] }})</span>@endif</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!empty($project))
        <div class="section">
            <h2>Project Terkait</h2>
            <div class="card">
                <p class="label">Judul Project</p>
                <p class="value">{{ $project['title'] ?? '—' }}</p>
                <p class="label">Nomor Project</p>
                <p class="value">{{ $project['project_no'] ?? '—' }}</p>
                <p class="label">Status</p>
                <p class="value">{{ $project['status'] ?? '—' }}</p>
                <p class="label">Timeline</p>
                <p class="value">{{ $project['timeline'] ?? '—' }}</p>
            </div>
        </div>
    @endif

    @if(!empty($tasks))
        <div class="section">
            <h2>Daftar Task</h2>
            <table>
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Status</th>
                        <th>Penanggung Jawab</th>
                        <th>Due</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $item)
                        <tr>
                            <td>{{ $item['title'] ?? '—' }}</td>
                            <td>{{ $item['status_label'] ?? '—' }}</td>
                            <td>{{ $item['assignee'] ?? '—' }}</td>
                            <td>{{ $item['due_at'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($attachments))
        <div class="section">
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
        </div>
    @endif

    <p class="muted" style="margin-top: 22px;">Dokumen ini dibuat otomatis pada {{ now()->timezone(config('app.timezone'))->format('d M Y H:i') }}.</p>
</body>
</html>
