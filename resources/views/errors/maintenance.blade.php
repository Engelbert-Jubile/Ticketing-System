<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 0; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
    .card { max-width: 560px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; box-shadow: 0 20px 50px -25px rgba(15,23,42,0.35); }
    h1 { margin: 0 0 12px; font-size: 26px; }
    p { margin: 0 0 10px; line-height: 1.5; }
    .muted { color: #475569; font-size: 14px; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 12px; border: 1px solid #1d4ed8; color: #1d4ed8; font-weight: 700; text-decoration: none; background: #eff6ff; box-shadow: 0 10px 25px -18px rgba(37,99,235,0.8); }
    .btn:hover { background: #dbeafe; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Maintenance</h1>
        <p>The system is temporarily unavailable due to maintenance.</p>
        <p class="muted">Please try again later.</p>
        @php
            $locale = app()->getLocale() ?? config('app.locale', 'en');
        @endphp
        <div style="margin-top: 16px;">
            <a class="btn" href="{{ route('welcome', ['locale' => $locale]) }}">
                <span aria-hidden="true">↩</span>
                Back
            </a>
        </div>
    </div>
</body>
</html>
