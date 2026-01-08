@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
@php
$originFrom = request('from');
$backTo = request('src') === 'detail'
    ? route('projects.show', ['project' => $project->public_slug ?? $project->id, 'from' => $originFrom])
    : ($originFrom ?: (url()->previous() ?: route('projects.report')));

$statusValue = $project->status instanceof \BackedEnum ? $project->status->value : ($project->status ?? '');
$statusValue = \App\Support\WorkflowStatus::normalize($statusValue);
$userMap = collect($users ?? [])->mapWithKeys(fn ($user) => [
    $user->id => $user->label ?? ($user->name ?? $user->email),
])->toArray();
@endphp

<div class="page-theme page-theme--project">
  <div class="page-shell page-shell--wide py-6">
    <div class="space-y-8">
      <div class="flex justify-center md:justify-start">
        @include('components.back', ['to' => $backTo])
      </div>

      <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 p-6 text-white shadow-xl">
        <div class="relative z-[1] flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-3xl font-semibold">Edit Project</h2>
            <p class="mt-2 max-w-2xl text-sm text-white/80">Perbarui informasi project, data tim, action plan, budget, dan deliverables dengan mudah.</p>
          </div>
          <div class="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 text-sm backdrop-blur">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-6 w-6">
                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Z" />
                <path d="M20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83Z" />
              </svg>
            </div>
            <div>
              <div class="font-semibold">Project: {{ $project->project_no ?? 'PRJ-' . $project->id }}</div>
              <p class="text-xs text-white/80">Last updated: {{ $project->updated_at?->translatedFormat('d M Y H:i') ?? '—' }}</p>
            </div>
          </div>
        </div>
        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
      </div>

      @if ($errors->any())
      <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-700 dark:border-red-700 dark:bg-red-900/20 dark:text-red-200">
        <div class="mb-2 font-semibold">Periksa kembali beberapa isian:</div>
        <ul class="list-disc list-inside space-y-1 text-sm">
          @foreach ($errors->all() as $message)
          <li>{{ $message }}</li>
          @endforeach
        </ul>
      </div>
      @endif

      <form
        id="projectEditForm"
        data-user-map="{{ json_encode($userMap) }}"
        action="{{ route('projects.update', $project) }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-8"
        data-impact-options='@json(array_values($impactOptions ?? []))'
        data-likelihood-options='@json(array_values($likelihoodOptions ?? []))'
        data-verified-options='@json(array_values($verifiedByOptions ?? []))'>
        @csrf
        @method('PUT')

        @if(!empty($originFrom))
            <input type="hidden" name="from" value="{{ $originFrom }}">
        @endif

        <!-- SECTION 1: PROJECT OVERVIEW -->
        <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-6 flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Informasi Utama Project</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Data dasar dan identitas project</p>
            </div>
          </div>

          <div class="grid gap-6 lg:grid-cols-2">
            <!-- Title -->
            <div class="lg:col-span-2">
              <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">1</span>
                Judul Project
              </label>
              <input id="title" type="text" name="title"
                value="{{ old('title', $project->title) }}"
                required
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 shadow-sm focus:border-blue-400 focus:ring-blue-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                placeholder="Contoh: Implementasi Dashboard Analytics">
              @error('title') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <!-- Project No & Status Side by Side -->
            <div>
              <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">2</span>
                Nomor Project
              </label>
              <input type="text" name="project_no"
                value="{{ old('project_no', $project->project_no) }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:ring-blue-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                maxlength="20"
                placeholder="PRJ-2025-001">
              @error('project_no')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
              <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">3</span>
                Status Workflow
              </label>
              @php $cur = old('status', $statusValue); @endphp
              <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:ring-blue-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                <option value="new" {{ $cur === 'new' ? 'selected' : '' }}>New</option>
                <option value="in_progress" {{ $cur === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="confirmation" {{ $cur === 'confirmation' ? 'selected' : '' }}>Confirmation</option>
                <option value="revision" {{ $cur === 'revision' ? 'selected' : '' }}>Revision</option>
                <option value="done" {{ $cur === 'done' ? 'selected' : '' }}>Done</option>
              </select>
              @error('status')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <!-- Ticket -->
            <div class="lg:col-span-2">
              <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span class="flex h-5 w-5 items-center justify-center rounded bg-blue-100 text-xs font-bold text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">4</span>
                Ticket Terkait (Opsional)
              </label>
              <select name="ticket_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:ring-blue-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">— Tidak diubah —</option>
                @foreach ($tickets as $t)
                    <option value="{{ $t->id }}" {{ (string) old('ticket_id', $project->ticket_id) === (string) $t->id ? 'selected' : '' }}>
                      {{ $t->ticket_no ?? 'Ticket #' . $t->id }} — {{ $t->title ? \Illuminate\Support\Str::limit($t->title, 50) : '—' }}
                    </option>
                @endforeach
              </select>
              @error('ticket_id')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
          </div>
        </div>

        <!-- SECTION 2: DESCRIPTION & TIMELINE -->
        <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-6 flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-300">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5.04-6.71l-2.75 3.54-2.08-2.4-2.59 3.57h10.78L13.96 12.29z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Deskripsi & Timeline</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Detail project dan periode pelaksanaan</p>
            </div>
          </div>

          <div class="space-y-6">
            <!-- Description -->
            <div>
              <label class="mb-2 flex items-center justify-between">
                <span class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                  <span class="flex h-5 w-5 items-center justify-center rounded bg-purple-100 text-xs font-bold text-purple-600 dark:bg-purple-900/30 dark:text-purple-300">5</span>
                  Deskripsi Project
                </span>
                <span id="projDescCounter" class="rounded-full bg-purple-50 px-3 py-1 text-xs font-semibold text-purple-600 dark:bg-purple-900/20 dark:text-purple-200">0 kata</span>
              </label>
              <div id="projectDescWrap" class="ql-card rounded-lg border border-gray-200 shadow-inner dark:border-gray-700">
                <div id="project_desc_editor" class="min-h-[180px] rounded-lg bg-white dark:bg-gray-900"></div>
              </div>
              <textarea id="description" name="description" class="hidden">{{ old('description', $project->description) }}</textarea>
              @error('description')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <!-- Timeline -->
            <div>
              <label class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span class="flex h-5 w-5 items-center justify-center rounded bg-purple-100 text-xs font-bold text-purple-600 dark:bg-purple-900/30 dark:text-purple-300">6</span>
                Timeline Pelaksanaan
              </label>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tanggal Mulai</label>
                  <input type="text" name="start_date" id="project_start_date"
                    value="{{ old('start_date', optional($project->start_date)->format('d/m/Y')) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-purple-400 focus:ring-purple-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                    placeholder="dd/mm/yyyy">
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tanggal Selesai</label>
                  <input type="text" name="end_date" id="project_end_date"
                    value="{{ old('end_date', optional($project->end_date)->format('d/m/Y')) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-purple-400 focus:ring-purple-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                    placeholder="dd/mm/yyyy">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 3: TEAM PROJECT -->
        <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-6 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
                  <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Tim Project</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Anggota tim dan peran mereka</p>
              </div>
            </div>
            <button type="button" id="btnAddMember" class="inline-flex items-center gap-2 rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-600 dark:bg-emerald-600 dark:hover:bg-emerald-700">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-4 w-4">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
              </svg>
              Tambah Anggota
            </button>
          </div>

          <div id="teamPills" class="mb-4 flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-300"></div>
          
          @php $memberData = old('project_pics', collect($project->pics ?? [])->map(fn($p) => ['user_id' => $p->user_id, 'position' => $p->position ?? ''])->values()->toArray()); @endphp
          <div id="membersWrap" class="space-y-3" data-old="{{ json_encode($memberData) }}"></div>

          <template id="memberRowTpl">
            <div class="flex flex-col gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/20 md:flex-row md:items-end md:gap-4">
              <input type="hidden" name="project_pics[__INDEX__][user_id]" value="">
              <div class="flex-1 md:flex-none md:min-w-48">
                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">User</label>
                <div class="user-label rounded-lg border border-emerald-300 bg-white px-3 py-2.5 text-sm font-medium text-emerald-700 dark:border-emerald-700 dark:bg-gray-800 dark:text-emerald-300">-</div>
              </div>
              <div class="flex-1">
                <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Peran</label>
                <input type="text" name="project_pics[__INDEX__][position]" placeholder="Contoh: Project Manager"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-emerald-400 focus:ring-emerald-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
              </div>
              <button type="button" class="btn-remove-memberRowTpl inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-4 w-4">
                  <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" />
                </svg>
                Hapus
              </button>
            </div>
          </template>
        </div>

        <!-- SECTION 4: ACTION PLAN -->
        <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-6 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Action Plan</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Aktivitas dan milestone project</p>
              </div>
            </div>
            <button type="button" id="btnAddAction" class="inline-flex items-center gap-2 rounded-full bg-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-600 dark:bg-indigo-600 dark:hover:bg-indigo-700">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-4 w-4">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
              </svg>
              Tambah Action
            </button>
          </div>

          @php $actionData = old('project_actions', collect($project->actions ?? [])->map(fn($a) => ['id' => $a->id, 'title' => $a->title, 'status_id' => $a->status_id, 'progress' => $a->progress ?? 0, 'start_date' => $a->start_date?->format('d/m/Y'), 'end_date' => $a->end_date?->format('d/m/Y'), 'description' => $a->description])->values()->toArray()); @endphp
          <div id="actionsWrap" class="space-y-4" data-old="{{ json_encode($actionData) }}"></div>

          <template id="actionTpl">
            <div class="action-card rounded-xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm dark:border-indigo-800 dark:bg-indigo-900/20" data-action="__INDEX__">
              <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                    <span class="action-order">1</span>
                  </span>
                  <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Action</span>
                </div>
                <button type="button" class="btn-remove-actionTpl inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">Hapus</button>
              </div>
              <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Judul Action</label>
                  <input type="text" name="project_actions[__INDEX__][title]" placeholder="Contoh: Workshop Implementasi"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Status</label>
                  <select name="project_actions[__INDEX__][status_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">-- Pilih status --</option>
                    @foreach ($statuses as $status)
                    <option value="{{ $status->id }}">{{ $status->name ?? $status->id }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Progress (%)</label>
                  <input type="number" name="project_actions[__INDEX__][progress]" min="0" max="100" placeholder="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div class="md:col-span-2 grid gap-4 sm:grid-cols-2">
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Mulai</label>
                    <input type="text" name="project_actions[__INDEX__][start_date]" placeholder="dd/mm/yyyy"
                      class="action-start-date w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Selesai</label>
                    <input type="text" name="project_actions[__INDEX__][end_date]" placeholder="dd/mm/yyyy"
                      class="action-end-date w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                </div>
                <div class="md:col-span-2">
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Deskripsi</label>
                  <textarea name="project_actions[__INDEX__][description]" rows="3" placeholder="Penjelasan detail action..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- SECTION 5: RINCIAN BUDGET -->
        <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-6 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm0-13c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Rincian Budget</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Item biaya, estimasi, dan realisasi</p>
              </div>
            </div>
            <button type="button" id="btnAddCost" class="inline-flex items-center gap-2 rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-4 w-4">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
              </svg>
              Tambah Biaya
            </button>
          </div>

          @php $costData = old('project_costs', collect($project->costs ?? [])->map(fn($c) => ['id' => $c->id, 'cost_item' => $c->cost_item, 'category' => $c->category, 'estimated_cost' => $c->estimated_cost, 'actual_cost' => $c->actual_cost, 'notes' => $c->notes ?? ''])->values()->toArray()); @endphp
          <div id="costsWrap" class="space-y-4" data-old="{{ json_encode($costData) }}"></div>

          <template id="costTpl">
            <div class="cost-card rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm dark:border-amber-800 dark:bg-amber-900/20" data-cost="__INDEX__">
              <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-600 text-xs font-bold text-white">
                    <span class="cost-order">1</span>
                  </span>
                  <span class="text-sm font-semibold text-amber-700 dark:text-amber-300">Biaya</span>
                </div>
                <button type="button" class="btn-remove-costTpl inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">Hapus</button>
              </div>
              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Item Biaya</label>
                  <input type="text" name="project_costs[__INDEX__][cost_item]" placeholder="Contoh: Hardware"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Kategori</label>
                  <input type="text" name="project_costs[__INDEX__][category]" placeholder="Contoh: Infrastructure"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Estimasi</label>
                  <input type="number" step="0.01" min="0" name="project_costs[__INDEX__][estimated_cost]" placeholder="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Aktual</label>
                  <input type="number" step="0.01" min="0" name="project_costs[__INDEX__][actual_cost]" placeholder="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div class="md:col-span-2">
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Catatan</label>
                  <textarea name="project_costs[__INDEX__][notes]" rows="2" placeholder="Catatan tambahan..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- SECTION 6: MITIGASI RESIKO -->
        <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-6 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
                  <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Mitigasi Resiko</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Identifikasi dan rencana penanganan risiko</p>
              </div>
            </div>
            <button type="button" id="btnAddRisk" class="inline-flex items-center gap-2 rounded-full bg-rose-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-rose-600 dark:bg-rose-600 dark:hover:bg-rose-700">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-4 w-4">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
              </svg>
              Tambah Risiko
            </button>
          </div>

          @php $riskData = old('project_risks', collect($project->risks ?? [])->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'status_id' => $r->status_id, 'impact' => $r->impact, 'likelihood' => $r->likelihood, 'description' => $r->description, 'mitigation_plan' => $r->mitigation_plan])->values()->toArray()); @endphp
          <div id="risksWrap" class="space-y-4" data-old="{{ json_encode($riskData) }}"></div>

          <template id="riskTpl">
            <div class="risk-card rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm dark:border-rose-800 dark:bg-rose-900/20" data-risk="__INDEX__">
              <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="flex h-6 w-6 items-center justify-center rounded-full bg-rose-600 text-xs font-bold text-white">
                    <span class="risk-order">1</span>
                  </span>
                  <span class="text-sm font-semibold text-rose-700 dark:text-rose-300">Risiko</span>
                </div>
                <button type="button" class="btn-remove-riskTpl inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">Hapus</button>
              </div>
              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Nama Risiko</label>
                  <input type="text" name="project_risks[__INDEX__][name]" placeholder="Contoh: Delay supplier"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Status</label>
                  <select name="project_risks[__INDEX__][status_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">-- Pilih status --</option>
                    @foreach ($statuses as $status)
                    <option value="{{ $status->id }}">{{ $status->name ?? $status->id }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Dampak</label>
                  <select name="project_risks[__INDEX__][impact]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">-- Pilih dampak --</option>
                    @foreach ($impactOptions as $impact)
                    <option value="{{ $impact }}">{{ ucfirst($impact) }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Kemungkinan</label>
                  <select name="project_risks[__INDEX__][likelihood]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">-- Pilih kemungkinan --</option>
                    @foreach ($likelihoodOptions as $likelihood)
                    <option value="{{ $likelihood }}">{{ ucwords(str_replace('_', ' ', $likelihood)) }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="md:col-span-2">
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Deskripsi</label>
                  <textarea name="project_risks[__INDEX__][description]" rows="2" placeholder="Jelaskan risiko tersebut..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
                <div class="md:col-span-2">
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Rencana Mitigasi</label>
                  <textarea name="project_risks[__INDEX__][mitigation_plan]" rows="2" placeholder="Langkah-langkah untuk mengurangi risiko..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- SECTION 7: DELIVERABLES -->
        <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-6 flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Deliverables</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Keluaran utama project</p>
              </div>
            </div>
            <button type="button" id="btnAddDeliverable" class="inline-flex items-center gap-2 rounded-full bg-teal-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-teal-600 dark:bg-teal-600 dark:hover:bg-teal-700">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-4 w-4">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
              </svg>
              Tambah Deliverable
            </button>
          </div>

          @php $deliverableData = old('project_deliverables', collect($project->deliverables ?? [])->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'status_id' => $d->status_id, 'completed_at' => $d->completed_at?->format('d/m/Y H:i'), 'verified_at' => $d->verified_at?->format('d/m/Y H:i'), 'verified_by' => $d->verified_by, 'description' => $d->description])->values()->toArray()); @endphp
          <div id="deliverablesWrap" class="space-y-4" data-old="{{ json_encode($deliverableData) }}"></div>

          <template id="deliverableTpl">
            <div class="deliverable-card rounded-xl border border-teal-200 bg-teal-50 p-5 shadow-sm dark:border-teal-800 dark:bg-teal-900/20" data-deliverable="__INDEX__">
              <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">
                    <span class="deliverable-order">1</span>
                  </span>
                  <span class="text-sm font-semibold text-teal-700 dark:text-teal-300">Deliverable</span>
                </div>
                <button type="button" class="btn-remove-deliverableTpl inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">Hapus</button>
              </div>
              <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Nama Deliverable</label>
                  <input type="text" name="project_deliverables[__INDEX__][name]" placeholder="Contoh: Dashboard Report"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Status</label>
                  <select name="project_deliverables[__INDEX__][status_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">-- Pilih status --</option>
                    @foreach ($statuses as $status)
                    <option value="{{ $status->id }}">{{ $status->name ?? $status->id }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Verified By</label>
                  <select name="project_deliverables[__INDEX__][verified_by]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">-- Pilih --</option>
                    @foreach ($verifiedByOptions as $option)
                    <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Selesai Pada</label>
                  <input type="text" name="project_deliverables[__INDEX__][completed_at]" placeholder="dd/mm/yyyy HH:mm"
                    class="deliverable-completed-at w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Terverifikasi Pada</label>
                  <input type="text" name="project_deliverables[__INDEX__][verified_at]" placeholder="dd/mm/yyyy HH:mm"
                    class="deliverable-verified-at w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div class="md:col-span-2">
                  <label class="mb-1 block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Deskripsi</label>
                  <textarea name="project_deliverables[__INDEX__][description]" rows="2" placeholder="Detail deliverable..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- SECTION 8: LAMPIRAN -->
        <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="mb-6 flex items-center gap-3 border-b border-gray-200 pb-4 dark:border-gray-700">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-900/30 dark:text-slate-300">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
                <path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2z"/>
                <path d="M11 3L5.5 8.5l1.42 1.41L11 5.83V15h2V5.83l4.08 4.08L18.5 8.5 12 2z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Lampiran & Dokumen</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">File pendukung project</p>
            </div>
          </div>

          @include('partials.attachments', [
            'initialAttachments' => collect($project->attachments ?? []),
            'toggleDefault' => true,
            'inputId' => 'project-attachments'
          ])
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
          <div></div>
          <div class="flex gap-3">
            <a href="{{ $backTo }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700/50">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-4 w-4">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" />
              </svg>
              Batal
            </a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-4 w-4">
                <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
              </svg>
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.snow.css">
<style>
  .page-theme--project {
    --brandA: #3b82f6;
    --brandB: #06b6d4;
  }

  .ql-card .ql-toolbar.ql-snow {
    border-radius: .75rem .75rem 0 0;
    border: none;
    border-bottom: 1px solid rgba(148, 163, 184, .3);
  }

  .ql-card .ql-container.ql-snow {
    border: none;
    border-radius: 0 0 .75rem .75rem;
    min-height: 180px;
  }

  .ql-card:focus-within {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, .15);
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('projectEditForm');
    const userMap = JSON.parse(form.getAttribute('data-user-map') || '{}');

    // Quill Editor
    const descWrap = document.getElementById('projectDescWrap');
    const descEditorEl = document.getElementById('project_desc_editor');
    const descHiddenEl = document.getElementById('description');

    if (descWrap && descEditorEl && descHiddenEl && typeof Quill !== 'undefined') {
        let quill = descEditorEl.__quill;
        if (!quill) {
            quill = new Quill(descEditorEl, {
                theme: 'snow',
                placeholder: 'Tulis detail project di sini…',
                modules: {
                    toolbar: [
                        [{ header: [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike', 'blockquote'],
                        [{ align: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
                        ['link', 'code-block', 'clean']
                    ]
                }
            });
            descEditorEl.__quill = quill;
        }

        if (descHiddenEl.value && descHiddenEl.value.trim()) {
            quill.clipboard.dangerouslyPasteHTML(descHiddenEl.value);
        }

        if (!descEditorEl.dataset.quillBound) {
            const sync = function () {
                descHiddenEl.value = quill.root.innerHTML;
                let text = quill.getText();
                if (text && text.slice(-1) === '\n') {
                    text = text.slice(0, -1);
                }
                const words = text && text.trim() ? text.trim().split(/\s+/).length : 0;
                const counter = document.getElementById('projDescCounter');
                if (counter) {
                    counter.textContent = words + ' kata';
                }
            };

            quill.on('text-change', sync);
            quill.on('selection-change', function (range) {
                const hasSelection = range !== null && range !== undefined;
                descWrap.classList.toggle('ring-2 ring-blue-400', hasSelection);
            });
            if (form) {
                form.addEventListener('submit', sync, { capture: true });
            }
            descEditorEl.dataset.quillBound = '1';
            sync();
        }
    }

    // Date Pickers
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#project_start_date, #project_end_date', {
            dateFormat: 'd/m/Y',
            allowInput: true,
        });
        flatpickr('.action-start-date, .action-end-date, .deliverable-completed-at, .deliverable-verified-at', {
            dateFormat: 'd/m/Y H:i',
            enableTime: true,
            allowInput: true,
            time_24hr: true,
        });
    }

    // Generic handlers for dynamic rows
    function setupRowHandler(btnId, wrapId, tplId, indexAttr) {
        const btn = document.getElementById(btnId);
        const wrap = document.getElementById(wrapId);
        const tpl = document.getElementById(tplId);
        if (!btn || !wrap || !tpl) return;

        let idx = 0;
        const data = wrap.dataset.old ? JSON.parse(wrap.dataset.old) : [];
        
        data.forEach((item, i) => {
            const html = tpl.innerHTML.replace(/__INDEX__/g, idx).replace(/__ACTION_INDEX__/g, idx);
            const div = document.createElement('div');
            div.innerHTML = html.trim();
            const row = div.firstElementChild;
            if (row) {
                Object.keys(item).forEach(key => {
                  if(key === 'id') return;
                    const selector = `[name*="[${idx}][${key}]"]`;
                    const field = row.querySelector(selector);
                    if (field) {
                        field.value = item[key] || '';
                    }
                });

                if (tplId === 'memberRowTpl' && item.user_id) {
                    const userLabelDiv = row.querySelector('.user-label');
                    if (userLabelDiv) {
                        userLabelDiv.textContent = userMap[item.user_id] || `User #${item.user_id}`;
                    }
                    const userIdInput = row.querySelector('input[name*="[user_id]"]');
                    if (userIdInput) {
                        userIdInput.value = item.user_id;
                    }
                }

                attachRowRemoveHandler(row, tplId);
                wrap.appendChild(row);
                idx++;
            }
        });

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const html = tpl.innerHTML.replace(/__INDEX__/g, idx).replace(/__ACTION_INDEX__/g, idx);
            const div = document.createElement('div');
            div.innerHTML = html.trim();
            const row = div.firstElementChild;
            if (row) {
                attachRowRemoveHandler(row, tplId);
                wrap.appendChild(row);
                idx++;
                updateOrders();
            }
        });
    }

    function attachRowRemoveHandler(row, tplId) {
        const removeBtn = row.querySelector('.btn-remove-' + tplId);
        if (removeBtn) {
            removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                row.remove();
                updateOrders();
            });
        }
    }

    function updateOrders() {
        document.querySelectorAll('.action-order').forEach((el, i) => el.textContent = i + 1);
        document.querySelectorAll('.cost-order').forEach((el, i) => el.textContent = i + 1);
        document.querySelectorAll('.risk-order').forEach((el, i) => el.textContent = i + 1);
        document.querySelectorAll('.deliverable-order').forEach((el, i) => el.textContent = i + 1);
    }

    setupRowHandler('btnAddMember', 'membersWrap', 'memberRowTpl', 'data-member');
    setupRowHandler('btnAddAction', 'actionsWrap', 'actionTpl', 'data-action');
    setupRowHandler('btnAddCost', 'costsWrap', 'costTpl', 'data-cost');
    setupRowHandler('btnAddRisk', 'risksWrap', 'riskTpl', 'data-risk');
    setupRowHandler('btnAddDeliverable', 'deliverablesWrap', 'deliverableTpl', 'data-deliverable');

    updateOrders();
});
</script>
@endpush
