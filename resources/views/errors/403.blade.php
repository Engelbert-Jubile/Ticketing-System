{{-- resources/views/errors/403.blade.php --}}
@extends('errors.layout')  {{-- pakai layout kustom kita di bawah --}}

@php
  $locale = app()->getLocale() ?? config('app.locale', 'en');
  $targetUrl = auth()->check()
    ? route('dashboard', ['locale' => $locale])
    : route('login', ['locale' => $locale]);
@endphp

@section('title', '403 - Unauthorized')
@section('code', '403')
@section('message', 'Anda tidak memiliki hak akses untuk halaman ini. Silakan hubungi admin jika menurut Anda ini sebuah kekeliruan.')

{{-- opsional: label & URL tombol --}}
@section('button_label', 'Kembali')
@section('button_url', $targetUrl)
