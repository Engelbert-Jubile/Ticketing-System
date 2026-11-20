<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} - {{ \App\Support\Branding::normalizeName(config('app.name')) }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 16px; color: #1e3a8a; }
        h2 { font-size: 14px; margin: 18px 0 10px; color: #1e293b; text-transform: uppercase; page-break-after: avoid; }
        p { margin: 0 0 6px; }
        .label { font-size: 10px; font-weight: bold; color: #475569; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        th, td { border: 1px solid #cbd5f5; padding: 8px; text-align: left; vertical-align: top; overflow-wrap: break-word; }
        thead { display: table-header-group; background: #e2e8f0; }
        tr { page-break-inside: avoid; }
        th { font-size: 10px; color: #1e293b; text-transform: uppercase; }
        tbody tr:nth-child(odd) { background: #f8fafc; }
        .summary td { width: 50%; background: #f8fafc; }
        .muted { color: #64748b; font-size: 10px; }
        .badge { display: inline-block; padding: 3px 7px; background: #dbeafe; color: #1d4ed8; }
        .description { border: 1px solid #cbd5f5; padding: 12px; line-height: 1.5; overflow-wrap: break-word; }
        .description img { max-width: 100%; height: auto; }
        ul { margin: 4px 0 10px 18px; padding: 0; }
        li { margin: 0 0 6px; }
    </style>
</head>
<body>
    @include('reports.pdf.brand')
    <h1>{{ $title }}</h1>

    <table class="summary">
        <tr>
            <td><p class="label">{{ __('ticket_report.number') }}</p>{{ $ticket['ticket_no'] ?? '—' }}</td>
            <td><p class="label">{{ __('ticket_report.status') }}</p><span class="badge">{{ $ticket['status_label'] ?? '—' }}</span></td>
        </tr>
        <tr>
            <td><p class="label">{{ __('ticket_report.priority') }}</p>{{ $labels['priority'] ?? '—' }}</td>
            <td><p class="label">{{ __('ticket_report.type') }}</p>{{ $labels['type'] ?? '—' }}</td>
        </tr>
        <tr>
            <td>
                <p class="label">{{ __('ticket_report.requester') }}</p>{{ $ticket['requester']['name'] ?? '—' }}
                @if(!empty($ticket['requester']['email']))<p class="muted">{{ $ticket['requester']['email'] }}</p>@endif
            </td>
            <td><p class="label">SLA</p>{{ $labels['sla'] ?? '—' }}</td>
        </tr>
        <tr>
            <td><p class="label">{{ __('ticket_report.created') }}</p>{{ $timeline['created_at'] ?? '—' }}</td>
            <td><p class="label">{{ __('ticket_report.updated') }}</p>{{ $timeline['updated_at'] ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2"><p class="label">{{ __('ticket_report.due') }}</p>{{ $timeline['due_at'] ?? __('ticket_report.not_specified') }}</td>
        </tr>
        @if(($ticket['status'] ?? null) === 'done')
            <tr><td colspan="2"><p class="label">{{ __('ticket_report.completed') }}</p>{{ $timeline['finish_at'] ?? __('ticket_report.not_specified') }}</td></tr>
        @endif
    </table>

    <h2>{{ __('ticket_report.description') }}</h2>
    <div class="description">{!! $description !!}</div>

    @if(!empty($assignedUsers))
        <h2>{{ __('ticket_report.assigned_team') }}</h2>
        <ul>
            @foreach($assignedUsers as $user)
                <li>{{ $user['name'] ?? '—' }} @if(!empty($user['email']))<span class="muted">({{ $user['email'] }})</span>@endif</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($project))
        <h2>{{ __('ticket_report.project') }}</h2>
        <table class="summary">
            <tr>
                <td><p class="label">{{ __('ticket_report.project_title') }}</p>{{ $project['title'] ?? '—' }}</td>
                <td><p class="label">{{ __('ticket_report.project_number') }}</p>{{ $project['project_no'] ?? '—' }}</td>
            </tr>
            <tr>
                <td><p class="label">{{ __('ticket_report.status') }}</p>{{ $project['status'] ?? '—' }}</td>
                <td><p class="label">{{ __('ticket_report.timeline') }}</p>{{ $project['timeline'] ?: __('ticket_report.not_specified') }}</td>
            </tr>
        </table>
    @endif

    @if(!empty($tasks))
        <h2>{{ __('ticket_report.tasks') }}</h2>
        <table>
            <thead><tr>
                <th style="width: 34%;">{{ __('ticket_report.task_title') }}</th>
                <th style="width: 16%;">{{ __('ticket_report.status') }}</th>
                <th style="width: 28%;">{{ __('ticket_report.assignee') }}</th>
                <th style="width: 22%;">{{ __('ticket_report.due') }}</th>
            </tr></thead>
            <tbody>
                @foreach($tasks as $item)
                    <tr>
                        <td>{{ $item['title'] ?? '—' }}</td>
                        <td>{{ $item['status_label'] ?? '—' }}</td>
                        <td>{{ $item['assignee'] ?? __('ticket_report.unassigned') }}</td>
                        <td>{{ $item['due_at'] ?? __('ticket_report.not_specified') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($attachments))
        <h2>{{ __('ticket_report.attachments') }}</h2>
        <table>
            <thead><tr><th style="width: 78%;">{{ __('ticket_report.file_name') }}</th><th style="width: 22%;">{{ __('ticket_report.size') }}</th></tr></thead>
            <tbody>
                @foreach($attachments as $attachment)
                    <tr><td>{{ $attachment['name'] ?? '—' }}</td><td>{{ $attachment['size'] ?? '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="muted" style="margin-top: 22px;">{{ __('ticket_report.generated', ['date' => now()->timezone(config('app.timezone'))->locale(app()->getLocale())->translatedFormat('d M Y H:i')]) }}</p>
</body>
</html>
