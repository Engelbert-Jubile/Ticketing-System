<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <style>
        @page { margin: 24px 28px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #0f172a;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 12px 0;
            color: #1e3a8a;
        }
        .meta {
            margin-bottom: 16px;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }
        .meta dl {
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 6px 18px;
        }
        .meta dt {
            font-weight: 600;
            color: #1e293b;
        }
        .meta dd {
            margin: 0;
            color: #475569;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        thead {
            background: #e2e8f0;
        }
        th, td {
            padding: 8px 10px;
            border: 1px solid #cbd5f5;
            text-align: left;
            vertical-align: top;
        }
        th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #1e293b;
        }
        tbody tr:nth-child(odd) {
            background: #f8fafc;
        }
        .small {
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    <div class="meta">
        <dl>
            <div>
                <dt>Dicetak</dt>
                <dd>{{ now()->timezone(config('app.timezone'))->format('d M Y, H:i') }}</dd>
            </div>
            @if(!empty($meta['filters']) && is_array($meta['filters']))
                @foreach($meta['filters'] as $label => $value)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                    </div>
                @endforeach
            @endif
        </dl>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell ?? '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align: center; padding: 24px 12px;">
                        Tidak ada data untuk filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="small">Laporan ini dihasilkan otomatis oleh {{ config('app.name') }}.</p>
</body>
</html>
