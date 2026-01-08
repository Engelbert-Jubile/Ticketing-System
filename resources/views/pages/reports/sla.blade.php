{{-- LEGACY (fallback) resources/views/pages/reports/sla.blade.php --}}
@extends('layouts.app')

@section('title', 'SLA Reports')

@section('content')
@php
    $isPaginated = $records instanceof \Illuminate\Contracts\Pagination\Paginator;
    $downloadQuery = array_filter([
        'type'       => $type,
        'from'       => $filters['from'] ?? null,
        'to'         => $filters['to'] ?? null,
        'sla_status' => $filters['sla_status'] ?? null,
        'q'          => $filters['q'] ?? null,
    ]);

    $slaBadgeClasses = [
        'met'      => 'bg-emerald-100 text-emerald-700',
        'pending'  => 'bg-amber-100 text-amber-700',
        'breached' => 'bg-rose-100 text-rose-700',
        'missing'  => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="mx-auto max-w-7xl px-4 py-6 transition-colors duration-300">
  @include('components.back', ['to' => route('dashboard')])

  <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-900 transition-colors duration-300 dark:text-gray-100">Service Level Agreement Reports</h1>
      <p class="text-sm text-gray-600 dark:text-gray-400">Pantau performa SLA ticket, task, dan project secara menyeluruh.</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('sla.download', array_merge($downloadQuery, ['format' => 'csv'])) }}"
        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-200 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
        <span class="material-icons text-base">download</span>
        <span>Download CSV</span>
      </a>
    </div>
  </div>

  <div class="mb-5 flex flex-wrap gap-2">
    @foreach ($availableTypes as $typeItem)
      @php
        $tabQuery = array_filter(array_merge($downloadParams ?? [], ['type' => $typeItem['value']]));
        unset($tabQuery['page']);
      @endphp
      <a href="{{ route('dashboard.sla', $tabQuery) }}"
        wire:navigate
        class="rounded-full px-4 py-2 text-sm font-semibold transition-colors {{ $type === $typeItem['value'] ? 'bg-blue-600 text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700' }}">
        {{ $typeItem['label'] }}
      </a>
    @endforeach
  </div>

  <form method="GET" class="mb-6 grid grid-cols-12 gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900/40">
    <input type="hidden" name="type" value="{{ $type }}">

    <div class="col-span-12 md:col-span-3">
      <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-300">Dari</label>
      <input type="text" name="from" value="{{ $filters['from'] ?? '' }}" placeholder="dd/mm/yyyy"
        class="flatpickr-field w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition-colors dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500">
    </div>

    <div class="col-span-12 md:col-span-3">
      <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-300">Sampai</label>
      <input type="text" name="to" value="{{ $filters['to'] ?? '' }}" placeholder="dd/mm/yyyy"
        class="flatpickr-field w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition-colors dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500">
    </div>

    <div class="col-span-12 md:col-span-3">
      <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-300">Status SLA</label>
      <select name="sla_status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition-colors dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
        @php
          $statuses = [
            '' => 'Semua status',
            'met' => 'Tercapai',
            'pending' => 'Dalam Proses',
            'breached' => 'Lewat SLA',
            'missing' => 'SLA tidak diset',
          ];
        @endphp
        @foreach($statuses as $value => $label)
          <option value="{{ $value }}" @selected(($filters['sla_status'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-span-12 md:col-span-3">
      <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-300">Pencarian</label>
      <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul / nomor"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition-colors dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-500">
    </div>

    <div class="col-span-6 md:col-span-2">
      <label class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-300">Per halaman</label>
      <select name="per_page" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition-colors dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
        @foreach([15,25,50,100] as $per)
          <option value="{{ $per }}" @selected((int)($filters['per_page'] ?? 25) === $per)>{{ $per }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-span-12 md:col-span-2 flex items-end justify-end gap-2">
      <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
        Terapkan
      </button>
      <a href="{{ route('dashboard.sla', ['type' => $type]) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
        Reset
      </a>
    </div>
  </form>

  <div class="mb-6 grid gap-3 md:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
      <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Total Item</div>
      <div class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $stats['total'] ?? 0 }}</div>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-700 dark:bg-emerald-900/30">
      <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-200">SLA Tercapai</div>
      <div class="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ $stats['met'] ?? 0 }}</div>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-700 dark:bg-amber-900/30">
      <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-200">Dalam Proses</div>
      <div class="mt-1 text-2xl font-bold text-amber-900 dark:text-amber-100">{{ $stats['pending'] ?? 0 }}</div>
    </div>
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm dark:border-rose-700 dark:bg-rose-900/30">
      <div class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-200">Lewat SLA</div>
      <div class="mt-1 text-2xl font-bold text-rose-900 dark:text-rose-100">{{ $stats['breached'] ?? 0 }}</div>
    </div>
  </div>

  @if ($type === 'ticket_work')
    <div class="space-y-4">
      @forelse($records as $entry)
        @php $ticket = $entry['ticket']; @endphp
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-colors duration-300 dark:border-slate-700 dark:bg-slate-900/40">
          <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
              <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Ticket</div>
              <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $ticket['number'] }} · {{ $ticket['title'] }}</h2>
              <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-600 dark:text-slate-300">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800">Status: {{ $ticket['status'] }}</span>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800">Assignee: {{ $ticket['assignee'] ?? '—' }}</span>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800">Deadline: {{ $ticket['deadline']['display'] ?? '—' }}</span>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2">
              @php
                $ticketBadgeClass = $slaBadgeClasses[$ticket['sla']['status'] ?? 'missing'] ?? $slaBadgeClasses['missing'];
              @endphp
              <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $ticketBadgeClass }}">
                {{ $ticket['sla']['label'] ?? '—' }}
              </span>
              <div class="text-xs text-slate-500 dark:text-slate-300">{{ $ticket['sla']['delta_human'] ?? '—' }}</div>
              <div class="flex gap-1 text-xs">
                <a href="{{ $ticket['links']['view'] }}" wire:navigate class="rounded border border-slate-300 px-2 py-1 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">View</a>
                <a href="{{ $ticket['links']['edit'] }}" wire:navigate class="rounded border border-blue-300 px-2 py-1 text-blue-700 hover:bg-blue-50 dark:border-blue-500 dark:text-blue-200 dark:hover:bg-blue-900/40">Edit</a>
              </div>
            </div>
          </div>

          @if(($entry['tasks']['items']->count() ?? 0) > 0)
            <details class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/40">
              <summary class="cursor-pointer text-sm font-semibold text-slate-700 dark:text-slate-100">Task terkait ({{ $entry['tasks']['items']->count() }})</summary>
              <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                      <th class="px-2 py-1">Task</th>
                      <th class="px-2 py-1">Assignee</th>
                      <th class="px-2 py-1">Status</th>
                      <th class="px-2 py-1">Deadline</th>
                      <th class="px-2 py-1">SLA</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($entry['tasks']['items'] as $task)
                      <tr class="border-t border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-200">
                        <td class="px-2 py-1 font-medium">{{ $task['number'] }}</td>
                        <td class="px-2 py-1">{{ $task['assignee'] ?? '—' }}</td>
                        <td class="px-2 py-1">{{ $task['status'] }}</td>
                        <td class="px-2 py-1">{{ $task['deadline']['display'] ?? '—' }}</td>
                        <td class="px-2 py-1">{{ $task['sla']['label'] ?? '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </details>
          @endif

          @if(!empty($entry['project']))
            @php $project = $entry['project']; @endphp
            <details class="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-3 dark:border-indigo-700 dark:bg-indigo-900/30">
              <summary class="cursor-pointer text-sm font-semibold text-indigo-700 dark:text-indigo-200">Project terkait</summary>
              <dl class="mt-3 grid gap-2 text-sm text-slate-700 dark:text-slate-200 md:grid-cols-2">
                <div>
                  <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Project</dt>
                  <dd class="font-semibold">{{ $project['number'] }} · {{ $project['title'] }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Status</dt>
                  <dd>{{ $project['status'] }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Deadline</dt>
                  <dd>{{ $project['deadline']['display'] ?? '—' }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">SLA</dt>
                  <dd>{{ $project['sla']['label'] ?? '—' }} ({{ $project['sla']['delta_human'] ?? '—' }})</dd>
                </div>
              </dl>
            </details>
          @endif
        </div>
      @empty
        <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">Tidak ada data SLA untuk kombinasi filter ini.</div>
      @endforelse
    </div>
  @else
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
          <tr>
            @if($type === 'ticket')
              <th class="px-4 py-3 text-left">Ticket</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Priority</th>
              <th class="px-4 py-3 text-left">Assignee</th>
              <th class="px-4 py-3 text-left">Deadline</th>
              <th class="px-4 py-3 text-left">Completed</th>
              <th class="px-4 py-3 text-left">SLA</th>
              <th class="px-4 py-3 text-left">Actions</th>
            @elseif($type === 'task')
              <th class="px-4 py-3 text-left">Task</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Assignee</th>
              <th class="px-4 py-3 text-left">Ticket</th>
              <th class="px-4 py-3 text-left">Project</th>
              <th class="px-4 py-3 text-left">Deadline</th>
              <th class="px-4 py-3 text-left">Completed</th>
              <th class="px-4 py-3 text-left">SLA</th>
              <th class="px-4 py-3 text-left">Actions</th>
            @else
              <th class="px-4 py-3 text-left">Project</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-left">Owner</th>
              <th class="px-4 py-3 text-left">Ticket</th>
              <th class="px-4 py-3 text-left">Deadline</th>
              <th class="px-4 py-3 text-left">Completed</th>
              <th class="px-4 py-3 text-left">SLA</th>
              <th class="px-4 py-3 text-left">Actions</th>
            @endif
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900/40">
          @forelse($records as $row)
            <tr class="text-slate-700 dark:text-slate-200">
              @if($type === 'ticket')
                <td class="px-4 py-3">
                  <div class="font-semibold">{{ $row['number'] }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ $row['title'] }}</div>
                </td>
                <td class="px-4 py-3">{{ $row['status'] }}</td>
                <td class="px-4 py-3">{{ $row['priority'] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row['assignee'] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row['deadline']['display'] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row['completed_at']['display'] ?? '—' }}</td>
                <td class="px-4 py-3">
                  @php
                    $badgeClass = $slaBadgeClasses[$row['sla']['status'] ?? 'missing'] ?? $slaBadgeClasses['missing'];
                  @endphp
                  <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">{{ $row['sla']['label'] ?? '—' }}</span>
                  <div class="text-xs text-slate-500 dark:text-slate-300">{{ $row['sla']['delta_human'] ?? '—' }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-1 text-xs">
                    <a href="{{ $row['links']['view'] }}" wire:navigate class="rounded border border-slate-300 px-2 py-1 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">View</a>
                    <a href="{{ $row['links']['edit'] }}" wire:navigate class="rounded border border-blue-300 px-2 py-1 text-blue-700 hover:bg-blue-50 dark:border-blue-500 dark:text-blue-200 dark:hover:bg-blue-900/40">Edit</a>
                  </div>
                </td>
              @elseif($type === 'task')
                <td class="px-4 py-3">
                  <div class="font-semibold">{{ $row['number'] }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ $row['title'] }}</div>
                </td>
                <td class="px-4 py-3">{{ $row['status'] }}</td>
                <td class="px-4 py-3">{{ $row['assignee'] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row['ticket_no'] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row['project_no'] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row['deadline']['display'] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row['completed_at']['display'] ?? '—' }}</td>
                <td class="px-4 py-3">
                  @php
                    $badgeClass = $slaBadgeClasses[$row['sla']['status'] ?? 'missing'] ?? $slaBadgeClasses['missing'];
                  @endphp
                  <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">{{ $row['sla']['label'] ?? '—' }}</span>
                  <div class="text-xs text-slate-500 dark:text-slate-300">{{ $row['sla']['delta_human'] ?? '—' }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-1 text-xs">
                    <a href="{{ $row['links']['view'] }}" wire:navigate class="rounded border border-slate-300 px-2 py-1 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">View</a>
                    <a href="{{ $row['links']['edit'] }}" wire:navigate class="rounded border border-blue-300 px-2 py-1 text-blue-700 hover:bg-blue-50 dark:border-blue-500 dark:text-blue-200 dark:hover:bg-blue-900/40">Edit</a>
                  </div>
                </td>
              @else
                <td class="px-4 py-3">
                  <div class="font-semibold">{{ $row['number'] }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ $row['title'] }}</div>
                </td>
                <td class="px-4 py-3">{{ $row['status'] }}</td>
                <td class="px-4 py-3">{{ $row['owner'] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row['ticket_no'] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row['deadline']['display'] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $row['completed_at']['display'] ?? '—' }}</td>
                <td class="px-4 py-3">
                  @php
                    $badgeClass = $slaBadgeClasses[$row['sla']['status'] ?? 'missing'] ?? $slaBadgeClasses['missing'];
                  @endphp
                  <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">{{ $row['sla']['label'] ?? '—' }}</span>
                  <div class="text-xs text-slate-500 dark:text-slate-300">{{ $row['sla']['delta_human'] ?? '—' }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-1 text-xs">
                    <a href="{{ $row['links']['view'] }}" wire:navigate class="rounded border border-slate-300 px-2 py-1 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">View</a>
                    <a href="{{ $row['links']['edit'] }}" wire:navigate class="rounded border border-blue-300 px-2 py-1 text-blue-700 hover:bg-blue-50 dark:border-blue-500 dark:text-blue-200 dark:hover:bg-blue-900/40">Edit</a>
                  </div>
                </td>
              @endif
            </tr>
          @empty
            <tr>
              <td colspan="8" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Tidak ada data untuk kombinasi filter ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif

  @if($isPaginated)
    <div class="mt-4">
      {{ $records->appends(array_filter($filters))->links() }}
    </div>
  @endif
</div>
@endsection
