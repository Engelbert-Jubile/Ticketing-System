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
    <h1>{{ $title }}</h1>

    <div class="grid">
        <div class="card">
            <p class="label">Nomor Project</p>
            <p class="value">{{ $project['project_no'] ?? '—' }}</p>
        </div>
        <div class="card">
            <p class="label">Status</p>
            <p class="value"><span class="badge">{{ $project['status_label'] ?? 'Tidak diketahui' }}</span></p>
        </div>
        <div class="card">
            <p class="label">Mulai</p>
            <p class="value">{{ $timeline['start'] ?? '—' }}</p>
        </div>
        <div class="card">
            <p class="label">Selesai</p>
            <p class="value">{{ $timeline['end'] ?? '—' }}</p>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <p class="label">Dibuat</p>
            <p class="value">{{ $timeline['created'] ?? '—' }}</p>
        </div>
        <div class="card">
            <p class="label">Diperbarui</p>
            <p class="value">{{ $timeline['updated'] ?? '—' }}</p>
        </div>
    </div>

    <h2>Deskripsi</h2>
    <div class="description">{!! $description !!}</div>

    @if(!empty($ticket))
        <h2>Informasi Ticket</h2>
        <div class="grid">
            <div class="card">
                <p class="label">Nomor Ticket</p>
                <p class="value">{{ $ticket['ticket_no'] ?? '—' }}</p>
            </div>
            <div class="card">
                <p class="label">Status Ticket</p>
                <p class="value">{{ $ticket['status_label'] ?? '—' }}</p>
            </div>
            <div class="card">
                <p class="label">Assignee</p>
                <p class="value">{{ $ticket['assignee']['name'] ?? '—' }}</p>
            </div>
            <div class="card">
                <p class="label">Requester</p>
                <p class="value">{{ $ticket['requester']['name'] ?? '—' }}</p>
            </div>
        </div>
        @if(!empty($ticket['assigned']))
            <div class="card">
                <p class="label">Assigned Users</p>
                <ul>
                    @foreach($ticket['assigned'] as $member)
                        <li>{{ $member['name'] ?? '—' }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    @if(!empty($pics))
        <h2>Person In Charge</h2>
        <ul>
            @foreach($pics as $pic)
                <li>{{ $pic['name'] ?? '—' }} @if(!empty($pic['position']))<span class="muted">— {{ $pic['position'] }}</span>@endif</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($actions))
        <h2>Daftar Actions</h2>
        <table>
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Start</th>
                    <th>End</th>
                </tr>
            </thead>
            <tbody>
                @foreach($actions as $action)
                    <tr>
                        <td>
                            {{ $action['title'] ?? '—' }}
                            @if(!empty($action['subactions']))
                                <ul>
                                    @foreach($action['subactions'] as $sub)
                                        <li>{{ $sub['title'] ?? '—' }} ({{ $sub['status_id'] ?? '—' }}, {{ $sub['progress'] ?? '0' }}%)</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td>{{ $action['status_id'] ?? '—' }}</td>
                        <td>{{ $action['progress'] !== null ? $action['progress'].'%' : '—' }}</td>
                        <td>{{ $action['start'] ?? '—' }}</td>
                        <td>{{ $action['end'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($deliverables))
        <h2>Deliverables</h2>
        <table>
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliverables as $deliverable)
                    <tr>
                        <td>{{ $deliverable['title'] ?? '—' }}</td>
                        <td>{{ $deliverable['status_id'] ?? '—' }}</td>
                        <td>{{ $deliverable['due'] ?? '—' }}</td>
                        <td>{{ $deliverable['description'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($costs))
        <h2>Biaya</h2>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Kategori</th>
                    <th>Estimasi</th>
                    <th>Realisasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($costs as $cost)
                    <tr>
                        <td>{{ $cost['item'] ?? '—' }}</td>
                        <td>{{ $cost['category'] ?? '—' }}</td>
                        <td>{{ $cost['estimated'] ?? '—' }}</td>
                        <td>{{ $cost['actual'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($risks))
        <h2>Risiko</h2>
        <table>
            <thead>
                <tr>
                    <th>Risiko</th>
                    <th>Status</th>
                    <th>Dampak</th>
                    <th>Kemungkinan</th>
                    <th>Mitigasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($risks as $risk)
                    <tr>
                        <td>{{ $risk['name'] ?? '—' }}</td>
                        <td>{{ $risk['status_id'] ?? '—' }}</td>
                        <td>{{ $risk['impact'] ?? '—' }}</td>
                        <td>{{ $risk['likelihood'] ?? '—' }}</td>
                        <td>{{ $risk['mitigation'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($attachments))
        <h2>Lampiran Project</h2>
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
