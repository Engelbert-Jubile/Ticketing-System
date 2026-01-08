{{-- LEGACY (fallback) resources/views/pages/tasks/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="page-shell page-shell--wide py-6 space-y-6">

  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-start gap-3">
      <div class="shrink-0">
        @include('components.back', ['to' => route('dashboard')])
      </div>
      <div>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Task Reports</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Atur pekerjaan tim, lihat status terbaru, dan tindak lanjuti pekerjaan yang tertunda.</p>
      </div>
    </div>
    <a href="{{ route('tasks.create', ['from' => route('dashboard')]) }}"
      class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition hover:shadow-xl">
      <span class="material-icons text-[18px]">add_task</span>
      Task Baru
    </a>
  </div>

  @if(session('success'))
  <div class="rounded-2xl border border-emerald-300/70 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-400/40 dark:bg-emerald-500/15 dark:text-emerald-200">
    {{ session('success') }}
  </div>
  @endif

  <div class="flex flex-wrap gap-2 text-sm font-medium">
    <a href="{{ route('tasks.index') }}"
      class="rounded-full border px-4 py-2 transition {{ $status ? 'border-transparent bg-white/60 text-slate-600 hover:bg-slate-100/80 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-700/60' : 'border-blue-400/40 bg-blue-50/80 text-blue-700 shadow-sm dark:border-blue-500/30 dark:bg-blue-500/20 dark:text-blue-100' }}">
      Semua
    </a>

    @foreach ($statuses as $s)
    @php $label = \App\Support\WorkflowStatus::label($s); @endphp
    <a href="{{ route('tasks.index', ['status' => $s]) }}"
      class="rounded-full border px-4 py-2 transition {{ $status === $s ? 'border-blue-400/40 bg-blue-50/80 text-blue-700 shadow-sm dark:border-blue-500/30 dark:bg-blue-500/20 dark:text-blue-100' : 'border-transparent bg-white/60 text-slate-600 hover:bg-slate-100/80 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-700/60' }}">
      {{ $label }}
    </a>
    @endforeach
  </div>

  <div class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white/90 shadow-sm backdrop-blur dark:border-slate-700/60 dark:bg-slate-900/70">
    <table class="min-w-full divide-y divide-slate-200/70 text-sm dark:divide-slate-700/60">
      <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/70 dark:text-slate-300">
        <tr>
          <th class="px-4 py-3">Title</th>
          <th class="px-4 py-3">Priority</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Status ID</th>
          <th class="px-4 py-3">Due</th>
          <th class="px-4 py-3">Attachments</th>
          <th class="px-4 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200/60 dark:divide-slate-800/70">
        @forelse($tasks as $task)
        @php
          $statusValue = \App\Support\WorkflowStatus::normalize($task->status);
          $statusLabel = \App\Support\WorkflowStatus::label($statusValue);
          $dueLabel = $task->due_at ? optional(\Carbon\Carbon::parse($task->due_at))->format('Y-m-d H:i') : '-';
          $atts = ($task->attachments ?? collect());
          $statusCodes = [
            \App\Support\WorkflowStatus::NEW => 'NEW',
            \App\Support\WorkflowStatus::IN_PROGRESS => 'INPR',
            \App\Support\WorkflowStatus::CONFIRMATION => 'CONF',
            \App\Support\WorkflowStatus::REVISION => 'REVS',
            \App\Support\WorkflowStatus::DONE => 'DONE',
            \App\Support\WorkflowStatus::CANCELLED => 'CANC',
            \App\Support\WorkflowStatus::ON_HOLD => 'HOLD',
          ];
          $statusId = $statusCodes[$statusValue] ?? strtoupper(substr(str_replace([' ', '_'], '', $statusValue), 0, 4));
        @endphp
        <tr class="transition hover:bg-blue-50/60 dark:hover:bg-slate-800/80">
          <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ $task->title }}</td>
          <td class="px-4 py-3 capitalize">
            <span class="status-badge" data-status="{{ $task->priority ?? 'normal' }}">{{ $task->priority ?? '-' }}</span>
          </td>
          <td class="px-4 py-3 align-middle">
            <span class="status-badge" data-status="{{ $statusValue }}">{{ $statusLabel }}</span>
          </td>
          <td class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">{{ $statusId }}</td>
          <td class="px-4 py-3">{{ $dueLabel }}</td>
          <td class="px-4 py-3">
            @if(method_exists($atts, 'isNotEmpty') ? $atts->isNotEmpty() : count($atts) > 0)
              <ul class="list-disc list-inside text-xs">
                @foreach($atts as $att)
                  <li>
                    <a href="{{ route('attachments.view', $att) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a>
                    <span class="text-slate-400">·</span>
                    <a href="{{ route('attachments.download', $att) }}" class="text-blue-600 hover:underline">Unduh</a>
                    <span class="text-slate-600 ml-1">{{ \Illuminate\Support\Str::limit($att->original_name, 24) }}</span>
                  </li>
                @endforeach
              </ul>
            @else
              <span class="text-gray-400">-</span>
            @endif
          </td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap justify-end gap-2">
              <a href="{{ route('tasks.show', ['taskSlug' => $task->public_slug]) }}"
                class="inline-flex items-center gap-1 rounded-lg border border-indigo-200/70 px-3 py-1.5 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50/80 dark:border-indigo-400/40 dark:text-indigo-200 dark:hover:bg-indigo-500/10">View</a>
              <a href="{{ route('tasks.edit', ['task' => $task->public_slug]) }}"
                class="inline-flex items-center gap-1 rounded-lg border border-blue-200/70 px-3 py-1.5 text-sm font-semibold text-blue-600 transition hover:bg-blue-50/80 dark:border-blue-400/40 dark:text-blue-200 dark:hover:bg-blue-500/10">Edit</a>
              <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="from" value="{{ request()->fullUrl() }}">
                <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-red-200/70 px-3 py-1.5 text-sm font-semibold text-red-600 transition hover:bg-red-50/80 dark:border-red-400/40 dark:text-red-300 dark:hover:bg-red-500/10">Delete</button>
              </form>
              @include('components.task.promote-button', ['task' => $task, 'size' => 'sm'])
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-slate-300">
            Belum ada task untuk ditampilkan.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>
@endsection
