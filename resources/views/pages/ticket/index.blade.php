{{-- LEGACY: migrated to Inertia resources/js/Pages/Tickets/Index.vue --}}
@extends('layouts.app')

@section('title', 'Semua Tiket')

@section('content')
<div class="page-shell page-shell--wide py-6 space-y-6">
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-start gap-3">
      <div class="shrink-0">
        @include('components.back', ['to' => route('dashboard')])
      </div>
      <div>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Ticket Reports</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Kelola tiket, pantau progres, dan tindak lanjuti permintaan dengan cepat.</p>
      </div>
    </div>
    <a href="{{ route('tickets.create', ['from'=>request()->fullUrl()]) }}"
      class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:shadow-xl">
      <span class="material-icons text-[18px]">add</span>
      Buat Tiket
    </a>
  </div>

  <div class="rounded-3xl border border-slate-200/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-slate-700/60 dark:bg-slate-900/70 sm:p-6">
    @include('pages.ticket._table', ['tickets'=>$tickets, 'statusLabelMap'=>$statusLabelMap])
  </div>
</div>
@endsection
