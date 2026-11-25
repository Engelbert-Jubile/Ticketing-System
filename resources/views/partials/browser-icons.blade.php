@php
    $shortcutIcon = 'branding/tickme-shortcut.png';
    $shortcutIconUrl = asset($shortcutIcon).'?v='.filemtime(public_path($shortcutIcon));
@endphp
<link rel="icon" type="image/x-icon" sizes="16x16 32x32 48x48 64x64 128x128 256x256" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ $shortcutIconUrl }}">
<link rel="icon" type="image/svg+xml" sizes="any" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}">
<link rel="apple-touch-icon" sizes="192x192" href="{{ $shortcutIconUrl }}">
