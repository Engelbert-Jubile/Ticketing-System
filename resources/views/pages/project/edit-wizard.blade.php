{{-- resources/views/pages/project/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
@php
$originFrom = request('from');
$backTo = request('src') === 'detail'
    ? route('projects.show', ['project' => $project->public_slug ?? $project->id, 'from' => $originFrom])
    : ($originFrom ?: (url()->previous() ?: route('projects.report')));

$defaultStatus = \App\Support\WorkflowStatus::normalize($project->status ?? 'new');
$defaultStatusCode = \App\Support\WorkflowStatus::code($defaultStatus);
$oldPics = collect($project->pics ?? [])->map(fn($p) => [
    'user_id' => $p->user_id,
    'position' => $p->position ?? '',
])->values()->toArray();
$oldActions = collect($project->actions ?? [])->map(fn($a) => [
    'title' => $a->title,
    'status_id' => $a->status_id,
    'progress' => $a->progress ?? 0,
    'pic_user_id' => $a->pic_user_id,
    'start_date' => $a->start_date,
    'end_date' => $a->end_date,
    'description' => $a->description,
    'subactions' => collect($a->subactions ?? [])->map(fn($s) => [
        'title' => $s->title,
        'status_id' => $s->status_id,
        'progress' => $s->progress ?? 0,
        'pic_user_id' => $s->pic_user_id,
        'start_date' => $s->start_date,
        'end_date' => $s->end_date,
        'description' => $s->description,
    ])->values()->toArray(),
])->values()->toArray();
$oldCosts = collect($project->costs ?? [])->map(fn($c) => [
    'cost_item' => $c->cost_item,
    'category' => $c->category,
    'estimated_cost' => $c->estimated_cost,
    'actual_cost' => $c->actual_cost,
    'notes' => $c->notes,
])->values()->toArray();
$oldRisks = collect($project->risks ?? [])->map(fn($r) => [
    'name' => $r->name,
    'status_id' => $r->status_id,
    'impact' => $r->impact,
    'likelihood' => $r->likelihood,
    'description' => $r->description,
    'mitigation_plan' => $r->mitigation_plan,
])->values()->toArray();
$oldDeliverables = collect($project->deliverables ?? [])->map(fn($d) => [
    'name' => $d->name,
    'status_id' => $d->status_id,
    'completed_at' => $d->completed_at,
    'verified_at' => $d->verified_at,
    'verified_by' => $d->verified_by,
    'description' => $d->description,
])->values()->toArray();
$oldPlanning = $project->planning ?? [];

$impactChoices = array_values($impactOptions ?? []);
$likelihoodChoices = array_values($likelihoodOptions ?? []);
$verifiedChoices = array_values($verifiedByOptions ?? []);
$statusIdLabel = $defaultStatusCode;
@endphp

<div class="page-theme page-theme--project">
  <div class="page-shell page-shell--wide py-6">
    <div class="space-y-8">
      <div class="flex justify-center md:justify-start">
        @include('components.back', ['to' => $backTo, 'text' => 'Kembali', 'icon' => 'arrow-left'])
      </div>

      <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-r from-violet-500 via-fuchsia-500 to-pink-500 p-6 text-white shadow-xl">
        <div class="relative z-[1] flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-3xl font-semibold">Edit Project</h2>
            <p class="mt-3 max-w-2xl text-sm text-white/80">Perbarui informasi project, anggota tim, rencana aksi, dan data pendukung lainnya.</p>
          </div>
          <div class="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 text-sm backdrop-blur">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-6 w-6">
                <path d="M5.25 3A2.25 2.25 0 0 0 3 5.25v10.5A2.25 2.25 0 0 0 5.25 18H9v2.25a.75.75 0 0 0 1.28.53l2.72-2.78h5.75A2.25 2.25 0 0 0 21 15.75V5.25A2.25 2.25 0 0 0 18.75 3H5.25Z" />
              </svg>
            </div>
            <div>
              <div class="font-semibold">Status Saat Ini: {{ \App\Support\WorkflowStatus::label($defaultStatus) }}</div>
              <p class="text-xs text-white/80">Perbarui status sesuai perkembangan project.</p>
            </div>
          </div>
        </div>
        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
      </div>

      @if ($errors->any())
      <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-700">
        <div class="mb-1 font-semibold">Periksa kembali beberapa isian:</div>
        <ul class="list-disc list-inside space-y-0.5">
          @foreach ($errors->all() as $message)
          <li>{{ $message }}</li>
          @endforeach
        </ul>
      </div>
      @endif

      <form
        id="projectEditForm"
        action="{{ route('projects.update', $project) }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-8"
        data-has-old="{{ $errors->any() ? '1' : '' }}"
        data-default-status="{{ $defaultStatus }}"
        data-impact-options='@json($impactChoices)'
        data-likelihood-options='@json($likelihoodChoices)'
        data-verified-options='@json($verifiedByOptions)'>
        @csrf
        @method('PUT')
        
        @if(!empty($originFrom))
        <input type="hidden" name="from" value="{{ $originFrom }}">
        @endif
        <input type="hidden" name="src" value="{{ request('src', 'detail') }}">

        <!-- WIZARD STEPS -->
        <div class="mb-6">
          <ol id="projectWizardSteps" class="flex flex-wrap justify-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
            <li><button type="button" class="wizard-step" data-step-label="1" data-target-step="1">Project Overview</button></li>
            <li><button type="button" class="wizard-step" data-step-label="2" data-target-step="2">Team Project</button></li>
            <li><button type="button" class="wizard-step" data-step-label="3" data-target-step="3">Action Plan</button></li>
            <li><button type="button" class="wizard-step" data-step-label="4" data-target-step="4">Rincian Budget</button></li>
            <li><button type="button" class="wizard-step" data-step-label="5" data-target-step="5">Mitigasi Resiko</button></li>
            <li><button type="button" class="wizard-step" data-step-label="6" data-target-step="6">Deliverables</button></li>
            <li><button type="button" class="wizard-step" data-step-label="7" data-target-step="7">Lampiran</button></li>
          </ol>
        </div>

        <!-- STEP 1: PROJECT OVERVIEW -->
        <div data-step="1" class="step-section space-y-6">
          <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Informasi Utama</h3>
              <div class="mt-4 space-y-4">
                <div>
                  <label class="mb-1 block text-sm font-medium">Judul Project</label>
                  <input type="text" name="title" id="title" value="{{ old('title', $project->title) }}" required class="w-full rounded-xl border border-gray-200 px-4 py-2.5 shadow-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 @error('title') border-red-600 @enderror">
                  @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium">Status Workflow</label>
                  <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                    {{ \App\Support\WorkflowStatus::label($defaultStatus) }}
                    <span class="block text-[11px] text-gray-500 dark:text-gray-400">Status dapat diperbarui melalui workflow.</span>
                  </div>
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium">Nomor Project</label>
                  <input type="text" name="project_no" value="{{ old('project_no', $project->project_no) }}" maxlength="20" class="w-full rounded-xl border border-gray-200 px-3 py-2 shadow-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 @error('project_no') border-red-600 @enderror">
                  @error('project_no')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium">Ticket terkait (opsional)</label>
                  @if ($canManageTicket)
                  <select name="ticket_id" id="ticket_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 @error('ticket_id') border-red-600 @enderror">
                    <option value="">-- Tanpa ticket --</option>
                    @foreach ($tickets as $ticket)
                    <option value="{{ $ticket->id }}" @selected(old('ticket_id', $project->ticket_id)==$ticket->id)>{{ $ticket->ticket_no ?? ('Ticket #' . $ticket->id) }} &mdash; {{ \Illuminate\Support\Str::limit($ticket->title, 48) }}</option>
                    @endforeach
                  </select>
                  @else
                  <input type="hidden" name="ticket_id" value="{{ $project->ticket_id }}">
                  <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                    Ticket terkait hanya dapat diubah oleh Admin.
                  </div>
                  @endif
                  @error('ticket_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Deskripsi &amp; Timeline</h3>
              <div class="mt-4 space-y-4">
                <div>
                  <label class="mb-1 flex items-center justify-between text-sm font-medium">Deskripsi Project <span id="descriptionCounter" class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-200">0 kata</span></label>
                  <div id="projectDescWrap" class="ql-card rounded-xl border border-gray-200 shadow-inner dark:border-gray-700">
                    <div id="project_description_editor" class="min-h-[200px] rounded-xl bg-white dark:bg-gray-900"></div>
                  </div>
                  <textarea id="description" name="description" class="hidden">{{ old('description', strip_tags($project->description, '<b><i><u><strong><em><p><br><ul><li><ol>')) }}</textarea>
                  @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Mulai</label>
                    <input type="text" id="project_start_date" name="start_date" value="{{ old('start_date', optional($project->start_date)->format('d/m/Y')) }}" placeholder="dd/mm/yyyy" class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Selesai</label>
                    <input type="text" id="project_end_date" name="end_date" value="{{ old('end_date', optional($project->end_date)->format('d/m/Y')) }}" placeholder="dd/mm/yyyy" class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- PLANNING SECTION IN STEP 1 -->
          <div id="planning-section" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-3 text-lg font-semibold">Planning</h2>
            <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
              Tambahkan aktivitas/milestone (contoh: "Workshop", "Master data collection", dll).
            </p>
            <div class="mb-2 flex items-center gap-2">
              <button type="button" id="btnAddPlan" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">+ Tambah Baris</button>
              <button type="button" id="btnClearPlan" class="rounded border border-amber-300 px-3 py-1.5 text-xs text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900/30">Clear</button>
            </div>
            <div id="planWrap" class="space-y-2" data-old='@json(old("planning", $oldPlanning))'></div>
            <template id="planRowTpl">
              <div class="grid gap-2 md:grid-cols-12 items-start">
                <div class="md:col-span-5">
                  <input type="text" name="planning[__INDEX__][title]" placeholder="Judul / Aktivitas" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                </div>
                <div class="md:col-span-2">
                  <input type="text" name="planning[__INDEX__][week]" placeholder="Minggu (cth: 1–2)" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                </div>
                <div class="md:col-span-3">
                  <select name="planning[__INDEX__][status]" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">— Status —</option>
                    <option value="done">Selesai</option>
                    <option value="in_progress">Progress</option>
                    <option value="blocked">Terblokir</option>
                  </select>
                </div>
                <div class="md:col-span-2 flex gap-2">
                  <button type="button" class="btn-remove-plan rounded-md border border-red-200 px-3 py-2 text-sm text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30">Hapus</button>
                </div>
                <div class="md:col-span-12">
                  <input type="text" name="planning[__INDEX__][note]" placeholder="Catatan (opsional)" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                </div>
              </div>
            </template>
          </div>

          <div class="flex flex-wrap items-center justify-end gap-3">
            <button type="button" data-next-step="2" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
          </div>
        </div>

        <!-- STEP 2: TEAM PROJECT -->
        <div data-step="2" class="step-section hidden space-y-6">
          <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Team Project &amp; Lead</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Pilih anggota terdaftar dan tentukan peran mereka.</p>
              </div>
              <button type="button" id="btnAddMember" class="inline-flex items-center gap-2 rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-600">
                <span class="material-icons text-[18px]">group_add</span>
                Tambah Anggota
              </button>
            </div>

            <div class="mt-4 space-y-3">
              <div>
                <label class="mb-1 block text-sm font-medium">Requester</label>
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                  {{ $project->requester?->name ?? 'Tidak ada requester' }}
                  <input type="hidden" name="requester_id" value="{{ $project->requester_id }}">
                </div>
              </div>
            </div>

            <div class="mt-8">
              <label class="mb-1 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                <span>Daftar User</span>
                <span class="font-normal normal-case text-gray-400">Gunakan CTRL/Shift untuk pilih lebih dari satu</span>
              </label>
              <select id="teamUserSelect" class="team-user-picker" multiple size="8">
                @foreach ($users as $user)
                @php $label = $user->label ?? ($user->name ?? ($user->email ?? 'User #' . $user->id)); @endphp
                <option value="{{ $user->id }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div id="teamQuickList" class="team-quick-list mt-3" role="listbox" aria-label="Pengguna" aria-multiselectable="true">
              @foreach ($users as $user)
              @php $label = $user->label ?? ($user->name ?? ($user->email ?? 'User #' . $user->id)); @endphp
              <button type="button" class="team-quick-btn" data-user-id="{{ $user->id }}" role="option" aria-selected="false">{{ $label }}</button>
              @endforeach
            </div>
          </div>

          <div id="teamPills" class="mt-4 flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-300"></div>
          <div id="membersWrap" class="mt-4 space-y-2" data-old='@json(old("project_pics", $oldPics))'></div>

          <template id="memberRowTpl">
            <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-600 dark:bg-gray-800/80 md:flex-row md:items-center md:gap-4" data-member="__INDEX__" data-user="__USER_ID__">
              <input type="hidden" name="project_pics[__INDEX__][user_id]" value="__USER_ID__">
              <div class="flex-1">
                <div class="mb-1 text-sm font-semibold text-emerald-600 dark:text-emerald-300">__LABEL__</div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Peran</label>
                <input type="text" name="project_pics[__INDEX__][position]" value="__POSITION__" placeholder="Contoh: Project Manager" class="member-position w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-400 focus:ring-emerald-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
              </div>
              <div class="flex flex-shrink-0 justify-end md:justify-center">
                <button type="button" class="btn-remove-member inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-500 dark:text-red-300 dark:hover:bg-red-600/20">
                  <span class="material-icons text-[16px] leading-none">close</span>
                  Hapus
                </button>
              </div>
            </div>
          </template>

          <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" data-prev-step="1" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Kembali</button>
            <button type="button" data-next-step="3" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
          </div>
        </div>

        <!-- STEP 3: ACTION PLAN -->
        <div data-step="3" class="step-section hidden space-y-6">
          <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Action Plan</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Outline each primary action and the supporting sub-actions.</p>
              </div>
              <button type="button" id="btnAddAction" class="inline-flex items-center gap-2 rounded-full bg-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-600">
                <span class="material-icons text-[18px]">playlist_add</span>
                Tambah Action
              </button>
            </div>
            <div id="actionsWrap" class="mt-4 space-y-4" data-old='@json(old("project_actions", $oldActions))'></div>

            <template id="actionTpl">
              <div class="action-card rounded-2xl border border-gray-200 bg-white/90 p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800" data-action="__INDEX__">
                <div class="flex items-center justify-between gap-3">
                  <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">Action <span class="action-order"></span></div>
                  <button type="button" class="btn-remove-action inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-500 dark:text-red-300 dark:hover:bg-red-500/20">Hapus</button>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                  <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Judul Aksi</label>
                    <input type="text" name="project_actions[__INDEX__][title]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</label>
                    <select name="project_actions[__INDEX__][status_id]" class="action-status-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                      <option value="">-- Pilih status --</option>
                      @foreach ($statuses as $status)
                      <option value="{{ $status->id }}">{{ $status->name ?? $status->id }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Progress (%)</label>
                    <input type="number" name="project_actions[__INDEX__][progress]" min="0" max="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">PIC</label>
                    <select name="project_actions[__INDEX__][pic_user_id]" class="action-pic-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                      <option value="">-- Pilih PIC --</option>
                    </select>
                  </div>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                      <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Mulai</label>
                      <input type="text" name="project_actions[__INDEX__][start_date]" placeholder="dd/mm/yyyy" class="action-start-date w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Selesai</label>
                      <input type="text" name="project_actions[__INDEX__][end_date]" placeholder="dd/mm/yyyy" class="action-end-date w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                  </div>
                  <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Deskripsi</label>
                    <textarea name="project_actions[__INDEX__][description]" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea>
                  </div>
                </div>
                <div class="mt-5">
                  <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Sub-Aksi</h4>
                    <button type="button" class="btn-add-subaction inline-flex items-center gap-1 rounded-full border border-indigo-300 px-3 py-1 text-xs font-semibold text-indigo-600 hover:bg-indigo-50 dark:border-indigo-500/50 dark:text-indigo-200 dark:hover:bg-indigo-500/10">Tambah Sub-Aksi</button>
                  </div>
                  <div class="mt-3 space-y-3" data-subaction-wrap></div>
                </div>
              </div>
            </template>

            <template id="subActionTpl">
              <div class="subaction-card rounded-xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-600 dark:bg-gray-900/60" data-subaction="__SUB_INDEX__">
                <div class="flex items-center justify-between gap-3">
                  <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Sub-Aksi <span class="subaction-order"></span></div>
                  <button type="button" class="btn-remove-subaction inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-500 dark:text-red-300 dark:hover:bg-red-500/20">Hapus</button>
                </div>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                  <div class="md:col-span-2">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Judul Sub-Aksi</label>
                    <input type="text" name="project_actions[__ACTION_INDEX__][subactions][__SUB_INDEX__][title]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</label>
                    <select name="project_actions[__ACTION_INDEX__][subactions][__SUB_INDEX__][status_id]" class="subaction-status-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                      <option value="">-- Pilih status --</option>
                      @foreach ($statuses as $status)
                      <option value="{{ $status->id }}">{{ $status->name ?? $status->id }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Progress (%)</label>
                    <input type="number" name="project_actions[__ACTION_INDEX__][subactions][__SUB_INDEX__][progress]" min="0" max="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">PIC</label>
                    <select name="project_actions[__ACTION_INDEX__][subactions][__SUB_INDEX__][pic_user_id]" class="subaction-pic-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                      <option value="">-- Pilih PIC --</option>
                    </select>
                  </div>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                      <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Mulai</label>
                      <input type="text" name="project_actions[__ACTION_INDEX__][subactions][__SUB_INDEX__][start_date]" placeholder="dd/mm/yyyy" class="subaction-start-date w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                      <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Selesai</label>
                      <input type="text" name="project_actions[__ACTION_INDEX__][subactions][__SUB_INDEX__][end_date]" placeholder="dd/mm/yyyy" class="subaction-end-date w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                  </div>
                  <div class="md:col-span-2">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Deskripsi</label>
                    <textarea name="project_actions[__ACTION_INDEX__][subactions][__SUB_INDEX__][description]" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" data-prev-step="2" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Kembali</button>
            <button type="button" data-next-step="4" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
          </div>
        </div>

        <!-- STEP 4: RINCIAN BUDGET -->
        <div data-step="4" class="step-section hidden space-y-6">
          <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Rincian Budget</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Catat item biaya, kategori, serta nilai estimasi dan aktual.</p>
              </div>
              <button type="button" id="btnAddCost" class="inline-flex items-center gap-2 rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-amber-600">
                <span class="material-icons text-[18px]">add_circle</span>
                Tambah Biaya
              </button>
            </div>
            <div id="costsWrap" class="mt-4 space-y-4" data-old='@json(old("project_costs", $oldCosts))'></div>

            <template id="costTpl">
              <div class="cost-card rounded-2xl border border-gray-200 bg-white/90 p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800" data-cost="__INDEX__">
                <div class="flex items-center justify-between gap-3">
                  <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">Biaya <span class="cost-order"></span></div>
                  <button type="button" class="btn-remove-cost inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-500 dark:text-red-300 dark:hover:bg-red-500/20">Hapus</button>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Item Biaya</label>
                    <input type="text" name="project_costs[__INDEX__][cost_item]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Kategori</label>
                    <input type="text" name="project_costs[__INDEX__][category]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Estimasi</label>
                    <input type="number" step="0.01" min="0" name="project_costs[__INDEX__][estimated_cost]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Aktual</label>
                    <input type="number" step="0.01" min="0" name="project_costs[__INDEX__][actual_cost]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Catatan</label>
                    <textarea name="project_costs[__INDEX__][notes]" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:ring-amber-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" data-prev-step="3" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Kembali</button>
            <button type="button" data-next-step="5" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
          </div>
        </div>

        <!-- STEP 5: MITIGASI RESIKO -->
        <div data-step="5" class="step-section hidden space-y-6">
          <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Mitigasi Resiko</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Identifikasi risiko, dampak, serta langkah mitigasinya.</p>
              </div>
              <button type="button" id="btnAddRisk" class="inline-flex items-center gap-2 rounded-full bg-rose-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-rose-600">
                <span class="material-icons text-[18px]">warning</span>
                Tambah Risiko
              </button>
            </div>
            <div id="risksWrap" class="mt-4 space-y-4" data-old='@json(old("project_risks", $oldRisks))'></div>

            <template id="riskTpl">
              <div class="risk-card rounded-2xl border border-gray-200 bg-white/90 p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800" data-risk="__INDEX__">
                <div class="flex items-center justify-between gap-3">
                  <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">Risiko <span class="risk-order"></span></div>
                  <button type="button" class="btn-remove-risk inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-500 dark:text-red-300 dark:hover:bg-red-500/20">Hapus</button>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Risiko</label>
                    <input type="text" name="project_risks[__INDEX__][name]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</label>
                    <select name="project_risks[__INDEX__][status_id]" class="risk-status-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                      <option value="">-- Pilih status --</option>
                      @foreach ($statuses as $status)
                      <option value="{{ $status->id }}">{{ $status->name ?? $status->id }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dampak</label>
                    <select name="project_risks[__INDEX__][impact]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                      <option value="">-- Pilih --</option>
                      @foreach ($impactOptions as $impact)
                      <option value="{{ $impact }}">{{ ucfirst($impact) }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Kemungkinan</label>
                    <select name="project_risks[__INDEX__][likelihood]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                      <option value="">-- Pilih --</option>
                      @foreach ($likelihoodOptions as $likelihood)
                      <option value="{{ $likelihood }}">{{ ucwords(str_replace('_', ' ', $likelihood)) }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Deskripsi</label>
                    <textarea name="project_risks[__INDEX__][description]" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea>
                  </div>
                  <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Mitigasi</label>
                    <textarea name="project_risks[__INDEX__][mitigation_plan]" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" data-prev-step="4" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Kembali</button>
            <button type="button" data-next-step="6" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
          </div>
        </div>

        <!-- STEP 6: DELIVERABLES -->
        <div data-step="6" class="step-section hidden space-y-6">
          <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Deliverables</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Daftar keluaran utama proyek beserta status verifikasinya.</p>
              </div>
              <button type="button" id="btnAddDeliverable" class="inline-flex items-center gap-2 rounded-full bg-teal-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-teal-600">
                <span class="material-icons text-[18px]">task_alt</span>
                Tambah Deliverable
              </button>
            </div>
            <div id="deliverablesWrap" class="mt-4 space-y-4" data-old='@json(old("project_deliverables", $oldDeliverables))'></div>

            <template id="deliverableTpl">
              <div class="deliverable-card rounded-2xl border border-gray-200 bg-white/90 p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800" data-deliverable="__INDEX__">
                <div class="flex items-center justify-between gap-3">
                  <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">Deliverable <span class="deliverable-order"></span></div>
                  <button type="button" class="btn-remove-deliverable inline-flex items-center gap-1 rounded-full border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:border-red-500 dark:text-red-300 dark:hover:bg-red-500/20">Hapus</button>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Deliverable</label>
                    <input type="text" name="project_deliverables[__INDEX__][name]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</label>
                    <select name="project_deliverables[__INDEX__][status_id]" class="deliverable-status-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                      <option value="">-- Pilih status --</option>
                      @foreach ($statuses as $status)
                      <option value="{{ $status->id }}">{{ $status->name ?? $status->id }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Selesai Pada</label>
                    <input type="text" name="project_deliverables[__INDEX__][completed_at]" placeholder="dd/mm/yyyy HH:mm" class="deliverable-completed-at w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Terverifikasi Pada</label>
                    <input type="text" name="project_deliverables[__INDEX__][verified_at]" placeholder="dd/mm/yyyy HH:mm" class="deliverable-verified-at w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Verified By</label>
                    <select name="project_deliverables[__INDEX__][verified_by]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                      <option value="">-- Pilih --</option>
                      @foreach ($verifiedByOptions as $option)
                      <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Deskripsi</label>
                    <textarea name="project_deliverables[__INDEX__][description]" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-400 focus:ring-teal-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" data-prev-step="5" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Kembali</button>
            <button type="button" data-next-step="7" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
          </div>
        </div>

        <!-- STEP 7: LAMPIRAN -->
        <div data-step="7" class="step-section hidden space-y-6">
          <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Lampiran Project / Attachments</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Unggah dokumen pendukung seperti TOR, surat tugas, atau bukti lainnya.</p>
            <div class="mt-4">
              @include('partials.attachments', [
              'initialAttachments' => collect($project->attachments ?? []),
              'toggleDefault' => true,
              'inputId' => 'project-attachments'
              ])
            </div>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3 attachments-actions">
            <button type="button" data-prev-step="6" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Kembali</button>
            <div class="flex flex-col gap-2 text-sm md:flex-row md:items-center">
              <span class="text-xs text-gray-500 dark:text-gray-400">Pintasan: <kbd class="rounded border px-1">Ctrl/Cmd</kbd> + <kbd class="rounded border px-1">S</kbd></span>
              <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-white shadow hover:bg-green-700">
                  <span>Simpan Perubahan</span>
                </button>
                <a href="{{ $backTo }}" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-white shadow hover:bg-red-700">Batal</a>
              </div>
            </div>
          </div>
        </div>
      </form>

      <script>
        (function() {
          const STEP_STORAGE_KEY = 'project:edit:step:{{ $project->id }}';
          const form = document.getElementById('projectEditForm');
          if (!form) return;

          const wizard = document.getElementById('projectWizardSteps');
          const wizardButtons = wizard ? Array.from(wizard.querySelectorAll('.wizard-step')) : [];
          const sections = Array.from(document.querySelectorAll('.step-section'));

          const activateStep = (target) => {
            if (!sections.length) return;
            const desired = String(target || '');
            let targetSection = sections.find(section => section.dataset.step === desired);
            if (!targetSection) targetSection = sections[0];
            if (!targetSection) return;

            const activeStep = targetSection.dataset.step;
            sections.forEach(section => {
              section.classList.toggle('hidden', section !== targetSection);
            });

            wizardButtons.forEach(button => {
              const buttonStep = button.dataset.targetStep || button.dataset.stepLabel;
              const isActive = buttonStep === activeStep;
              button.classList.toggle('is-active', isActive);
              button.setAttribute('aria-current', isActive ? 'step' : 'false');
            });

            try {
              localStorage.setItem(STEP_STORAGE_KEY, activeStep);
            } catch (_) {}
          };

          let defaultStep = sections[0]?.dataset.step || '1';
          try {
            const stored = localStorage.getItem(STEP_STORAGE_KEY);
            if (stored && sections.some(section => section.dataset.step === stored)) {
              defaultStep = stored;
            }
          } catch (_) {}

          activateStep(defaultStep);

          wizardButtons.forEach(button => {
            button.addEventListener('click', () => {
              const target = button.dataset.targetStep || button.dataset.stepLabel;
              if (target) activateStep(target);
            });
          });

          document.querySelectorAll('[data-next-step]').forEach(button => {
            button.addEventListener('click', () => {
              const target = button.dataset.nextStep;
              if (target) activateStep(target);
            });
          });

          document.querySelectorAll('[data-prev-step]').forEach(button => {
            button.addEventListener('click', () => {
              const target = button.dataset.prevStep;
              if (target) activateStep(target);
            });
          });

          // Planning form handling
          const planWrap = document.getElementById('planWrap');
          const planTpl = document.getElementById('planRowTpl');
          const btnAddPlan = document.getElementById('btnAddPlan');
          const btnClearPlan = document.getElementById('btnClearPlan');
          let planIdx = 0;

          function addPlanRow(data = {}) {
            const html = planTpl.innerHTML.replaceAll('__INDEX__', planIdx);
            const div = document.createElement('div');
            div.innerHTML = html.trim();
            const row = div.firstElementChild;

            row.querySelector(`[name="planning[${planIdx}][title]"]`).value = data.title || '';
            row.querySelector(`[name="planning[${planIdx}][week]"]`).value = data.week || '';
            row.querySelector(`[name="planning[${planIdx}][status]"]`).value = data.status || '';
            row.querySelector(`[name="planning[${planIdx}][note]"]`).value = data.note || '';

            row.querySelector('.btn-remove-plan')?.addEventListener('click', () => row.remove());
            planWrap.appendChild(row);
            planIdx++;
          }

          let oldPlan = [];
          try {
            oldPlan = JSON.parse(planWrap.dataset.old || '[]');
          } catch (_) {}

          if (Array.isArray(oldPlan) && oldPlan.length) {
            oldPlan.forEach(o => addPlanRow(o || {}));
          } else {
            addPlanRow();
          }

          btnAddPlan?.addEventListener('click', () => addPlanRow());
          btnClearPlan?.addEventListener('click', () => {
            planWrap.innerHTML = '';
            planIdx = 0;
            addPlanRow();
          });
        })();
      </script>

      @push('styles')
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
      <style>
        .page-theme--project {
          --brandA: #8b5cf6;
          --brandB: #d946ef;
        }

        #projectWizardSteps {
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
          align-items: center;
          gap: .5rem .6rem;
          margin: 0 auto 1rem auto;
          padding: .25rem .5rem;
          max-width: 100%;
          box-sizing: border-box
        }

        .wizard-step {
          display: inline-flex;
          align-items: center;
          gap: .55rem;
          padding: .5rem 1rem;
          border-radius: 9999px;
          border: 1px solid rgba(16, 185, 129, .25);
          background: rgba(255, 255, 255, .85);
          color: #0f172a;
          font-weight: 600;
          line-height: 1;
          white-space: nowrap;
          transition: all .2s ease;
          box-shadow: 0 8px 16px -14px rgba(16, 185, 129, .35);
          cursor: pointer;
          appearance: none;
          -webkit-appearance: none
        }

        .wizard-step::before {
          content: attr(data-step-label);
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 1.6rem;
          height: 1.6rem;
          min-width: 1.6rem;
          border-radius: 999px;
          background: rgba(16, 185, 129, .18);
          color: #047857;
          font-weight: 700
        }

        .wizard-step.is-active {
          color: #fff;
          border-color: transparent;
          background: linear-gradient(135deg, var(--brandA, #8b5cf6), var(--brandB, #d946ef));
          box-shadow: 0 18px 35px -20px rgba(16, 185, 129, .55)
        }

        .wizard-step.is-active::before {
          background: rgba(255, 255, 255, .3);
          color: #fff
        }

        .dark .wizard-step {
          background: rgba(15, 23, 42, .75);
          border-color: rgba(45, 212, 191, .35);
          color: #ccfbf1
        }

        .dark .wizard-step::before {
          background: rgba(45, 212, 191, .24);
          color: #ccfbf1
        }

        .ql-card .ql-toolbar.ql-snow {
          border: none;
          border-bottom: 1px solid rgba(148, 163, 184, .3);
          border-radius: .75rem .75rem 0 0
        }

        .ql-card .ql-container.ql-snow {
          border: none;
          border-radius: 0 0 .75rem .75rem;
          min-height: 200px
        }

        .page-shell {
          min-height: 100vh;
          overflow-y: auto
        }

        .attachments-actions {
          position: static;
          margin-top: 1rem;
          padding-top: .75rem;
          border-top: 1px solid rgba(148, 163, 184, .35);
          background: transparent
        }

        .dark .attachments-actions {
          border-color: rgba(148, 163, 184, .35)
        }

        .team-user-picker {
          width: 100%;
          min-height: 180px;
          border-radius: 0.75rem;
          border: 1px solid rgba(148, 163, 184, .6);
          padding: .75rem;
          background: rgba(248, 250, 252, .8);
          font-size: .875rem;
          color: #0f172a;
        }

        .team-user-picker:focus {
          outline: none;
          border-color: rgba(16, 185, 129, .6);
          box-shadow: 0 0 0 2px rgba(16, 185, 129, .15);
        }

        .dark .team-user-picker {
          background: rgba(15, 23, 42, .6);
          color: #e2e8f0;
          border-color: rgba(148, 163, 184, .35);
        }

        .team-quick-list {
          display: flex;
          flex-wrap: wrap;
          gap: .5rem;
        }

        .team-quick-btn {
          padding: .35rem .75rem;
          border-radius: 999px;
          border: 1px dashed rgba(148, 163, 184, .8);
          font-size: .75rem;
          color: #334155;
          background: rgba(248, 250, 252, .7);
          transition: all .15s ease;
        }

        .team-quick-btn:hover {
          background: rgba(16, 185, 129, .12);
          border-style: solid;
        }

        .team-quick-btn.is-active {
          background: linear-gradient(135deg, rgba(16, 185, 129, .85), rgba(45, 212, 191, .7));
          color: #fff;
          border-color: transparent;
        }

        .dark .team-quick-btn {
          background: rgba(30, 41, 59, .6);
          color: #cbd5f5;
          border-color: rgba(148, 163, 184, .3);
        }

        .dark .team-quick-btn.is-active {
          background: linear-gradient(135deg, rgba(16, 185, 129, .65), rgba(59, 130, 246, .6));
          color: #f8fafc;
        }
      </style>
      @endpush

      @push('scripts')
      <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
      <script src="https://cdn.jsdelivr.net/npm/quill@1.3.6/dist/quill.js"></script>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          const form = document.getElementById('projectEditForm');
          if (!form) return;

          // Initialize Quill
          const descEditorEl = document.getElementById('project_description_editor');
          const descHiddenEl = document.getElementById('description');
          if (descEditorEl && descHiddenEl && typeof Quill !== 'undefined') {
            const quill = new Quill(descEditorEl, {
              theme: 'snow',
              modules: {
                toolbar: [[{'header': [1, 2, false]}], ['bold', 'italic', 'underline'], ['link', 'code-block'], ['clean']],
              },
            });
            if (descHiddenEl.value) {
              quill.clipboard.dangerouslyPasteHTML(descHiddenEl.value);
            }
            quill.on('text-change', () => {
              descHiddenEl.value = quill.root.innerHTML;
            });
          }

          // Date pickers
          if (typeof flatpickr !== 'undefined') {
            flatpickr('#project_start_date, #project_end_date', {
              dateFormat: 'd/m/Y',
              allowInput: true,
            });
          }
        });
      </script>
      @endpush
    </div>
  </div>
</div>

@endsection
