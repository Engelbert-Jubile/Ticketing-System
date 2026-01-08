@extends('errors.layout')

@section('title', '401 - Tidak Diizinkan')
@section('code', '401')
@section('message', 'Silakan masuk untuk mengakses halaman ini.')
@section('helper', 'Masuk terlebih dahulu untuk melanjutkan sesi Anda.')
@section('button_label', 'Masuk')
@section('button_url', route('login', ['locale' => app()->getLocale() ?? config('app.locale', 'en')]))
@section('art', 'images/error-abstract-graph.svg')
