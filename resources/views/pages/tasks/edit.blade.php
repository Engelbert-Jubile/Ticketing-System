{{-- LEGACY (fallback) resources/views/pages/tasks/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
@php
$backTo = request('from', route('tasks.report'));
@endphp

<div class="page-theme page-theme--task">
  <div class="page-shell page-shell--wide py-6">
    <div class="space-y-8">
      {{-- Back button --}}
      <div class="flex justify-center md:justify-start">
        @include('components.back', ['to' => $backTo])
      </div>

      {{-- Header --}}
      <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 p-6 text-white shadow-xl">
        <div class="relative z-[1] flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-3xl font-semibold">Edit Task</h2>
            <p class="mt-3 max-w-2xl text-sm text-white/80">Perbarui informasi, data, dan lampiran task dengan mudah.</p>
          </div>
          @php
          $statusValue = $task->status instanceof \BackedEnum ? $task->status->value : $task->status;
          $normalized = \App\Support\WorkflowStatus::normalize($statusValue);
          $label = \App\Support\WorkflowStatus::label($normalized);
          @endphp
          <div class="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 text-sm backdrop-blur">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Z" />
                <path d="M20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83Z" />
              </svg>
            </div>
            <div>
              <div class="font-semibold">Status: {{ strtoupper($normalized) }}</div>
              <p class="text-xs text-white/80">Updated: {{ optional($task->updated_at)->translatedFormat('d M Y H:i') ?? '—' }}</p>
            </div>
          </div>
        </div>
        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
      </div>

      {{-- Error Messages --}}
      @if ($errors->any())
      <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-200">
        <div class="mb-1 font-semibold">Periksa kembali beberapa isian:</div>
        <ul class="list-disc list-inside space-y-0.5 text-sm">
          @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
      @endif

      {{-- Main Form --}}
      <form method="POST" action="{{ route('tasks.update', $task) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="from" value="{{ $backTo }}">

        {{-- SECTION 1: INFORMASI TASK --}}
        <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-6 flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                <path d="M16.5 4.5v15a.75.75 0 0 1-1.102.659L12 18.576l-3.398 1.583A.75.75 0 0 1 7.5 19.5v-15a.75.75 0 0 1 1.102-.659L12 5.424l3.398-1.583a.75.75 0 0 1 1.102.659Z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Informasi Task</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Isi judul dan deskripsi task</p>
            </div>
          </div>

          <div class="grid gap-6 lg:grid-cols-2">
            {{-- Judul --}}
            <div>
              <label for="title" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Judul Task *</label>
              <input id="title" name="title" type="text" value="{{ old('title', $task->title) }}"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/40">
              @error('title')
              <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>

            {{-- Ticket Relation --}}
            <div>
              <label for="ticket_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Ticket Terkait</label>
              <select id="ticket_id" name="ticket_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/40">
                <option value="">-- Tanpa ticket --</option>
                @foreach ($tickets as $ticket)
                <option value="{{ $ticket->id }}" @selected(old('ticket_id', $task->ticket_id) == $ticket->id)>
                  {{ $ticket->ticket_no ?? ('Ticket #' . $ticket->id) }} — {{ \Illuminate\Support\Str::limit($ticket->title, 40) }}
                </option>
                @endforeach
              </select>
              @error('ticket_id')
              <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>

            {{-- Deskripsi (Quill dengan single toolbar) --}}
            <div class="lg:col-span-2 w-full">
              <label for="description_editor" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi</label>
              <div class="quill-wrapper">
                <div id="description_editor"></div>
              </div>
              <textarea id="description" name="description" class="hidden">{{ old('description', $task->description) }}</textarea>
              @error('description')
              <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>
          </div>
        </div>

        {{-- SECTION 2: DATA TASK --}}
        @php
        $initialDueDate = null;
        $initialDueTime = null;
        if ($task->due_at) {
          try {
            $dueParsed = \Carbon\Carbon::parse($task->due_at);
            $initialDueDate = $dueParsed->format('d/m/Y');
            $initialDueTime = $dueParsed->format('H:i');
          } catch (\Throwable $e) {
            // Silent fail
          }
        }
        $initialPriority = $task->priority ? ucfirst($task->priority) : null;
        $initialStatusLabel = \App\Support\WorkflowStatus::label(\App\Support\WorkflowStatus::normalize($task->status));

        $usersCollection = $users instanceof \Illuminate\Support\Collection ? $users : collect($users);
        $usersById = $usersCollection->keyBy('id');

        $baselineAssigneeIds = $task->assignee_id ? [(int) $task->assignee_id] : [];
        $storedAssigneeIds = [];
        $fallbackNames = [];

        if ($task->assigned_to) {
          $decoded = null;
          if (is_string($task->assigned_to)) {
            $decoded = json_decode($task->assigned_to, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
              $decoded = null;
            }
          } elseif (is_array($task->assigned_to)) {
            $decoded = $task->assigned_to;
          }

          if (is_array($decoded)) {
            $storedAssigneeIds = array_values(array_unique(array_map(fn($val) => is_numeric($val) ? (int) $val : null, array_filter($decoded, fn($v) => $v !== null && $v !== ''))));
          } else {
            $fallbackNames = array_map('trim', array_filter(explode(',', (string) $task->assigned_to), fn($name) => trim($name) !== ''));
          }
        }

        $baselineAssigneeIds = array_values(array_unique(array_merge($baselineAssigneeIds, $storedAssigneeIds)));

        $baselineAssigneeNames = [];
        foreach ($baselineAssigneeIds as $assigneeId) {
          $user = $usersById->get($assigneeId);
          if ($user) {
            $baselineAssigneeNames[] = $user->display_name ?? $user->email ?? ('User #' . $assigneeId);
          }
        }

        if (!empty($fallbackNames)) {
          $baselineAssigneeNames = array_merge($baselineAssigneeNames, $fallbackNames);
        }

        $baselineAssigneeNames = array_values(array_unique(array_filter($baselineAssigneeNames)));
        $initialRequester = optional($task->requester)->display_name ?? optional($task->requester)->email;
        $baselineSummary = [
          'Prioritas' => $initialPriority ?? '—',
          'Status' => $initialStatusLabel ?? '—',
          'Due Date' => $initialDueDate ?? '—',
          'Due Time' => $initialDueTime ?? '—',
          'Assignee' => !empty($baselineAssigneeNames) ? implode(', ', $baselineAssigneeNames) : '—',
          'Requester' => $initialRequester ?? '—',
        ];
        @endphp
        <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-6 flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                <path d="M12 1C6.48 1 2 5.48 2 11s4.48 10 10 10 10-4.48 10-10S17.52 1 12 1zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm-2-13h4v6h-4z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Data Task</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Prioritas, status, dan assignment</p>
            </div>
          </div>

          <div class="mb-6 rounded-xl border border-emerald-200/70 bg-emerald-50/60 p-4 text-sm text-emerald-900 dark:border-emerald-700/60 dark:bg-emerald-900/20 dark:text-emerald-200">
            <div class="mb-2 flex items-center gap-2 text-emerald-700 dark:text-emerald-200">
              <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">i</span>
              <span class="font-semibold">Data bawaan task</span>
            </div>
            <dl class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
              @foreach ($baselineSummary as $label => $value)
              <div>
                <dt class="text-[11px] uppercase tracking-wide text-emerald-500/80">{{ $label }}</dt>
                <dd class="font-medium text-emerald-900 dark:text-emerald-100">{{ $value ?: '—' }}</dd>
              </div>
              @endforeach
            </dl>
            <p class="mt-3 text-xs text-emerald-600/80 dark:text-emerald-200/70">Jika Anda mengubah nilai di bawah ini, indikator akan menunjukkan perubahan dibanding data awal.</p>
          </div>

          <div class="grid gap-6 lg:grid-cols-2">
            {{-- Prioritas --}}
            <div class="field-change-wrap" data-indicator-wrapper="priority">
              <label for="priority" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Prioritas</label>
              <select id="priority" name="priority" data-indicator-key="priority" data-initial-value="{{ $task->priority ?? '' }}" data-initial-display="{{ $initialPriority ?? '—' }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/40">
                <option value="">-- Pilih --</option>
                <option value="low" @selected(old('priority', $task->priority) === 'low')>Low</option>
                <option value="normal" @selected(old('priority', $task->priority) === 'normal')>Normal</option>
                <option value="high" @selected(old('priority', $task->priority) === 'high')>High</option>
                <option value="critical" @selected(old('priority', $task->priority) === 'critical')>Critical</option>
              </select>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 field-indicator" data-field-indicator="priority" data-initial-value="{{ $task->priority ?? '' }}" data-initial-display="{{ $initialPriority ?? '—' }}">Data awal: {{ $initialPriority ?? '—' }}</p>
              @error('priority')
              <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>

            {{-- Status --}}
            <div class="field-change-wrap" data-indicator-wrapper="status">
              <label for="status" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status *</label>
              <select id="status" name="status" data-indicator-key="status" data-initial-value="{{ \App\Support\WorkflowStatus::normalize($task->status) }}" data-initial-display="{{ $initialStatusLabel ?? '—' }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/40">
                @php
                $statusLabels = collect(\App\Support\WorkflowStatus::labels());
                $statusOptions = $statusLabels->keys()->all();
                $currentStatus = \App\Support\WorkflowStatus::normalize($task->status);
                $defaultStatus = \App\Support\WorkflowStatus::normalize(old('status', $currentStatus));
                @endphp
                @foreach ($statusOptions as $s)
                <option value="{{ $s }}" @selected($defaultStatus===$s)>
                  {{ $statusLabels[$s] ?? \App\Support\WorkflowStatus::label($s) }}
                </option>
                @endforeach
              </select>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 field-indicator" data-field-indicator="status" data-initial-value="{{ \App\Support\WorkflowStatus::normalize($task->status) }}" data-initial-display="{{ $initialStatusLabel ?? '—' }}">Data awal: {{ $initialStatusLabel ?? '—' }}</p>
              @error('status')
              <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>

            {{-- Due Date --}}
            <div class="field-change-wrap" data-indicator-wrapper="due_date">
              <label for="due_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
              @php
              $dueDate = old('due_date', $initialDueDate);
              @endphp
              <input id="due_date" name="due_date" type="text" value="{{ $dueDate }}" placeholder="dd/mm/yyyy" autocomplete="off" data-indicator-key="due_date" data-initial-value="{{ $initialDueDate ?? '' }}" data-initial-display="{{ $initialDueDate ?? '—' }}"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/40">
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 field-indicator" data-field-indicator="due_date" data-initial-value="{{ $initialDueDate ?? '' }}" data-initial-display="{{ $initialDueDate ?? '—' }}">Data awal: {{ $initialDueDate ?? '—' }}</p>
              @error('due_date')
              <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>

            {{-- Due Time --}}
            <div class="field-change-wrap" data-indicator-wrapper="due_time">
              <label for="due_time" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Due Time</label>
              @php
              $dueTime = old('due_time', $initialDueTime);
              @endphp
              <input id="due_time" name="due_time" type="text" value="{{ $dueTime }}" placeholder="hh:mm" autocomplete="off" data-indicator-key="due_time" data-initial-value="{{ $initialDueTime ?? '' }}" data-initial-display="{{ $initialDueTime ?? '—' }}"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/40">
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 field-indicator" data-field-indicator="due_time" data-initial-value="{{ $initialDueTime ?? '' }}" data-initial-display="{{ $initialDueTime ?? '—' }}">Data awal: {{ $initialDueTime ?? '—' }}</p>
              @error('due_time')
              <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>

            {{-- Assignees (single sumber data dari assignee_id saat ini) --}}
            <div class="lg:col-span-2 field-change-wrap" data-indicator-wrapper="assignees">
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Assign ke User (Bisa pilih lebih dari 1)</label>
              @php
              $selectedAssignees = $baselineAssigneeIds;
              $oldAssignees = old('assignees');
              if (is_string($oldAssignees) && $oldAssignees !== '') {
              try {
              $decoded = json_decode($oldAssignees, true, 512, JSON_THROW_ON_ERROR);
              if (is_array($decoded)) { $selectedAssignees = array_map('intval', array_filter($decoded, fn($v) => $v !== null && $v !== '')); }
              } catch (\Throwable) {}
              } elseif (is_array($oldAssignees)) {
              $selectedAssignees = array_map('intval', array_filter($oldAssignees, fn($v) => $v !== null && $v !== ''));
              }
              $selectedAssignees = array_values(array_unique($selectedAssignees));
              @endphp
              <select id="taskAssigneesSelect" class="task-assignee-picker w-full" multiple size="6">
                @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(in_array($user->id, $selectedAssignees))>{{ $user->display_name ?? $user->email }}</option>
                @endforeach
              </select>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Gunakan CTRL/Shift untuk pilih lebih dari satu</p>
              <div id="taskAssigneePills" class="mt-3 flex flex-wrap gap-2"></div>
              <div id="taskAssigneesWrap" class="mt-3 space-y-2"></div>
              <input type="hidden" id="assigneesInput" name="assignees" value='@json($selectedAssignees)' data-indicator-key="assignees" data-initial-value='@json($baselineAssigneeIds)' data-initial-display="{{ !empty($baselineAssigneeNames) ? implode(', ', $baselineAssigneeNames) : '—' }}">
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 field-indicator" data-field-indicator="assignees" data-initial-value='@json($baselineAssigneeIds)' data-initial-display="{{ !empty($baselineAssigneeNames) ? implode(', ', $baselineAssigneeNames) : '—' }}">Data awal: {{ !empty($baselineAssigneeNames) ? implode(', ', $baselineAssigneeNames) : '—' }}</p>
              @error('assignees')
              <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>

            {{-- Requester (Admin only) --}}
            @if(auth()->user() && (auth()->user()->hasAnyRole(['superadmin', 'Super Admin']) || auth()->user()->hasRole('Admin')))
            <div class="lg:col-span-2 field-change-wrap" data-indicator-wrapper="requester">
              <label for="requester_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Requester</label>
              @php
              $requesterId = old('requester_id', $task->created_by ?? null);
              @endphp
              <select id="requester_id" name="requester_id" data-indicator-key="requester" data-initial-value="{{ $task->created_by ?? '' }}" data-initial-display="{{ $initialRequester ?? '—' }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 shadow-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/40">
                <option value="">-- Pilih Requester --</option>
                @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected($requesterId==$user->id)>
                  {{ $user->display_name ?? $user->email }}
                </option>
                @endforeach
              </select>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 field-indicator" data-field-indicator="requester" data-initial-value="{{ $task->created_by ?? '' }}" data-initial-display="{{ $initialRequester ?? '—' }}">Data awal: {{ $initialRequester ?? '—' }}</p>
              @error('requester_id')
              <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
              @enderror
            </div>
            @endif
          </div>
        </div>

        {{-- SECTION 3: LAMPIRAN --}}
        @include('partials.attachments', [
        'initialAttachments' => $task->attachments ?? collect(),
        'toggleDefault' => true,
        'inputId' => 'task-attachments'
        ])

        {{-- SECTION 4: ACTION BUTTONS --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Fields marked with <span class="font-semibold text-red-600">*</span> are required
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <a href="{{ $backTo }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800/60">
              Batal
            </a>
            <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white shadow-md hover:bg-blue-700 dark:hover:bg-blue-700">
              Simpan Perubahan
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
  .page-theme--task {
    --brandA: #10b981;
    --brandB: #14b8a6;
  }

  html {
    scroll-behavior: smooth;
  }

  .page-shell {
    min-height: 100vh;
    overflow-y: auto;
  }

  /* Styling Quill editor */
  .quill-wrapper {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 100%;
    min-height: 300px;
    border: 1px solid rgba(209, 213, 219, 1);
    border-radius: .75rem;
    background: white;
    overflow: hidden;
    box-sizing: border-box;
  }

  .quill-wrapper * {
    box-sizing: border-box;
  }

  .quill-wrapper .ql-toolbar.ql-snow {
    border: none !important;
    border-bottom: 1px solid rgba(148, 163, 184, .3) !important;
    background: white;
    display: flex;
    flex-wrap: wrap;
    gap: .25rem;
    padding: .5rem;
    width: 100%;
  }

  .quill-wrapper .ql-container.ql-snow {
    border: none !important;
    flex: 1;
    min-height: 220px;
    background: white;
    display: flex;
  }

  .quill-wrapper .ql-editor {
    flex: 1;
    padding: 1rem;
    font-size: 1rem;
    color: #111827;
    overflow-y: auto;
  }

  .quill-wrapper .ql-editor.ql-blank::before {
    color: #d1d5db;
  }

  .dark .quill-wrapper {
    background: #111827;
    border-color: rgba(55, 65, 81, 1);
  }

  .dark .quill-wrapper .ql-toolbar.ql-snow {
    background: #1f2937;
    border-bottom-color: rgba(55, 65, 81, 1) !important;
  }

  .dark .quill-wrapper .ql-container.ql-snow,
  .dark .quill-wrapper .ql-editor {
    background: #111827;
    color: #f3f4f6;
  }

  .task-assignee-picker {
    padding: .5rem;
    font-size: .875rem;
  }

  #taskAssigneePills {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
  }

  .task-pill {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .375rem .75rem;
    background: #e0e7ff;
    border-radius: 9999px;
    font-size: .875rem;
    font-weight: 500;
    color: #3730a3
  }

  .task-pill .btn-remove-pill {
    margin-left: .25rem;
    padding: 0;
    border: none;
    background: none;
    cursor: pointer;
    opacity: .7;
    transition: opacity .2s;
    color: inherit
  }

  .task-pill .btn-remove-pill:hover {
    opacity: 1
  }

  .dark .task-pill {
    background: #3730a3;
    color: #e0e7ff
  }

  .field-change-wrap {
    transition: border-color .25s ease, box-shadow .25s ease;
  }

  .field-change-wrap.is-changed input,
  .field-change-wrap.is-changed select,
  .field-change-wrap.is-changed .task-assignee-picker {
    border-color: rgba(249, 115, 22, .65) !important;
    box-shadow: 0 0 0 1px rgba(249, 115, 22, .25);
  }

  .field-indicator {
    display: block;
    font-size: .75rem;
    color: #64748b;
  }

  .dark .field-indicator {
    color: rgba(148, 163, 184, .8);
  }

  .field-indicator.is-changed {
    color: #ea580c;
    font-weight: 600;
  }

  .dark .field-indicator.is-changed {
    color: #fbbf24;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js"></script>
<script>
  const FieldIndicators = (() => {
    const indicators = {};

    const parseValue = (raw) => {
      if (raw === undefined || raw === null) return '';
      if (Array.isArray(raw) || typeof raw === 'object') return raw;
      const str = String(raw).trim();
      if (!str.length) return '';
      const startsJson = (str.startsWith('[') && str.endsWith(']')) || (str.startsWith('{') && str.endsWith('}'));
      if (startsJson) {
        try {
          return JSON.parse(str);
        } catch (e) {
          return str;
        }
      }
      return str;
    };

    const normalizeValue = (value) => {
      if (Array.isArray(value)) {
        return JSON.stringify(value.map(v => String(v)).sort());
      }
      if (value === null || value === undefined) return '';
      return String(value).trim();
    };

    const formatDisplay = (value) => {
      if (Array.isArray(value)) {
        if (!value.length) return '—';
        return value.join(', ');
      }
      if (value === null || value === undefined) return '—';
      const str = String(value).trim();
      return str.length ? str : '—';
    };

    document.querySelectorAll('.field-indicator').forEach(el => {
      const key = el.dataset.fieldIndicator;
      if (!key) return;
      const initialValue = parseValue(el.dataset.initialValue ?? '');
      const initialDisplay = el.dataset.initialDisplay ?? formatDisplay(initialValue);
      indicators[key] = {
        element: el,
        wrapper: document.querySelector(`[data-indicator-wrapper="${key}"]`),
        initialValue,
        initialDisplay,
        initialNormalized: normalizeValue(initialValue)
      };
      el.textContent = initialDisplay ? `Data awal: ${initialDisplay}` : 'Data awal: —';
    });

    const update = (key, rawValue, displayOverride) => {
      const data = indicators[key];
      if (!data) return;
      const normalizedCurrent = normalizeValue(rawValue);
      const displayValue = displayOverride !== undefined ? (displayOverride || '—') : formatDisplay(rawValue);
      if (normalizedCurrent === data.initialNormalized) {
        data.element.textContent = data.initialDisplay ? `Data awal: ${data.initialDisplay}` : 'Data awal: —';
        data.element.classList.remove('is-changed');
        data.wrapper?.classList.remove('is-changed');
      } else {
        data.element.textContent = `Diubah dari ${data.initialDisplay || '—'} → ${displayValue || '—'}`;
        data.element.classList.add('is-changed');
        data.wrapper?.classList.add('is-changed');
      }
    };

    const bind = (el) => {
      const key = el.dataset.indicatorKey;
      if (!key || !indicators[key]) return;
      const isSelect = el.tagName === 'SELECT';
      const isMulti = isSelect && el.multiple;
      const isTextInput = el.tagName === 'INPUT' && el.type === 'text';
      const isHidden = el.type === 'hidden';

      const getRawValue = () => {
        if (isMulti) {
          return Array.from(el.selectedOptions).map(opt => opt.value);
        }
        if (isSelect) {
          return el.value;
        }
        if (isHidden) {
          return parseValue(el.value);
        }
        return el.value;
      };

      const getDisplay = () => {
        if (isMulti) {
          return Array.from(el.selectedOptions).map(opt => opt.textContent.trim()).join(', ');
        }
        if (isSelect) {
          const opt = el.selectedOptions[0];
          return opt ? opt.textContent.trim() : '';
        }
        if (isHidden) {
          const raw = parseValue(el.value);
          return formatDisplay(raw);
        }
        return el.value;
      };

      const handler = () => update(key, getRawValue(), getDisplay());
      handler();
      if (!isHidden) {
        el.addEventListener('change', handler);
        if (isTextInput) {
          el.addEventListener('input', handler);
        }
      }
    };

    const init = () => {
      document.querySelectorAll('[data-indicator-key]').forEach(bind);
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
      init();
    }

    return {
      updateArray(key, values, labels) {
        const list = Array.isArray(values) ? values.slice() : [];
        const labelText = Array.isArray(labels) ? labels.filter(Boolean).join(', ') : undefined;
        update(key, list, labelText);
      },
      parseValue,
      normalizeValue
    };
  })();

  document.addEventListener('DOMContentLoaded', function() {
    if (window.flatpickr) {
      window.flatpickr('#due_date', {
        dateFormat: 'd/m/Y',
        allowInput: true
      });
    }
  });

  // Task Assignees management
  (function() {
    const selectEl = document.getElementById('taskAssigneesSelect');
    const pillsEl = document.getElementById('taskAssigneePills');
    const wrapEl = document.getElementById('taskAssigneesWrap');
    const inputEl = document.getElementById('assigneesInput');

    let selectedIds = [];
    try {
      const jsonVal = inputEl?.value;
      if (jsonVal && jsonVal !== '[]') {
        const parsed = JSON.parse(jsonVal);
        if (Array.isArray(parsed)) {
          selectedIds = parsed.map(Number).filter(v => !Number.isNaN(v));
        }
      }
    } catch (e) {
      selectedIds = [];
    }

    const getLabel = (userId) => {
      const opt = selectEl ? Array.from(selectEl.options).find(o => parseInt(o.value) === userId) : null;
      return opt ? opt.textContent.trim() : `User #${userId}`;
    };

    function updateUI() {
      if (selectEl) {
        Array.from(selectEl.options).forEach(opt => {
          opt.selected = selectedIds.includes(parseInt(opt.value));
        });
      }
      renderPills();
      renderCards();
      inputEl.value = JSON.stringify(selectedIds);
      const labels = selectedIds.map(getLabel);
      FieldIndicators.updateArray('assignees', selectedIds, labels);
    }

    function renderPills() {
      pillsEl.innerHTML = selectedIds.map(userId => {
        const label = getLabel(userId);
        return `<div class="task-pill"><span>${label}</span><button type="button" class="btn-remove-pill" data-user-id="${userId}">✕</button></div>`;
      }).join('');
      pillsEl.querySelectorAll('.btn-remove-pill').forEach(btn => {
        btn.addEventListener('click', e => {
          e.preventDefault();
          const id = parseInt(btn.dataset.userId);
          selectedIds = selectedIds.filter(v => v !== id);
          updateUI();
        });
      });
    }

    function renderCards() {
      wrapEl.innerHTML = selectedIds.map(userId => {
        const label = getLabel(userId);
        return `
          <div class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/30">
            <div class="text-sm font-medium text-blue-900 dark:text-blue-100">${label}</div>
            <button type="button" class="btn-remove-card inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-100 dark:text-red-300 dark:hover:bg-red-900/30" data-user-id="${userId}">
              Hapus
            </button>
          </div>
        `;
      }).join('');
      wrapEl.querySelectorAll('.btn-remove-card').forEach(btn => {
        btn.addEventListener('click', e => {
          e.preventDefault();
          const id = parseInt(btn.dataset.userId);
          selectedIds = selectedIds.filter(v => v !== id);
          updateUI();
        });
      });
    }

    if (selectEl) {
      selectEl.addEventListener('change', () => {
        selectedIds = Array.from(selectEl.selectedOptions).map(opt => parseInt(opt.value));
        updateUI();
      });
    }

    updateUI();
  })();
</script>
@endpush
