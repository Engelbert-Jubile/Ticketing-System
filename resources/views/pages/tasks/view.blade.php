{{-- LEGACY (fallback) resources/views/pages/tasks/view.blade.php --}}
@extends('layouts.app')

@section('title', 'Lihat Task')

@section('content')
<div class="page-theme page-theme--task">
  <div class="page-shell page-shell--wide py-6">
    <div class="space-y-8">
      {{-- Back button --}}
      <div class="flex justify-center md:justify-start">
        @include('components.back', ['to' => request('from', route('tasks.report')), 'text' => 'Task Reports', 'icon' => 'list'])
      </div>

      <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 p-6 text-white shadow-xl">
        <div class="relative z-[1] flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-3xl font-semibold">{{ $task->title }}</h2>
            <p class="mt-3 max-w-2xl text-sm text-white/80">Detail lengkap dari task yang telah dibuat.</p>
          </div>
          @php
            $statusValue = $task->status instanceof \BackedEnum ? $task->status->value : $task->status;
            $normalized = \App\Support\WorkflowStatus::normalize($statusValue);
            $label = \App\Support\WorkflowStatus::label($normalized);
            $badgeClass = \App\Support\WorkflowStatus::badgeClass($normalized);
          @endphp
          <div class="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 text-sm backdrop-blur">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                <path d="M7.5 2.25A2.25 2.25 0 0 0 5.25 4.5v15a.75.75 0 0 0 1.06.67l4.19-2.094a.75.75 0 0 1 .66 0l4.19 2.094a.75.75 0 0 0 1.06-.67v-15A2.25 2.25 0 0 0 15.75 2.25h-8.25Z" />
              </svg>
            </div>
            <div>
              <div class="font-semibold">Status: <span class="inline-block rounded-full px-2 py-1 text-xs {{ $badgeClass }}">{{ $label }}</span></div>
              <p class="text-xs text-white/80 mt-1">Updated: {{ optional($task->updated_at)->translatedFormat('d M Y H:i') ?? '—' }}</p>
            </div>
          </div>
        </div>
        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
      </div>

      {{-- Informasi Task (Step 1) --}}
      <div class="grid gap-6 lg:grid-cols-2">
        {{-- Judul & Deskripsi --}}
        <div class="rounded-2xl border border-gray-200 bg-white/90 p-6 shadow-sm backdrop-blur-sm transition-all dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-4 flex items-start gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-200">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                <path d="M16.5 4.5v15a.75.75 0 0 1-1.102.659L12 18.576l-3.398 1.583A.75.75 0 0 1 7.5 19.5v-15a.75.75 0 0 1 1.102-.659L12 5.424l3.398-1.583a.75.75 0 0 1 1.102.659Z" />
              </svg>
            </span>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Informasi Task</p>
            </div>
          </div>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Judul Task</label>
              <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-gray-900">
                {{ $task->title }}
              </div>
            </div>
          </div>
        </div>

        {{-- Deskripsi --}}
        <div class="rounded-2xl border border-gray-200 bg-white/90 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-4 flex items-center justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Detail Deskripsi</p>
            </div>
          </div>
          <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900 prose prose-sm max-w-none dark:prose-invert">
            @if($task->description)
              {!! $task->description !!}
            @else
              <span class="text-gray-500 dark:text-gray-400">—</span>
            @endif
          </div>
        </div>
      </div>

      {{-- Relasi --}}
      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Relasi</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">Ticket terkait</label>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
              @if($task->ticket)
                <a href="{{ route('tickets.show', $task->ticket) }}" class="text-blue-600 hover:underline dark:text-blue-400">
                  {{ $task->ticket->ticket_no ?? ('Ticket #' . $task->ticket->id) }} — {{ $task->ticket->title }}
                </a>
              @else
                <span class="text-gray-500">— Tanpa ticket —</span>
              @endif
            </div>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">Output</label>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
              @if($task->project)
                <span class="text-green-600 dark:text-green-400">Sudah menjadi Project</span>
              @else
                <span class="text-gray-500">Tetap sebagai Task</span>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- Data Task & Timeline (Step 2) --}}
      <div class="grid gap-6 lg:grid-cols-2">
        {{-- Data Task --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Data Task</h3>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Prioritas</label>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                @php
                  $priorityMap = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'critical' => 'Critical'];
                  $priority = $task->priority ?? null;
                @endphp
                @if($priority)
                  <span class="inline-block rounded px-2 py-0.5 text-xs font-semibold 
                    {{ $priority === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : '' }}
                    {{ $priority === 'high' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' : '' }}
                    {{ $priority === 'normal' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}
                    {{ $priority === 'low' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                  ">
                    {{ $priorityMap[$priority] ?? $priority }}
                  </span>
                @else
                  <span class="text-gray-500">—</span>
                @endif
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Status</label>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badgeClass }}">
                  {{ $label }}
                </span>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Due Date</label>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                @php
                  $dueDate = $task->due_at;
                @endphp
                @if($dueDate)
                  {{ \Carbon\Carbon::parse($dueDate)->translatedFormat('d/m/Y') }}
                @else
                  <span class="text-gray-500">—</span>
                @endif
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Due Time</label>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                @if($dueDate)
                  {{ \Carbon\Carbon::parse($dueDate)->format('H:i') }}
                @else
                  <span class="text-gray-500">—</span>
                @endif
              </div>
            </div>

            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Assign ke User</label>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                @php
                  $assignees = collect();
                  $assignedIds = [];
                  $rawAssigned = $task->assigned_to;

                  if ($rawAssigned) {
                    $decoded = null;
                    if (is_string($rawAssigned)) {
                      $decoded = json_decode($rawAssigned, true);
                      if (json_last_error() !== JSON_ERROR_NONE) {
                        $decoded = null;
                      }
                    } elseif (is_array($rawAssigned)) {
                      $decoded = $rawAssigned;
                    }

                    if (is_array($decoded)) {
                      $assignedIds = array_values(array_unique(array_map(fn($val) => is_numeric($val) ? (int) $val : null, array_filter($decoded, fn($v) => $v !== null && $v !== ''))));
                    } elseif (is_string($rawAssigned)) {
                      $fallbackNames = array_map('trim', array_filter(explode(',', $rawAssigned), fn($name) => trim($name) !== ''));
                      foreach ($fallbackNames as $name) {
                        $assignees->push($name);
                      }
                    }
                  }

                  if (!empty($assignedIds)) {
                    $usersMap = \App\Models\User::whereIn('id', $assignedIds)->get()->keyBy('id');
                    foreach ($assignedIds as $assignedId) {
                      $user = $usersMap->get($assignedId);
                      if ($user) {
                        $assignees->push($user->display_name ?? $user->email ?? ('User #' . $assignedId));
                      }
                    }
                  }

                  if ($task->assignee_id && $task->assignee) {
                    $assignees->prepend($task->assignee->display_name ?? $task->assignee->email);
                  }

                  $assignees = $assignees->filter()->unique()->values();
                @endphp
                @if($assignees->isNotEmpty())
                  <div class="flex flex-wrap gap-2">
                    @foreach($assignees as $assignee)
                      <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                        {{ $assignee }}
                      </span>
                    @endforeach
                  </div>
                @else
                  <span class="text-gray-500">— Tidak ada assignee —</span>
                @endif
              </div>
            </div>

            @if($task->requester_id)
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Requester</label>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                {{ optional($task->requester)->display_name ?? optional($task->requester)->email ?? '—' }}
              </div>
            </div>
            @endif
          </div>
        </div>

        {{-- Timeline --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Timeline</h3>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-600 dark:text-gray-300">Created At</label>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                @php
                  $tz = config('app.timezone');
                @endphp
                {{ optional($task->created_at)->timezone($tz ?? 'UTC')->translatedFormat('d M Y H:i') ?? '—' }}
              </div>
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-600 dark:text-gray-300">Updated At</label>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                {{ optional($task->updated_at)->timezone($tz ?? 'UTC')->translatedFormat('d M Y H:i') ?? '—' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Project Info (jika ada) --}}
      @if($task->project)
      <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-6 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-500/10">
        <h3 class="text-sm font-semibold text-emerald-700 dark:text-emerald-200">Project Terkait</h3>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
          <div>
            <label class="block text-sm font-medium text-emerald-600 dark:text-emerald-300 mb-1">Judul Project</label>
            <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm dark:border-emerald-700 dark:bg-gray-900">
              <a href="{{ route('projects.show', ['project' => $task->project->public_slug ?? $task->project->id]) }}" class="text-emerald-600 hover:underline dark:text-emerald-400">
                {{ $task->project->title }}
              </a>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-emerald-600 dark:text-emerald-300 mb-1">Nomor Project</label>
            <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm dark:border-emerald-700 dark:bg-gray-900">
              {{ $task->project->project_no ?? '—' }}
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-emerald-600 dark:text-emerald-300 mb-1">Status Project</label>
            <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm dark:border-emerald-700 dark:bg-gray-900">
              {{ $task->project->status ?? '—' }}
            </div>
          </div>
        </div>
      </div>
      @endif

      {{-- Attachments (Step 3) --}}
      @php
        $taskAttachments = $task->attachments ?? collect();
        $ticketAttachments = $task->ticket?->attachments ?? collect();
        $allAttachments = $taskAttachments->merge($ticketAttachments);
      @endphp
      @if($allAttachments->isNotEmpty())
      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Lampiran</h3>
        <div class="mt-4 grid gap-3 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
          @foreach($allAttachments as $att)
          <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900 flex items-center justify-between gap-3">
            <div class="flex-1 truncate">
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $att->original_name }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                @php
                  $size = $att->size ?? $att->file_size ?? 0;
                  $units = ['B', 'KB', 'MB', 'GB'];
                  $i = 0;
                  while ($size >= 1024 && $i < count($units) - 1) {
                    $size /= 1024;
                    $i++;
                  }
                  echo round($size, 2) . ' ' . $units[$i];
                @endphp
              </div>
            </div>
            <div class="flex gap-1 flex-shrink-0">
              <a href="{{ route('attachments.view', $att) }}" target="_blank"
                 class="p-1.5 rounded border border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800" title="View">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                  <path d="M12 15a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z" />
                  <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.826 0 1.188-1.49 4.467-5.705 7.694-10.677 7.694-4.972 0-9.186-3.227-10.675-7.694a1.762 1.762 0 0 1 0-1.188ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" />
                </svg>
              </a>
              <a href="{{ route('attachments.download', $att) }}"
                 class="p-1.5 rounded border border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800" title="Download">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                  <path fill-rule="evenodd" d="M12 2.25a.75.75 0 0 1 .75.75v11.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.22 3.22V3a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                </svg>
              </a>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @endif

      {{-- Tombol Aksi --}}
      <div class="flex flex-wrap items-center justify-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 px-5 py-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
        <a href="{{ route('tasks.edit', ['task' => $task->public_slug]) }}"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-300 dark:focus:ring-offset-gray-900">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
            <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157a.75.75 0 0 0 0 1.061l3.712 3.712a.75.75 0 0 0 1.06 0l1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z" />
            <path d="M5.25 5.25a3 3 0 0 0-3 3v10.5a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3V13.5a.75.75 0 0 0-1.5 0v4.75a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V8.25a1.5 1.5 0 0 1 1.5-1.5h4.75a.75.75 0 0 0 0-1.5H5.25Z" />
          </svg>
          <span>Edit Task</span>
        </a>
        <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus task ini?')">
          @csrf @method('DELETE')
          <input type="hidden" name="from" value="{{ request()->fullUrl() }}">
          <button type="submit"
            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-300 dark:focus:ring-offset-gray-900">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
              <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478m-3.622.986a48.88 48.88 0 0 1-4.144 0M9 7.522c.513-1.978 2.25-3.522 4.5-3.522s3.987 1.544 4.5 3.522m-9 1.5h13.5M5.75 18h12.5a1.5 1.5 0 0 0 1.5-1.5V7.5a1.5 1.5 0 0 0-1.5-1.5H5.75a1.5 1.5 0 0 0-1.5 1.5v9a1.5 1.5 0 0 0 1.5 1.5Z" clip-rule="evenodd" />
            </svg>
            <span>Hapus Task</span>
          </button>
        </form>
      </div>

      {{-- Back to report --}}
      <div class="flex justify-center">
        <a href="{{ request('from', route('tasks.report')) }}"
          class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 shadow transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
          Kembali ke Task Reports
        </a>
      </div>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
  .page-theme--task { --brandA: #0ea5e9; --brandB: #06b6d4; }
  html{scroll-behavior:smooth}
  .page-shell{min-height:100vh;overflow-y:auto}
</style>
@endpush
