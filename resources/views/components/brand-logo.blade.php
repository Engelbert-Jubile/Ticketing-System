@props(['tagline' => true, 'stacked' => false, 'singleLine' => false])
@php
    $logo = $stacked ? \App\Support\Branding::STACKED_LOGO : \App\Support\Branding::LOGO;
@endphp
<span {{ $attributes->class(['tickme-brand']) }} style="display: inline-flex; width: 100%; flex-direction: column; align-items: center; gap: 12px;">
    <img src="{{ asset($logo) }}?v={{ filemtime(public_path($logo)) }}" alt="{{ config('app.name') }}" width="{{ $stacked ? 1254 : 1600 }}" height="{{ $stacked ? 1254 : 420 }}" style="display: block; width: 100%; height: auto; border-radius: 12px;" />
    @if($tagline)
        <span style="display: block; max-width: {{ $singleLine ? 'none' : '36rem' }}; font-size: {{ $singleLine ? 'clamp(7px, 2vw, 14px)' : '14px' }}; white-space: {{ $singleLine ? 'nowrap' : 'normal' }}; font-weight: 500; line-height: 1.6; letter-spacing: normal; text-transform: none; color: #123b7a; text-align: center;">{{ config('app.tagline') }}</span>
    @endif
</span>
