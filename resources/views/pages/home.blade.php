@extends('layouts.app')

@section('title', 'Home')

@section('content')
  <div class="text-center welcome-text">
    <h1 style="max-width: 360px; margin: 0 auto 24px;"><x-brand-logo /></h1>
    <p class="lead">This is the homepage of your Laravel project using Bootstrap Materialize template.</p>

    @auth
      <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
    @else
      <a href="{{ route('login', ['locale' => app()->getLocale() ?? config('app.locale', 'en')]) }}" class="btn btn-outline-primary">Login</a>
    @endauth
  </div>
@endsection
