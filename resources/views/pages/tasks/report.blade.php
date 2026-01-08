@extends('layouts.app')

@section('title', 'Task Reports')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 transition-colors duration-300">

  {{-- Header --}}
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div class="flex items-start gap-3">
      <div class="shrink-0">
        @include('components.back', ['to' => route('dashboard')])
      </div>
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Task Reports</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Laporan dan statistik task</p>
      </div>
    </div>
    <a href="{{ route('tasks.create', ['from' => request()->fullUrl()]) }}"
      class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 via-indigo-500 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-purple-500/30 transition duration-300 hover:shadow-xl">
      <span class="material-icons text-[18px]">add</span>
      Buat Task Baru
    </a>
  </div>

  @if(session('success'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
      {{ session('success') }}
    </div>
  @endif

  {{-- Filter Bar --}}
  <form method="GET" class="mb-6 grid items-end gap-3 grid-cols-12 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-gray-800">
    <div class="col-span-12 lg:col-span-4">
      <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Cari</label>
      <input type="text" name="q" value="{{ $q ?? '' }}"
        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        placeholder="Cari task...">
    </div>

    <div class="col-span-6 sm:col-span-4 lg:col-span-2">
      <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Status</label>
      <select name="status"
        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
        <option value="">Semua Status</option>
        @foreach ($statuses as $s)
          <option value="{{ $s }}" {{ (isset($status) && $status === $s) ? 'selected' : '' }}>
            {{ \App\Support\WorkflowStatus::label($s) }}
          </option>
        @endforeach
      </select>
    </div>

    <div class="col-span-6 sm:col-span-3 lg:col-span-2">
      <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Dari</label>
      <input type="text" name="from" value="{{ $fromRaw ?? '' }}"
        class="flatpickr-field w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        placeholder="dd/mm/yyyy" autocomplete="off">
    </div>

    <div class="col-span-6 sm:col-span-3 lg:col-span-2">
      <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Sampai</label>
      <input type="text" name="to" value="{{ $toRaw ?? '' }}"
        class="flatpickr-field w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        placeholder="dd/mm/yyyy" autocomplete="off">
    </div>

    <div class="col-span-12 lg:col-span-2">
      <div class="flex justify-end gap-2">
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white font-medium hover:bg-blue-700">
          Filter
        </button>
        <a href="{{ route('tasks.report') }}"
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800/60">Reset</a>
      </div>
    </div>
  </form>

  {{-- Table --}}
  <div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm dark:border-slate-700 dark:bg-gray-800">
    <table class="report-table min-w-full text-sm">
      <thead>
        <tr class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-200">
          <th class="px-4 py-3 text-left w-12">#</th>
          <th class="px-4 py-3 text-left">Title</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-left">Priority</th>
          <th class="px-4 py-3 text-left">Assignee</th>
          <th class="px-4 py-3 text-left">Due Date</th>
          <th class="px-4 py-3 text-left">Actions</th>
        </tr>
      </thead>

      <tbody>
        @forelse($tasks as $task)
          @php
            $statusValue = $task->status instanceof \BackedEnum ? $task->status->value : ($task->status ?? '');
            $statusValue = \App\Support\WorkflowStatus::normalize($statusValue);
            $statusLabel = \App\Support\WorkflowStatus::label($statusValue);

            $priority = ucfirst($task->priority ?? 'normal');
            $assignee = $task->assignee?->display_name ?? $task->assignee?->email ?? '—';
            
            $tz = config('app.timezone');
            $due = '—';
            if ($task->due_date) {
              try {
                $due = \Illuminate\Support\Carbon::parse($task->due_date)->timezone($tz)->format('d/m/Y');
              } catch (\Throwable $e) {
                $due = $task->due_date;
              }
            }
          @endphp

          <tr class="border-b border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/30">
            <td class="cell-index px-4 py-3">{{ $loop->iteration }}</td>
            
            <td class="px-4 py-3">
              <span class="font-medium text-gray-900 dark:text-gray-100">{{ $task->title }}</span>
            </td>

            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ \App\Support\WorkflowStatus::badgeClass($statusValue) }}">
                {{ $statusLabel }}
              </span>
            </td>

            <td class="px-4 py-3">
              <span class="capitalize">{{ $priority }}</span>
            </td>

            <td class="px-4 py-3">
              {{ $assignee }}
            </td>

            <td class="px-4 py-3">
              {{ $due }}
            </td>

            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tasks.view', ['task' => $task->id]) }}"
                  class="rounded-lg border border-slate-200 px-3 py-1.5 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800/60">View</a>

                <a href="{{ route('tasks.edit', ['task' => $task->public_slug]) }}"
                  class="rounded-lg border border-blue-200 px-3 py-1.5 text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-900/30">Edit</a>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
              Tidak ada task untuk laporan.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  <div class="mt-4">
    {{ $tasks->links() }}
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
  .report-table {
    border-collapse: separate;
    border-spacing: 0;
  }

  .report-table thead th {
    background: #f8fafc;
    border-bottom: 2px solid #cbd5e1;
    color: #334155
  }

  .cell-index {
    text-align: center;
    font-weight: 700;
    background: #f1f5f9
  }

  .dark .report-table thead th {
    background: #0f172a;
    border-color: #334155;
    color: #e2e8f0
  }

  .dark .cell-index {
    background: #0b1220
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    flatpickr(".flatpickr-field", {
      dateFormat: "d/m/Y",
      allowInput: true
    });
  });
</script>
@endpush
