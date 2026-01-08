@extends('layouts.app')
@section('title','Dashboard')

@push('styles')
<style>
  .fade-in {
    opacity: 1;
    transform: none;
    animation: fadeInUp .4s var(--ease-smooth, cubic-bezier(.22, .61, .36, 1)) forwards
  }

  @keyframes fadeInUp {
    to {
      opacity: 1;
      transform: translateY(0)
    }
  }

  .chart-card {
    position: relative;
    overflow: hidden;
    transform: translateZ(0);
    will-change: transform;
    isolation: isolate
  }

  .chart-card canvas {
    display: block;
    max-width: 100%;
    transform: translateZ(0);
    backface-visibility: hidden
  }

  .meter {
    height: .6rem;
    border-radius: .5rem;
    background: rgba(148, 163, 184, .2);
    overflow: hidden
  }

  .meter>i {
    display: block;
    height: 100%;
    border-radius: .5rem;
    width: var(--w, 0%);
    background: linear-gradient(90deg, #22c55e, #16a34a);
    box-shadow: 0 1px 3px rgba(16, 185, 129, .35) inset
  }

  .meter.warning>i {
    background: linear-gradient(90deg, #f59e0b, #d97706);
    box-shadow: 0 1px 3px rgba(245, 158, 11, .35) inset
  }

  .meter.danger>i {
    background: linear-gradient(90deg, #ef4444, #b91c1c);
    box-shadow: 0 1px 3px rgba(239, 68, 68, .35) inset
  }

  .kpi-cap {
    font-size: 11px;
    color: #64748b
  }

  .dark .meter {
    background: rgba(148, 163, 184, .25)
  }

  .card {
    border-radius: 1rem;
    background: var(--card-bg, #fff);
    box-shadow: 0 1px 2px rgba(0, 0, 0, .06);
    padding: 1rem
  }

  .dark .card {
    --card-bg: #1f2937;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .35)
  }

  /* ===== Quick Actions (klik-able) ===== */
  .qa {
    position: relative;
    border-radius: 1rem;
    border: 1px solid var(--qa-border, #e5e7eb);
    transition: background-color .18s, border-color .18s, box-shadow .2s, transform .18s;
    isolation: isolate;
    will-change: transform
  }

  .dark .qa {
    border-color: rgba(255, 255, 255, .06)
  }

  /* Overlay tipis saat hover/focus */
  .qa::after {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: var(--qa-hover-bg, transparent);
    opacity: 0;
    transition: opacity .18s ease;
    pointer-events: none;
    z-index: 0
  }

  .qa:hover::after,
  .qa:focus-visible::after {
    opacity: 1
  }

  .qa>* {
    position: relative;
    z-index: 1
  }

  /* Ring fokus untuk keyboard */
  .qa:focus-visible {
    outline: 3px solid var(--qa-ring, rgba(59, 130, 246, .28));
    outline-offset: 2px
  }

  /* aksesibel :contentReference[oaicite:0]{index=0} */

  /* Motion lembut */
  .qa:hover {
    box-shadow: 0 4px 22px -12px rgba(0, 0, 0, .45);
    transform: translateY(-1px)
  }

  .qa:active {
    transform: translateY(0)
  }

  @media (prefers-reduced-motion: reduce) {

    /* hormati preferensi user :contentReference[oaicite:1]{index=1} */
    .qa {
      transition: background-color .18s, border-color .18s
    }

    .qa:hover,
    .qa:active {
      transform: none;
      box-shadow: none
    }
  }

  /* Variasi warna (light & dark) � tipis, elegan */
  .qa-blue {
    --qa-border: rgba(59, 130, 246, .25);
    --qa-hover-bg: rgba(59, 130, 246, .08);
    --qa-ring: rgba(59, 130, 246, .30);
  }

  .dark .qa-blue {
    --qa-border: rgba(59, 130, 246, .30);
    --qa-hover-bg: rgba(59, 130, 246, .14);
    --qa-ring: rgba(59, 130, 246, .36);
  }

  .qa-emerald {
    --qa-border: rgba(16, 185, 129, .25);
    --qa-hover-bg: rgba(16, 185, 129, .08);
    --qa-ring: rgba(16, 185, 129, .28);
  }

  .dark .qa-emerald {
    --qa-border: rgba(16, 185, 129, .30);
    --qa-hover-bg: rgba(16, 185, 129, .14);
    --qa-ring: rgba(16, 185, 129, .34);
  }

  .qa-violet {
    --qa-border: rgba(139, 92, 246, .25);
    --qa-hover-bg: rgba(139, 92, 246, .08);
    --qa-ring: rgba(139, 92, 246, .30);
  }

  .dark .qa-violet {
    --qa-border: rgba(139, 92, 246, .30);
    --qa-hover-bg: rgba(139, 92, 246, .15);
    --qa-ring: rgba(139, 92, 246, .36);
  }

  .chart-hint {
    white-space: pre-line;
    line-height: 1.15;
    text-align: right;
    font-size: 11px;
    color: #6b7280
  }

  .dark .chart-hint {
    color: #9ca3af
  }

  .chart-hint .k {
    font-weight: 600;
    opacity: .9
  }

  .chart-hint .v {
    font-weight: 700
  }

  /* Mini legend (ikon-only) */
  .mini-legend {
    position: absolute;
    top: .35rem;
    left: 50%;
    transform: translateX(-50%);
    display: inline-flex;
    gap: .5rem;
    z-index: 5;
    pointer-events: auto
  }

  .mini-legend .ml-item {
    width: 18px;
    height: 12px;
    border-radius: .25rem;
    border: 2px solid var(--ml-color, #94a3b8);
    background: var(--ml-bg, rgba(148, 163, 184, .28));
    box-shadow: 0 0 0 1px rgba(0, 0, 0, .02);
    position: relative;
    cursor: pointer;
    transition: transform .18s ease
  }

  .mini-legend .ml-item:hover {
    transform: translateY(-1px)
  }

  .mini-legend .ml-item::before {
    content: "";
    position: absolute;
    left: -2px;
    right: -2px;
    top: 50%;
    height: 2px;
    background: #111;
    transform: translateY(-50%) scaleX(0);
    transform-origin: center;
    transition: transform .18s ease
  }

  .mini-legend .ml-item.is-off::before {
    transform: translateY(-50%) scaleX(1)
  }

  /* Naikkan posisi hint Project Report agar tidak mepet chart */
  #projectsReportHint {
    top: .75rem !important
  }
</style>
@endpush

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
  <div class="flex items-end justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Overview</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">Ringkasan tiket, task, dan project anda.</p>
    </div>
    <div class="text-sm text-gray-500 dark:text-gray-400">{{ now()->format('D, d M Y') }}</div>
  </div>

  @php
  $card='rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/60 dark:ring-white/10 p-4';
  $ttl='text-[11px] font-semibold tracking-wider uppercase text-gray-500 dark:text-gray-400';
  $val='mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100';
  $cap='text-xs text-gray-500 dark:text-gray-400';
  $chartCard='chart-card rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/60 dark:ring-white/10 p-4 h-[320px] md:h-[360px] lg:h-[400px]';

  $ticketsNew=(int)($ticketsNew??0); $ticketsInProgress=(int)($ticketsInProgress??0); $ticketsDone=(int)($ticketsDone??0);
  $usersCount=(int)($usersCount??0); $tasksDone=(int)($tasksDone??0); $projectsCompleted=(int)($projectsCompleted??0);

  $ticketsLabels=$ticketsLabels??[]; $ticketsValues=array_map('intval',$ticketsValues??[]);
  $taskStatusLabels=$taskStatusLabels??[]; $taskStatusCounts=array_map('intval',$taskStatusCounts??[]);
  $projectStatusLabels=$projectStatusLabels??[]; $projectStatusCounts=array_map('intval',$projectStatusCounts??[]);
  $tasksWeeklyLabels=$tasksWeeklyLabels??[]; $tasksWeeklyDoneCounts=array_map('intval',$tasksWeeklyDoneCounts??[]);
  $tasksWeeklyProgressCounts=array_map('intval',$tasksWeeklyProgressCounts??[]);
  $projCreatedLabels=$projCreatedLabels??[]; $projCreatedCounts=array_map('intval',$projCreatedCounts??[]);
  $projReportLabels=$projReportLabels??[]; $projReportDoneCounts=array_map('intval',$projReportDoneCounts??[]);
  $projReportProgressCounts=array_map('intval',$projReportProgressCounts??[]);
  $projectsPeriod=$projectsPeriod??'';

  $ticketsTotal=max(0,$ticketsNew+$ticketsInProgress+$ticketsDone);
  $ticketRate=$ticketsTotal>0?round($ticketsDone/$ticketsTotal*100):0;
  $tasksTotal=count($taskStatusCounts)>0?array_sum($taskStatusCounts):$tasksDone;
  $taskRate=$tasksTotal>0?round($tasksDone/$tasksTotal*100):0;
  $projectsTotal=count($projectStatusCounts)>0?array_sum($projectStatusCounts):$projectsCompleted;
  $projectRate=$projectsTotal>0?round($projectsCompleted/$projectsTotal*100):0;
  @endphp

  {{-- Quick actions (sekarang ada hover tint halus) --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 fade-in">
    <a href="{{ route('tickets.create') }}" class="card qa qa-blue flex items-center gap-3">
      <span class="material-icons text-blue-600 dark:text-blue-400">add_task</span>
      <div>
        <div class="font-semibold">Buat Ticket</div>
        <div class="kpi-cap">Catat masalah baru dan assign tim</div>
      </div>
    </a>

    <a href="{{ route('tasks.create') }}" class="card qa qa-emerald flex items-center gap-3">
      <span class="material-icons text-emerald-600 dark:text-emerald-400">checklist</span>
      <div>
        <div class="font-semibold">Tambah Task</div>
        <div class="kpi-cap">Breakdown pekerjaan dan due date</div>
      </div>
    </a>

    <a href="{{ route('projects.create') }}" class="card qa qa-violet flex items-center gap-3">
      <span class="material-icons text-violet-600 dark:text-violet-400">folder_open</span>
      <div>
        <div class="font-semibold">Project Baru</div>
        <div class="kpi-cap">Kelola scope dan progress</div>
      </div>
    </a>
  </div>

  {{-- KPI --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
    <div class="{{ $card }}">
      <div class="flex items-center justify-between">
        <div class="{{ $ttl }}">Tickets (New)</div><span class="material-icons text-blue-500">fiber_new</span>
      </div>
      <div class="{{ $val }}">{{ $ticketsNew }}</div>
      <div class="{{ $cap }}">Menunggu diproses</div>
    </div>

    <div class="{{ $card }}">
      <div class="flex items-center justify-between">
        <div class="{{ $ttl }}">Tickets (In Progress)</div><span class="material-icons text-amber-500">autorenew</span>
      </div>
      <div class="{{ $val }}">{{ $ticketsInProgress }}</div>
      <div class="{{ $cap }}">Sedang ditangani</div>
    </div>

    <div class="{{ $card }}">
      <div class="flex items-center justify-between">
        <div class="{{ $ttl }}">Tickets (Done)</div><span class="material-icons text-emerald-500">task_alt</span>
      </div>
      <div class="{{ $val }}">{{ $ticketsDone }}</div>
      <div class="{{ $cap }}">Selesai ditangani</div>
    </div>

    <div class="{{ $card }}">
      <div class="flex items-center justify-between">
        <div class="{{ $ttl }}">Users Terdaftar</div><span class="material-icons text-indigo-500">people</span>
      </div>
      <div class="{{ $val }}">{{ $usersCount }}</div>
      <div class="{{ $cap }}">Akun aktif</div>
    </div>

    <div class="{{ $card }}">
      <div class="flex items-center justify-between">
        <div class="{{ $ttl }}">Tasks Done</div><span class="material-icons text-sky-500">check_circle</span>
      </div>
      <div class="{{ $val }}">{{ $tasksDone }}</div>
      <div class="{{ $cap }}">Total selesai</div>
    </div>

    <div class="{{ $card }}">
      <div class="flex items-center justify-between">
        <div class="{{ $ttl }}">Projects Done</div><span class="material-icons text-pink-500">done_all</span>
      </div>
      <div class="{{ $val }}">{{ $projectsCompleted }}</div>
      <div class="{{ $cap }}">Project tuntas</div>
    </div>
  </div>

  {{-- Insight --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 fade-in">
    <div class="card">
      <div class="flex items-center justify-between">
        <div class="text-sm font-semibold">Ticket Completion</div>
        <div class="kpi-cap">{{ $ticketsDone }}/{{ $ticketsTotal }}</div>
      </div>
      <div class="mt-2 meter {{ $ticketRate<40?'danger':($ticketRate<70?'warning':'') }}"><i style="--w: {{ $ticketRate }}%"></i></div>
      <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $ticketRate }}% tickets selesai</div>
      <div class="kpi-cap mt-1">New: {{ $ticketsNew }}, In progress: {{ $ticketsInProgress }}, Done: {{ $ticketsDone }}</div>
    </div>

    <div class="card">
      <div class="flex items-center justify-between">
        <div class="text-sm font-semibold">Task Completion</div>
        <div class="kpi-cap">{{ $tasksDone }}/{{ $tasksTotal }}</div>
      </div>
      <div class="mt-2 meter {{ $taskRate<40?'danger':($taskRate<70?'warning':'') }}"><i style="--w: {{ $taskRate }}%"></i></div>
      <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $taskRate }}% tasks selesai</div>
      <div class="kpi-cap mt-1">Periode: {{ $tasksPeriod }}</div>
    </div>

    <div class="card">
      <div class="flex items-center justify-between">
        <div class="text-sm font-semibold">Project Completion</div>
        <div class="kpi-cap">{{ $projectsCompleted }}/{{ $projectsTotal }}</div>
      </div>
      <div class="mt-2 meter {{ $projectRate<40?'danger':($projectRate<70?'warning':'') }}"><i style="--w: {{ $projectRate }}%"></i></div>
      <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $projectRate }}% projects tuntas</div>
      <div class="kpi-cap mt-1">Status: {{ implode(', ', $projectStatusLabels) }}</div>
    </div>
  </div>

  {{-- CHARTS ROW 1 --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="{{ $chartCard }}">
      <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Tickets by Status</h3>
      <span id="ticketsHint" class="pointer-events-none absolute top-4 right-4 text-[11px] font-medium text-gray-500 dark:text-gray-400"></span>
      <div class="h-[calc(100%-1.75rem)]">
        <canvas id="ticketsChart" data-labels='@json($ticketsLabels)' data-values='@json($ticketsValues)'></canvas>
      </div>
    </div>

    <div class="{{ $chartCard }}">
      <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Task Report</h3>
      <span id="tasksReportHint" class="chart-hint pointer-events-none absolute top-4 right-4"></span>
      <div class="h-[calc(100%-1.75rem)]">
        <canvas id="tasksReportChart"
          data-labels='@json($taskReportLabels)'
          data-values-done='@json($taskReportDoneCounts)'
          data-values-progress='@json($taskReportProgressCounts)'
          data-period="{{ $tasksPeriod }}"></canvas>
      </div>
    </div>

    <div class="{{ $chartCard }}">
      <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Tasks by Status</h3>
      <span id="taskStatusHint_top" class="pointer-events-none absolute top-4 right-4 text-[11px] font-medium text-gray-500 dark:text-gray-400"></span>
      <div class="h-[calc(100%-1.75rem)]">
        <canvas id="tasksStatusChart_top"
          data-labels='@json($taskStatusLabels)'
          data-values='@json($taskStatusCounts)'></canvas>
      </div>
    </div>
  </div>

  {{-- CHARTS ROW 2 --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="{{ $chartCard }}">
      <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Projects Created</h3>
      <span id="projectsCreatedHint" class="chart-hint pointer-events-none absolute top-4 right-4"></span>
      <div class="h-[calc(100%-1.75rem)]">
        <canvas id="projectsCreatedChart"
          data-labels='@json($projCreatedLabels)'
          data-values='@json($projCreatedCounts)'
          data-period="{{ $projectsPeriod }}"></canvas>
      </div>
    </div>

    <div class="{{ $chartCard }}">
      <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Project Report</h3>
      <span id="projectsReportHint" class="chart-hint pointer-events-none absolute top-4 right-4"></span>
      <div class="h-[calc(100%-1.75rem)]">
        <canvas id="projectsReportChart"
          data-labels='@json($projReportLabels)'
          data-values-done='@json($projReportDoneCounts)'
          data-values-progress='@json($projReportProgressCounts)'
          data-period="{{ $projectsPeriod }}"></canvas>
      </div>
    </div>

    <div class="{{ $chartCard }}">
      <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Projects by Status</h3>
      <span id="projectsHint" class="pointer-events-none absolute top-4 right-4 text-[11px] font-medium text-gray-500 dark:text-gray-400"></span>
      <div class="h-[calc(100%-1.75rem)]">
        <canvas id="projectsChart"
          data-labels='@json($projectStatusLabels)'
          data-values='@json($projectStatusCounts)'></canvas>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@vite('resources/js/dashboard.js')
@endpush
