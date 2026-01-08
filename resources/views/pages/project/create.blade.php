{{-- resources/views/pages/project/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Project Baru')

@section('content')
@php
$defaultStatus = old('status', \App\Support\WorkflowStatus::default());
$normalizedStatus = \App\Support\WorkflowStatus::normalize($defaultStatus);
$defaultStatusCode = \App\Support\WorkflowStatus::code($normalizedStatus);
$oldPics = old('project_pics', []);
$oldActions = old('project_actions', []);
$oldCosts = old('project_costs', []);
$oldRisks = old('project_risks', []);
$oldDeliverables = old('project_deliverables', []);
$impactChoices = array_values($impactOptions ?? []);
$likelihoodChoices = array_values($likelihoodOptions ?? []);
$verifiedChoices = array_values($verifiedByOptions ?? []);
$canManageTicket = $canManageTicket ?? false;
$canPickRequester = $canPickRequester ?? false;
$requesterOptions = $requesterOptions ?? $users;
$defaultRequester = $defaultRequester ?? null;
$requesterLabel = $requesterLabel ?? null;
$statusIdLabel = $defaultStatusCode;
@endphp
<div class="page-theme page-theme--project">
  <div class="page-shell page-shell--wide py-6">
    <div class="space-y-8">
      <div class="flex justify-center md:justify-start">
        @include('components.back', ['to' => request('from', route('projects.report')), 'text' => 'Daftar Project', 'icon' => 'chevron_left'])
      </div>

      <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-r from-violet-500 via-fuchsia-500 to-pink-500 p-6 text-white shadow-xl">
        <div class="relative z-[1] flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-3xl font-semibold">Buat Project Baru</h2>
            <p class="mt-3 max-w-2xl text-sm text-white/80">Ikuti tiga langkah sederhana: Info project, Timeline &amp; anggaran, kemudian susun tim dan lampiran pendukung.</p>
          </div>
          <div class="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 text-sm backdrop-blur">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-6 w-6">
                <path d="M5.25 3A2.25 2.25 0 0 0 3 5.25v10.5A2.25 2.25 0 0 0 5.25 18H9v2.25a.75.75 0 0 0 1.28.53l2.72-2.78h5.75A2.25 2.25 0 0 0 21 15.75V5.25A2.25 2.25 0 0 0 18.75 3H5.25Z" />
              </svg>
            </div>
            <div>
              <div class="font-semibold">Status Awal: {{ \App\Support\WorkflowStatus::label($normalizedStatus) }}</div>
              <p class="text-xs text-white/80">Status dapat diperbarui sewaktu-waktu setelah project dibuat.</p>
            </div>
          </div>
        </div>
        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
      </div>

      <div id="leaveGuardModal" class="fixed inset-0 z-[9999] hidden">
        <div class="relative z-10 flex h-full w-full items-center justify-center px-4">
          <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-gray-900 dark:text-gray-100">
            <div class="flex items-start gap-3 p-5">
              <div class="mt-1 text-amber-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M11 7h2v6h-2V7Zm0 8h2v2h-2v-2Z" />
                  <path d="M11.001 2a1 1 0 0 0-.866.5l-9 15.5A1 1 0 0 0 2 20h20a1 1 0 0 0 .866-1.5l-9-15.5A1 1 0 0 0 13 2h-2Z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="text-base font-semibold">Tinggalkan halaman ini?</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Perubahan Anda belum disimpan.</p>
                <div class="mt-5 flex items-center justify-end gap-2">
                  <button type="button" id="leaveGuardCancel" class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Kembali</button>
                  <button type="button" id="leaveGuardConfirm" class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">Tinggalkan</button>
                </div>
              </div>
            </div>
          </div>
        </div>
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
        id="projectCreateForm"
        action="{{ route('projects.store') }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-8"
        data-has-old="{{ $errors->any() ? '1' : '' }}"
        data-success="{{ session('success') ? '1' : '' }}"
        data-default-status="{{ $defaultStatus }}"
        data-index-url="{{ route('projects.report') }}"
        data-impact-options='@json($impactChoices)'
        data-likelihood-options='@json($likelihoodChoices)'
        data-verified-options='@json($verifiedByOptions)'
        data-next-number="{{ \App\Domains\Project\Models\Project::nextNumber() }}">
        @csrf
        <input type="hidden" name="from" value="{{ request('from', route('projects.report')) }}">

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

        <div data-step="1" class="step-section space-y-6">
          <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Informasi Utama</h3>
              <div class="mt-4 space-y-4">
                <div>
                  <label class="mb-1 block text-sm font-medium">Judul Project</label>
                  <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full rounded-xl border border-gray-200 px-4 py-2.5 shadow-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium">Status Workflow</label>
                  <input type="hidden" name="status" id="status" value="{{ $defaultStatus }}">
                  <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                    {{ \App\Support\WorkflowStatus::label($defaultStatus) }}
                    <span class="block text-[11px] text-gray-500 dark:text-gray-400">Status awal mengikuti workflow default.</span>
                  </div>
                </div>
                <div>
                  <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                    {{ $statusIdLabel }}
                    <span class="block text-[11px] text-gray-500 dark:text-gray-400">Status ID akan mengikuti status workflow secara otomatis.</span>
                  </div>
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium">Nomor Project (opsional)</label>
                  <input type="text" name="project_no" value="{{ old('project_no') }}" maxlength="20" placeholder="Contoh: PRJ20250001" class="w-full rounded-xl border border-gray-200 px-3 py-2 shadow-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                  <p id="project_no_hint" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nomor otomatis tersuguh: <span id="project_no_auto">{{ \App\Domains\Project\Models\Project::nextNumber() }}</span></p>
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium">Ticket terkait (opsional)</label>
                  @if ($canManageTicket)
                  <select name="ticket_id" id="ticket_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">-- Tanpa ticket --</option>
                    @foreach ($tickets as $ticket)
                    <option value="{{ $ticket->id }}" @selected(old('ticket_id')==$ticket->id)>{{ $ticket->ticket_no ?? ('Ticket #' . $ticket->id) }} &mdash; {{ \Illuminate\Support\Str::limit($ticket->title, 48) }}</option>
                    @endforeach
                  </select>
                  @else
                  <input type="hidden" name="ticket_id" value="">
                  <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                    Ticket terkait hanya dapat dipilih oleh Admin.
                  </div>
                  @endif
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
                  <textarea id="description" name="description" class="hidden">{{ old('description') }}</textarea>
                  <ul class="mt-3 grid gap-2 text-xs text-gray-500 dark:text-gray-400 lg:grid-cols-2">
                    <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>Jelaskan tujuan akhir atau kendala utama.</li>
                    <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>Sertakan juga detail pendukung.</li>
                  </ul>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Mulai</label>
                    <input type="text" id="project_start_date" name="start_date" value="{{ old('start_date') }}" placeholder="dd/mm/yyyy" class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                  <div>
                    <label class="mb-1 block text-sm font-medium">Tanggal Selesai</label>
                    <input type="text" id="project_end_date" name="end_date" value="{{ old('end_date') }}" placeholder="dd/mm/yyyy" class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-teal-400 focus:ring-teal-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap items-center justify-end gap-3">
            <div class="flex items-center gap-2">
              <button type="button" data-next-step="2" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
            </div>
          </div>
        </div>

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
                @if ($canPickRequester)
                <select name="requester_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-400 focus:ring-emerald-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                  <option value="">-- Pilih Requester --</option>
                  @foreach ($requesterOptions as $u)
                  @php $label = $u->label ?? ($u->name ?? ($u->email ?? 'User #'.$u->id)); @endphp
                  <option value="{{ $u->id }}" @selected(old('requester_id', $defaultRequester)==$u->id)>{{ $label }}</option>
                  @endforeach
                </select>
                @else
                <input type="hidden" name="requester_id" value="{{ $defaultRequester }}">
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                  {{ $requesterLabel }}
                  <span class="block text-[11px] text-gray-500 dark:text-gray-400">Requester otomatis mengikuti akun yang sedang login.</span>
                </div>
                @endif
                @error('requester_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
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
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Gunakan tombol di atas atau pintasan berikut untuk memasukkan PIC dengan cepat.</p>
            <div id="teamQuickList" class="team-quick-list mt-3" role="listbox" aria-label="Pengguna" aria-multiselectable="true">
              @foreach ($users as $user)
              @php $label = $user->label ?? ($user->name ?? ($user->email ?? 'User #' . $user->id)); @endphp
              <button type="button" class="team-quick-btn" data-user-id="{{ $user->id }}" role="option" aria-selected="false">{{ $label }}</button>
              @endforeach
            </div>
          </div>

          <div id="teamPills" class="mt-4 flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-300"></div>

          <div id="membersWrap" class="mt-4 space-y-2" data-old='@json($oldPics)'></div>

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
            <div class="flex items-center gap-2">
              <button type="button" data-next-step="3" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
            </div>
          </div>
        </div>

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

            <div id="actionsWrap" class="mt-4 space-y-4" data-old='@json($oldActions)'></div>

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
            <div class="flex items-center gap-2">
              <button type="button" data-next-step="4" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
            </div>
          </div>
        </div>

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

            <div id="costsWrap" class="mt-4 space-y-4" data-old='@json($oldCosts)'></div>

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
            <div class="flex items-center gap-2">
              <button type="button" data-next-step="5" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
            </div>
          </div>
        </div>

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

        <div id="risksWrap" class="mt-4 space-y-4" data-old='@json($oldRisks)'></div>

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
                  @foreach ($impactOptions as $impact)
                  <option value="{{ $impact }}">{{ ucfirst($impact) }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Kemungkinan</label>
                <select name="project_risks[__INDEX__][likelihood]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-400 focus:ring-rose-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
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
        <div class="flex items-center gap-2">
          <button type="button" data-next-step="6" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
        </div>
      </div>
    </div>

    <div data-step="6" class="step-section hidden space-y-6">
      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Deliverables</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Daftar keluaran utama proyek beserta status verifikasinya.</p>
          </div>
          <button type="button" id="btnAddDeliverable" class="inline-flex items-center gap-2 rounded-full bg-teal-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-teal-600">
            <span class="material-icons text-[18px]">task_alt</span>
            Add Deliverable
          </button>
        </div>

        <div id="deliverablesWrap" class="mt-4 space-y-4" data-old='@json($oldDeliverables)'></div>

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
        <div class="flex items-center gap-2">
          <button type="button" data-next-step="7" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
        </div>
      </div>
    </div>

    <div data-step="7" class="step-section hidden space-y-6">
      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Lampiran Project / Attachments</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Unggah dokumen pendukung seperti TOR, surat tugas, atau bukti lainnya.</p>
        <div class="mt-4">
          @include('partials.attachments', [
          'initialAttachments' => collect(),
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
              <span>Simpan Project</span>
            </button>
            <button type="button" id="projectCancelBtn" data-cancel="{{ route('projects.create') }}" data-confirm-title="Batal buat project?" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-white shadow hover:bg-red-700">Batal</button>
          </div>
        </div>
      </div>
    </div>
    </form>
    <script>
      (function() {
        // Selaraskan kunci dengan partial attachments (storageKey = 'filepond_tmp_'+inputId)
        const ATTACH_KEY = 'filepond_tmp_project-attachments';
        // Clear drafts on successful save
        try {
          const form = document.getElementById('projectCreateForm');
          if (form && form.dataset && form.dataset.success === '1') {
            try {
              localStorage.removeItem(ATTACH_KEY);
              if (window.attachmentDrafts && window.attachmentDrafts[ATTACH_KEY]) {
                try {
                  window.attachmentDrafts[ATTACH_KEY].clear();
                } catch (_) {}
              }
            } catch (_) {}
          }
        } catch (_) {}

        let dirty = false;
        try {
          document.addEventListener('create:dirty', () => {
            dirty = true;
          }, true);
        } catch (_) {}
        const step1 = document.querySelector('[data-step="1"]');
        if (step1) {
          step1.addEventListener('input', () => {
            dirty = true;
          }, true);
          step1.addEventListener('change', () => {
            dirty = true;
          }, true);
        }

        const openModal = (targetUrl) => {
          const modal = document.getElementById('leaveGuardModal');
          const btnCancel = document.getElementById('leaveGuardCancel');
          const btnConfirm = document.getElementById('leaveGuardConfirm');
          if (!modal) {
            if (targetUrl) location.href = targetUrl;
            return;
          }
          modal.classList.remove('hidden');
          requestAnimationFrame(() => modal.classList.add('modal-show'));
          const cleanup = () => {
            modal.classList.remove('modal-show');
            setTimeout(() => {
              if (!modal.classList.contains('modal-show')) modal.classList.add('hidden');
            }, 180);
          };
          btnCancel?.addEventListener('click', (e) => {
            e.preventDefault();
            cleanup();
          }, {
            once: true
          });
          btnConfirm?.addEventListener('click', (e) => {
            e.preventDefault();
            try {
              localStorage.removeItem('project:create:draft');
              localStorage.removeItem('project:create:step');
              localStorage.removeItem('filepond_tmp_project-attachments');
              if (window.attachmentDrafts && window.attachmentDrafts['filepond_tmp_project-attachments']) {
                try {
                  window.attachmentDrafts['filepond_tmp_project-attachments'].clear();
                } catch (_) {}
              }
            } catch (_) {}
            cleanup();
            if (targetUrl) location.href = targetUrl;
          }, {
            once: true
          });
        };

        document.addEventListener('click', (e) => {
          try {
            const a = e.target.closest('a');
            if (!a) return;
            const href = a.getAttribute('href') || '';
            if (!href || href.startsWith('#') || a.target === '_blank') return;
            if (!dirty) return;
            e.preventDefault();
            e.stopPropagation();
            openModal(a.href);
          } catch (_) {}
        }, true);

        // Cancel button -> ask confirmation and clear drafts if confirmed
        try {
          const cancelBtn = document.getElementById('projectCancelBtn');
          cancelBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            const url = cancelBtn.getAttribute('data-cancel') || '';
            openModal(url);
          });
        } catch (_) {}

        try {
          if (window.Livewire && typeof window.Livewire.navigate === 'function' && !window.Livewire.__projNavGuard) {
            const originalNavigate = window.Livewire.navigate.bind(window.Livewire);
            const guardedNavigate = (url, ...rest) => {
              if (dirty) {
                openModal(url);
                return;
              }
              return originalNavigate(url, ...rest);
            };
            try {
              window.Livewire.navigate = guardedNavigate;
            } catch (_) {
              /* navigate is read-only; skip override */
            }
            window.Livewire.__projNavGuard = true;
          }
        } catch (_) {}
      })();
    </script>
  </div>
</div>
</div>

@endsection

@push('styles')
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
    box-sizing: border-box;
  }

  @media (min-width: 1024px) {
    #projectWizardSteps {
      flex-wrap: nowrap;
      overflow-x: auto;
      justify-content: center;
      scrollbar-width: none;
    }

    #projectWizardSteps::-webkit-scrollbar {
      display: none;
    }
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

  #leaveGuardModal {
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s ease
  }

  #leaveGuardModal.modal-show {
    opacity: 1;
    pointer-events: auto
  }

  body.modal-open {
    overflow: hidden
  }

  /* Allow the create page to scroll when content grows */
  .page-shell {
    min-height: 100vh;
    overflow-y: auto
  }

  /* Actions sit below attachments and page can scroll */
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

  .team-user-picker option {
    padding: .25rem .5rem;
    border-radius: .5rem;
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

  .team-pill .material-icons {
    font-size: 14px;
    line-height: 1;
  }

  [data-step="7"]>.flex.flex-wrap.items-center.justify-between button[data-next-step] {
    display: none !important;
  }
</style>
@endpush

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const STEP_STORAGE_KEY = 'project:create:step';
    const form = document.getElementById('projectCreateForm');
    if (!form) {
      return;
    }
    const projectNoInput = form?.querySelector('input[name="project_no"]');
    const projectNoHint = document.getElementById('project_no_auto');
    const projectNoStored = form?.dataset.nextNumber || '';
    if (projectNoInput && projectNoHint) {
      if (projectNoInput.value.trim() === '' && projectNoStored) {
        projectNoInput.value = projectNoStored;
      }
      projectNoHint.textContent = projectNoInput.value.trim() || projectNoStored || '-';
      projectNoInput.addEventListener('input', () => {
        const manual = projectNoInput.value.trim();
        projectNoHint.textContent = manual || projectNoStored || '-';
      });
    }
    const wizard = document.getElementById('projectWizardSteps');
    const wizardButtons = wizard ? Array.from(wizard.querySelectorAll('.wizard-step')) : [];
    const sections = Array.from(document.querySelectorAll('.step-section'));

    const navSelector = '.flex.flex-wrap.items-center.justify-between';

    const cleanupStepNavigation = () => {
      sections.forEach(section => {
        const navs = Array.from(section.querySelectorAll(navSelector)).filter(nav => {
          return nav.querySelector('button[data-prev-step], button[data-next-step]');
        });

        if (navs.length > 1) {
          const preferredNav = navs.find(nav => nav.classList.contains('attachments-actions')) || navs[navs.length - 1];
          navs.forEach(nav => {
            if (nav !== preferredNav) {
              nav.remove();
            }
          });
        }

        const remainingNavs = Array.from(section.querySelectorAll(navSelector)).filter(nav => nav.querySelector('button[data-prev-step], button[data-next-step]'));

        if (section.dataset.step === '1') {
          remainingNavs.forEach(nav => {
            nav.querySelectorAll('button[data-prev-step]').forEach(button => button.remove());
          });
        }

        if (section.dataset.step === '7') {
          remainingNavs.forEach(nav => {
            nav.querySelectorAll('button[data-next-step]').forEach(button => button.remove());
          });
        }
      });
    };

    const scheduleNavigationCleanup = (() => {
      let scheduled = false;
      return () => {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(() => {
          scheduled = false;
          cleanupStepNavigation();
        });
      };
    })();

    cleanupStepNavigation();

    if (form) {
      const observer = new MutationObserver(() => {
        scheduleNavigationCleanup();
      });
      observer.observe(form, {
        childList: true,
        subtree: true
      });
    }

    let firstActivation = true;

    const activateStep = (target) => {
      cleanupStepNavigation();
      if (!sections.length) return;
      const desired = String(target || '');
      let targetSection = sections.find(section => section.dataset.step === desired);
      if (!targetSection) {
        targetSection = sections[0];
      }
      if (!targetSection) return;
      const activeStep = targetSection.dataset.step;

      sections.forEach(section => {
        if (section === targetSection) {
          section.classList.remove('hidden');
        } else {
          section.classList.add('hidden');
        }
      });

      wizardButtons.forEach(button => {
        const buttonStep = button.dataset.targetStep || button.dataset.stepLabel || button.getAttribute('data-step-label');
        const isActive = buttonStep === activeStep;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-current', isActive ? 'step' : 'false');
      });

      if (form) {
        form.dataset.currentStep = activeStep;
      }

      if (activeStep === '3') {
        setTimeout(() => {
          updateAllPicSelects();
        }, 100);
      }

      if (activeStep === '6') {
        setTimeout(() => {
          document.querySelectorAll('.deliverable-completed-at, .deliverable-verified-at').forEach(el => {
            if (!el._flatpickr) {
              initializeDeliverableDateTimePicker(el);
            }
          });
        }, 100);
      }

      try {
        localStorage.setItem(STEP_STORAGE_KEY, activeStep);
      } catch (_) {}

      if (!firstActivation && wizard) {
        const offset = wizard.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({
          top: offset > 0 ? offset : 0,
          behavior: 'smooth'
        });
      }

      firstActivation = false;
    };

    let defaultStep = sections[0]?.dataset.step || '1';
    try {
      const stored = localStorage.getItem(STEP_STORAGE_KEY);
      if (stored && sections.some(section => section.dataset.step === stored)) {
        defaultStep = stored;
      }
    } catch (_) {}

    activateStep(defaultStep);

    if (defaultStep === '1') {
      setTimeout(() => {
        initializeDatePickers();
      }, 50);
    }

    wizardButtons.forEach(button => {
      button.addEventListener('click', () => {
        const target = button.dataset.targetStep || button.dataset.stepLabel || button.getAttribute('data-step-label');
        if (target) {
          activateStep(target);
          scheduleNavigationCleanup();
          if (target === '1') {
            setTimeout(() => {
              initializeDatePickers();
            }, 50);
          }
        }
      });
    });

    document.querySelectorAll('[data-next-step]').forEach(button => {
      button.addEventListener('click', () => {
        const target = button.dataset.nextStep;
        if (target) {
          activateStep(target);
          if (target === '1') {
            setTimeout(() => {
              initializeDatePickers();
            }, 50);
          }
        }
      });
    });

    document.querySelectorAll('[data-prev-step]').forEach(button => {
      button.addEventListener('click', () => {
        const target = button.dataset.prevStep;
        if (target) {
          activateStep(target);
          scheduleNavigationCleanup();
        }
      });
    });

    const impactOptions = (() => {
      try {
        return JSON.parse(form?.dataset.impactOptions || '[]');
      } catch (_) {
        return [];
      }
    })();
    const likelihoodOptions = (() => {
      try {
        return JSON.parse(form?.dataset.likelihoodOptions || '[]');
      } catch (_) {
        return [];
      }
    })();
    const verifiedByOptions = (() => {
      try {
        return JSON.parse(form?.dataset.verifiedOptions || '[]');
      } catch (_) {
        return [];
      }
    })();

    const initializeDatePickers = () => {
      if (typeof flatpickr !== 'function') return;

      const flatpickrOpts = {
        dateFormat: 'Y-m-d',
        allowInput: true,
        altInput: true,
        altFormat: 'd/m/Y'
      };

      const startInput = document.getElementById('project_start_date');
      const endInput = document.getElementById('project_end_date');

      if (flatpickr.l10ns?.id) {
        flatpickrOpts.locale = flatpickr.l10ns.id;
      }

      if (startInput && !startInput._flatpickr) {
        startInput._flatpickr = flatpickr(startInput, flatpickrOpts);
      }
      if (endInput && !endInput._flatpickr) {
        endInput._flatpickr = flatpickr(endInput, flatpickrOpts);
      }
    };

    const initializeActionDatePicker = (element) => {
      if (typeof flatpickr !== 'function' || !element) return;
      if (element._flatpickr) return;

      const flatpickrOpts = {
        dateFormat: 'Y-m-d',
        allowInput: true,
        altInput: true,
        altFormat: 'd/m/Y'
      };

      if (flatpickr.l10ns?.id) {
        flatpickrOpts.locale = flatpickr.l10ns.id;
      }

      element._flatpickr = flatpickr(element, flatpickrOpts);
    };

    const initializeDeliverableDateTimePicker = (element) => {
      if (typeof flatpickr !== 'function' || !element) return;
      if (element._flatpickr) return;

      const flatpickrOpts = {
        dateFormat: 'Y-m-d H:i',
        enableTime: true,
        time_24hr: true,
        allowInput: true,
        altInput: true,
        altFormat: 'd/m/Y H:i'
      };

      if (flatpickr.l10ns?.id) {
        flatpickrOpts.locale = flatpickr.l10ns.id;
      }

      element._flatpickr = flatpickr(element, flatpickrOpts);
    };

    if (!flatpickr.l10ns || !flatpickr.l10ns.id) {
      const localeScript = document.createElement('script');
      localeScript.src = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js';
      localeScript.onload = () => {
        initializeDatePickers();
      };
      localeScript.onerror = () => {
        initializeDatePickers();
      };
      document.head.appendChild(localeScript);
    } else {
      initializeDatePickers();
    }

    let teamOptions = [];

    const getTeamOptions = () => {
      const wrap = document.getElementById('membersWrap');
      if (!wrap) return teamOptions;
      
      const rows = Array.from(wrap.querySelectorAll('[data-member]'));
      if (rows.length === 0) return teamOptions;
      
      return rows.map(row => ({
        user_id: row.dataset.user,
        id: row.dataset.user,
        label: row.dataset.label || row.querySelector('.member-position')?.value || 'User',
        position: row.querySelector('.member-position')?.value?.trim() || ''
      }));
    };

    const toDateValue = (value) => {
      if (!value) return '';
      if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
      if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(value)) return value.slice(0, 10);
      if (/^\d{2}\/\d{2}\/\d{4}$/.test(value)) {
        const [d, m, y] = value.split('/');
        return `${y}-${m}-${d}`;
      }
      const date = new Date(value);
      if (!Number.isNaN(date.getTime())) {
        return date.toISOString().slice(0, 10);
      }
      return '';
    };

    const toDateTimeValue = (value) => {
      if (!value) return '';
      if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(value)) return value;
      if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value)) {
        return value.replace(' ', 'T').slice(0, 16);
      }
      const date = new Date(value);
      if (!Number.isNaN(date.getTime())) {
        return date.toISOString().slice(0, 16);
      }
      return '';
    };

    const fillPicOptions = (selectEl, selected) => {
      if (!selectEl) return;
      const requestedRaw = selected !== undefined ? selected : selectEl.dataset.requested;
      const requested = requestedRaw == null ? '' : String(requestedRaw);
      selectEl.dataset.requested = requested;

      const currentTeam = getTeamOptions();
      const options = document.createDocumentFragment();
      const defaultOpt = document.createElement('option');
      defaultOpt.value = '';
      defaultOpt.textContent = '-- Pilih PIC --';
      options.appendChild(defaultOpt);
      currentTeam.forEach(member => {
        const opt = document.createElement('option');
        opt.value = String(member.user_id ?? member.id ?? '');
        const extra = member.position ? ` (${member.position})` : '';
        opt.textContent = `${member.label || member.name || 'User'}${extra}`;
        options.appendChild(opt);
      });
      selectEl.innerHTML = '';
      selectEl.appendChild(options);
      if (requested && currentTeam.some(member => String(member.user_id ?? member.id ?? '') === requested)) {
        selectEl.value = requested;
      } else {
        selectEl.value = '';
      }
    };

    let updateAllPicSelects = () => {};

    document.addEventListener('project:team:update', (event) => {
      const detail = Array.isArray(event.detail) ? event.detail : [];
      teamOptions = detail;
      setTimeout(() => {
        updateAllPicSelects();
      }, 50);
    });

    const btnAdd = document.getElementById('btnAddMember');
    const wrap = document.getElementById('membersWrap');
    const tpl = document.getElementById('memberRowTpl');
    const pillWrap = document.getElementById('teamPills');
    const multiSelect = document.getElementById('teamUserSelect');
    const quickList = document.getElementById('teamQuickList');
    const hasTeamControls = btnAdd && wrap && tpl && multiSelect;

    if (hasTeamControls) {
      let counter = 0;

      const getRows = () => Array.from(wrap.querySelectorAll('[data-member]'));
      const findRow = (userId) => wrap.querySelector(`[data-member][data-user="${userId}"]`);
      const getLabel = (userId) => {
        const opt = multiSelect.querySelector(`option[value="${userId}"]`);
        return opt ? opt.text.trim() : `User #${userId}`;
      };

      const setQuickState = (userId, active) => {
        const btn = quickList?.querySelector(`[data-user-id="${userId}"]`);
        if (!btn) return;
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
      };

      const syncMultiSelect = () => {
        const selected = new Set(getRows().map(row => row.dataset.user));
        Array.from(multiSelect.options).forEach(opt => {
          opt.selected = selected.has(opt.value);
        });
      };

      const updatePills = () => {
        if (!pillWrap) return;
        pillWrap.innerHTML = '';

        const rows = getRows();
        if (!rows.length) {
          const placeholder = document.createElement('span');
          placeholder.className = 'text-xs text-slate-400';
          placeholder.textContent = 'Belum ada anggota yang dipilih.';
          pillWrap.appendChild(placeholder);
          return;
        }

        rows.forEach(row => {
          const userId = row.dataset.user;
          if (!userId) return;
          const label = row.dataset.label || getLabel(userId);
          const role = row.querySelector('.member-position')?.value?.trim();
          const pill = document.createElement('button');
          pill.type = 'button';
          pill.className = 'team-pill flex items-center gap-2 rounded-full border border-emerald-300/70 bg-emerald-50 px-3 py-1 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-100';
          pill.innerHTML = `<span>${label}${role ? ' · ' + role : ''}</span><span class="material-icons text-[14px] leading-none">close</span>`;
          pill.addEventListener('click', () => {
            removeRow(userId);
          });
          pillWrap.appendChild(pill);
        });
      };

      const emitTeamUpdate = () => {
        const payload = getRows().map(row => ({
          user_id: row.dataset.user,
          label: row.dataset.label || getLabel(row.dataset.user),
          position: row.querySelector('.member-position')?.value?.trim() || ''
        }));
        teamOptions = payload;
        document.dispatchEvent(new CustomEvent('project:team:update', {
          detail: payload
        }));
      };

      const removeRow = (userId) => {
        const row = findRow(userId);
        if (!row) return;
        row.remove();
        setQuickState(userId, false);
        syncMultiSelect();
        updatePills();
        emitTeamUpdate();
      };

      const buildRow = (userId, position = '') => {
        if (!userId) return;
        const existingRow = findRow(userId);
        if (existingRow) {
          const existingRole = existingRow.querySelector('.member-position');
          if (existingRole && position !== undefined) {
            existingRole.value = position;
          }
          existingRow.dataset.position = position || '';
          setQuickState(userId, true);
          syncMultiSelect();
          updatePills();
          emitTeamUpdate();
          return existingRow;
        }

        const label = getLabel(userId);
        const html = tpl.innerHTML
          .replace(/__INDEX__/g, counter++)
          .replace(/__USER_ID__/g, userId)
          .replace(/__LABEL__/g, label)
          .replace(/__POSITION__/g, position || '');
        const tmp = document.createElement('div');
        tmp.innerHTML = html.trim();
        const row = tmp.firstElementChild;
        if (!row) return;

        const roleInput = row.querySelector('.member-position');
        const removeBtn = row.querySelector('.btn-remove-member');
        row.dataset.user = userId;
        row.dataset.label = label;
        row.dataset.position = position || '';

        roleInput?.addEventListener('input', () => {
          row.dataset.position = roleInput.value || '';
          updatePills();
          emitTeamUpdate();
        });
        removeBtn?.addEventListener('click', () => removeRow(userId));

        wrap.appendChild(row);
        setQuickState(userId, true);
        syncMultiSelect();
        updatePills();
        emitTeamUpdate();

        return row;
      };

      btnAdd.addEventListener('click', () => {
        const selected = Array.from(multiSelect.selectedOptions).map(opt => opt.value);
        if (!selected.length) return;
        selected.forEach(id => buildRow(id));
        emitTeamUpdate();
      });

      multiSelect.addEventListener('change', () => {
        const selected = new Set(Array.from(multiSelect.selectedOptions).map(opt => opt.value));
        // Remove rows not selected
        getRows().forEach(row => {
          if (!selected.has(row.dataset.user)) {
            removeRow(row.dataset.user);
          }
        });
        // Add new rows
        selected.forEach(id => buildRow(id));
        emitTeamUpdate();
      });

      quickList?.addEventListener('click', (e) => {
        const btn = e.target.closest('.team-quick-btn');
        if (!btn) return;
        const userId = btn.dataset.userId;
        if (!userId) return;
        if (findRow(userId)) {
          removeRow(userId);
        } else {
          buildRow(userId);
        }
      });

      let initialMembers = [];
      try {
        const raw = wrap.dataset.old || '';
        if (raw) {
          const parsed = JSON.parse(raw);
          if (Array.isArray(parsed)) {
            initialMembers = parsed;
          } else if (parsed && typeof parsed === 'object') {
            initialMembers = Object.values(parsed);
          }
        }
      } catch (e) {
        initialMembers = [];
      }

      if (initialMembers.length) {
        initialMembers.forEach(member => {
          if (!member) return;
          const userId = member.user_id || member.user_select || member.id || '';
          if (!userId) return;
          buildRow(userId, member.position || member.role || '');
        });
      }

      syncMultiSelect();
      updatePills();
      emitTeamUpdate();
      
      setTimeout(() => {
        updateAllPicSelects();
      }, 100);
    }

    const descEditorEl = document.getElementById('project_description_editor');
    const descHiddenEl = document.getElementById('description');
    const descWrapEl = document.getElementById('projectDescWrap');
    const descCounterEl = document.getElementById('descriptionCounter');
    if (descEditorEl && descHiddenEl && typeof Quill !== 'undefined') {
      let quill = descEditorEl.__quill;
      if (!quill) {
        quill = new Quill(descEditorEl, {
          theme: 'snow',
          placeholder: 'Tuliskan tujuan, ruang lingkup, dan informasi penting project.',
          modules: {
            toolbar: [
              [{
                header: [1, 2, false]
              }],
              ['bold', 'italic', 'underline', 'strike'],
              [{
                list: 'ordered'
              }, {
                list: 'bullet'
              }],
              ['link', 'code-block'],
              ['clean'],
            ],
          },
        });
        descEditorEl.__quill = quill;
      }

      const syncDesc = () => {
        const html = quill.root.innerHTML;
        descHiddenEl.value = html;
        const text = (quill.getText() || '').trim();
        if (descCounterEl) {
          const words = text === '' ? 0 : text.split(/\s+/).length;
          descCounterEl.textContent = words + ' kata';
        }
      };

      if (descHiddenEl.value) {
        quill.clipboard.dangerouslyPasteHTML(descHiddenEl.value);
      }
      syncDesc();

      quill.on('text-change', syncDesc);
      quill.on('selection-change', (range) => {
        descWrapEl?.classList.toggle('is-focused', !!range);
      });
    }

    const actionsWrap = document.getElementById('actionsWrap');
    const actionTpl = document.getElementById('actionTpl');
    const subActionTpl = document.getElementById('subActionTpl');
    const btnAddAction = document.getElementById('btnAddAction');
    let actionCounter = 0;

    const updateSubOrders = (actionNode) => {
      actionNode?.querySelectorAll('.subaction-card').forEach((card, idx) => {
        const orderEl = card.querySelector('.subaction-order');
        if (orderEl) orderEl.textContent = idx + 1;
      });
    };

    const updateActionOrders = () => {
      if (!actionsWrap) return;
      actionsWrap.querySelectorAll('.action-card').forEach((card, idx) => {
        const orderEl = card.querySelector('.action-order');
        if (orderEl) orderEl.textContent = idx + 1;
      });
    };

    const buildSubaction = (actionNode, actionIndex, data = {}) => {
      if (!subActionTpl) return null;
      const subWrap = actionNode.querySelector('[data-subaction-wrap]');
      if (!subWrap) return null;
      const currentCounter = Number(actionNode.dataset.subCounter || '0');
      const subIndex = typeof data.__index === 'number' ? data.__index : currentCounter;
      const nextCounter = Math.max(currentCounter, subIndex + 1);
      actionNode.dataset.subCounter = String(nextCounter);

      const html = subActionTpl.innerHTML
        .replace(/__ACTION_INDEX__/g, actionIndex)
        .replace(/__SUB_INDEX__/g, subIndex);
      const tmp = document.createElement('div');
      tmp.innerHTML = html.trim();
      const node = tmp.firstElementChild;
      if (!node) return null;

      const titleInput = node.querySelector(`[name="project_actions[${actionIndex}][subactions][${subIndex}][title]"]`);
      if (titleInput && data.title) titleInput.value = data.title;

      const statusSelect = node.querySelector(`[name="project_actions[${actionIndex}][subactions][${subIndex}][status_id]"]`);
      if (statusSelect && data.status_id) {
        statusSelect.value = data.status_id;
      }

      const progressInput = node.querySelector(`[name="project_actions[${actionIndex}][subactions][${subIndex}][progress]"]`);
      if (progressInput && typeof data.progress !== 'undefined') {
        progressInput.value = data.progress;
      }

      const picSelect = node.querySelector(`[name="project_actions[${actionIndex}][subactions][${subIndex}][pic_user_id]"]`);
      if (picSelect) {
        fillPicOptions(picSelect, data.pic_user_id);
        picSelect.addEventListener('change', () => {
          picSelect.dataset.requested = picSelect.value || '';
        });
      }

      const startInput = node.querySelector(`[name="project_actions[${actionIndex}][subactions][${subIndex}][start_date]"]`);
      if (startInput) {
        startInput.value = toDateValue(data.start_date || '');
        setTimeout(() => initializeActionDatePicker(startInput), 50);
      }

      const endInput = node.querySelector(`[name="project_actions[${actionIndex}][subactions][${subIndex}][end_date]"]`);
      if (endInput) {
        endInput.value = toDateValue(data.end_date || '');
        setTimeout(() => initializeActionDatePicker(endInput), 50);
      }

      const descInput = node.querySelector(`[name="project_actions[${actionIndex}][subactions][${subIndex}][description]"]`);
      if (descInput && data.description) descInput.value = data.description;

      node.querySelector('.btn-remove-subaction')?.addEventListener('click', () => {
        node.remove();
        updateSubOrders(actionNode);
      });

      subWrap.appendChild(node);
      updateSubOrders(actionNode);
      return node;
    };

    const buildAction = (data = {}) => {
      if (!actionsWrap || !actionTpl) return null;
      const index = typeof data.__index === 'number' ? data.__index : actionCounter++;
      if (index >= actionCounter) {
        actionCounter = index + 1;
      }
      const html = actionTpl.innerHTML.replace(/__INDEX__/g, index);
      const tmp = document.createElement('div');
      tmp.innerHTML = html.trim();
      const node = tmp.firstElementChild;
      if (!node) return null;

      node.dataset.actionIndex = String(index);
      node.dataset.subCounter = '0';

      const titleInput = node.querySelector(`[name="project_actions[${index}][title]"]`);
      if (titleInput && data.title) titleInput.value = data.title;

      const statusSelect = node.querySelector(`[name="project_actions[${index}][status_id]"]`);
      if (statusSelect && data.status_id) {
        statusSelect.value = data.status_id;
      }

      const progressInput = node.querySelector(`[name="project_actions[${index}][progress]"]`);
      if (progressInput && typeof data.progress !== 'undefined') {
        progressInput.value = data.progress;
      }

      const picSelect = node.querySelector(`[name="project_actions[${index}][pic_user_id]"]`);
      if (picSelect) {
        fillPicOptions(picSelect, data.pic_user_id);
        picSelect.addEventListener('change', () => {
          picSelect.dataset.requested = picSelect.value || '';
        });
      }

      const startInput = node.querySelector(`[name="project_actions[${index}][start_date]"]`);
      if (startInput) {
        startInput.value = toDateValue(data.start_date || '');
        setTimeout(() => initializeActionDatePicker(startInput), 50);
      }

      const endInput = node.querySelector(`[name="project_actions[${index}][end_date]"]`);
      if (endInput) {
        endInput.value = toDateValue(data.end_date || '');
        setTimeout(() => initializeActionDatePicker(endInput), 50);
      }

      const descInput = node.querySelector(`[name="project_actions[${index}][description]"]`);
      if (descInput && data.description) descInput.value = data.description;

      const subactions = Array.isArray(data.subactions) ? data.subactions : [];
      subactions.forEach((subData, subIdx) => {
        buildSubaction(node, index, {
          ...subData,
          __index: subIdx
        });
      });

      node.querySelector('.btn-remove-action')?.addEventListener('click', () => {
        node.remove();
        updateActionOrders();
      });

      node.querySelector('.btn-add-subaction')?.addEventListener('click', () => {
        buildSubaction(node, index, {});
      });

      actionsWrap.appendChild(node);
      updateActionOrders();
      return node;
    };

    if (actionsWrap && actionTpl) {
      updateAllPicSelects = () => {
        setTimeout(() => {
          actionsWrap.querySelectorAll('.action-pic-select').forEach(select => {
            fillPicOptions(select);
          });
          actionsWrap.querySelectorAll('.subaction-pic-select').forEach(select => {
            fillPicOptions(select);
          });
        }, 50);
      };

      let initialActions = [];
      try {
        const raw = actionsWrap.dataset.old || '';
        if (raw) {
          const parsed = JSON.parse(raw);
          if (Array.isArray(parsed)) {
            initialActions = parsed;
          } else if (parsed && typeof parsed === 'object') {
            initialActions = Object.values(parsed);
          }
        }
      } catch (_) {
        initialActions = [];
      }

      if (initialActions.length) {
        initialActions.forEach((actionData, idx) => {
          const prepared = actionData || {};
          prepared.__index = idx;
          if (Array.isArray(prepared.subactions)) {
            prepared.subactions = prepared.subactions.map((sub, subIdx) => ({
              ...(sub || {}),
              __index: subIdx
            }));
          }
          buildAction(prepared);
        });
      }

      btnAddAction?.addEventListener('click', () => {
        buildAction({});
      });

      updateAllPicSelects();
    }

    // ==================== COST MANAGER ====================
    const costsWrap = document.getElementById('costsWrap');
    const costTpl = document.getElementById('costTpl');
    const btnAddCost = document.getElementById('btnAddCost');
    let costCounter = 0;

    const updateCostOrders = () => {
      if (!costsWrap) return;
      costsWrap.querySelectorAll('.cost-card').forEach((card, idx) => {
        const orderEl = card.querySelector('.cost-order');
        if (orderEl) orderEl.textContent = idx + 1;
      });
    };

    const buildCost = (data = {}) => {
      if (!costsWrap || !costTpl) return null;
      const index = typeof data.__index === 'number' ? data.__index : costCounter++;
      if (index >= costCounter) {
        costCounter = index + 1;
      }
      const html = costTpl.innerHTML.replace(/__INDEX__/g, index);
      const tmp = document.createElement('div');
      tmp.innerHTML = html.trim();
      const node = tmp.firstElementChild;
      if (!node) return null;

      const costItem = node.querySelector(`[name="project_costs[${index}][cost_item]"]`);
      if (costItem && data.cost_item) costItem.value = data.cost_item;

      const category = node.querySelector(`[name="project_costs[${index}][category]"]`);
      if (category && data.category) category.value = data.category;

      const estimated = node.querySelector(`[name="project_costs[${index}][estimated_cost]"]`);
      if (estimated && typeof data.estimated_cost !== 'undefined') estimated.value = data.estimated_cost;

      const actual = node.querySelector(`[name="project_costs[${index}][actual_cost]"]`);
      if (actual && typeof data.actual_cost !== 'undefined' && data.actual_cost !== null) actual.value = data.actual_cost;

      const notes = node.querySelector(`[name="project_costs[${index}][notes]"]`);
      if (notes && data.notes) notes.value = data.notes;

      node.querySelector('.btn-remove-cost')?.addEventListener('click', () => {
        node.remove();
        updateCostOrders();
      });

      costsWrap.appendChild(node);
      updateCostOrders();
      return node;
    };

    if (costsWrap && costTpl) {
      let initialCosts = [];
      try {
        const raw = costsWrap.dataset.old || '';
        if (raw) {
          const parsed = JSON.parse(raw);
          if (Array.isArray(parsed)) {
            initialCosts = parsed;
          } else if (parsed && typeof parsed === 'object') {
            initialCosts = Object.values(parsed);
          }
        }
      } catch (_) {
        initialCosts = [];
      }

      initialCosts.forEach((costData, idx) => buildCost({
        ...(costData || {}),
        __index: idx
      }));

      btnAddCost?.addEventListener('click', () => buildCost({}));
    }

    // ==================== RISK MANAGER ====================
    const risksWrap = document.getElementById('risksWrap');
    const riskTpl = document.getElementById('riskTpl');
    const btnAddRisk = document.getElementById('btnAddRisk');
    let riskCounter = 0;

    const updateRiskOrders = () => {
      if (!risksWrap) return;
      risksWrap.querySelectorAll('.risk-card').forEach((card, idx) => {
        const orderEl = card.querySelector('.risk-order');
        if (orderEl) orderEl.textContent = idx + 1;
      });
    };

    const buildRisk = (data = {}) => {
      if (!risksWrap || !riskTpl) return null;
      const index = typeof data.__index === 'number' ? data.__index : riskCounter++;
      if (index >= riskCounter) {
        riskCounter = index + 1;
      }
      const html = riskTpl.innerHTML.replace(/__INDEX__/g, index);
      const tmp = document.createElement('div');
      tmp.innerHTML = html.trim();
      const node = tmp.firstElementChild;
      if (!node) return null;

      const nameInput = node.querySelector(`[name="project_risks[${index}][name]"]`);
      if (nameInput && data.name) nameInput.value = data.name;

      const statusSelect = node.querySelector(`[name="project_risks[${index}][status_id]"]`);
      if (statusSelect) statusSelect.value = data.status_id || '';

      const impactSelect = node.querySelector(`[name="project_risks[${index}][impact]"]`);
      if (impactSelect) {
        const impactVal = typeof data.impact === 'string' ? data.impact.toLowerCase() : '';
        const foundImpact = impactOptions.find(opt => String(opt).toLowerCase() === impactVal);
        if (foundImpact) impactSelect.value = foundImpact;
      }

      const likelihoodSelect = node.querySelector(`[name="project_risks[${index}][likelihood]"]`);
      if (likelihoodSelect) {
        const likeVal = typeof data.likelihood === 'string' ? data.likelihood.toLowerCase() : '';
        const foundLike = likelihoodOptions.find(opt => String(opt).toLowerCase() === likeVal);
        if (foundLike) likelihoodSelect.value = foundLike;
      }

      const descInput = node.querySelector(`[name="project_risks[${index}][description]"]`);
      if (descInput && data.description) descInput.value = data.description;

      const mitigationInput = node.querySelector(`[name="project_risks[${index}][mitigation_plan]"]`);
      if (mitigationInput && data.mitigation_plan) mitigationInput.value = data.mitigation_plan;

      node.querySelector('.btn-remove-risk')?.addEventListener('click', () => {
        node.remove();
        updateRiskOrders();
      });

      risksWrap.appendChild(node);
      updateRiskOrders();
      return node;
    };

    if (risksWrap && riskTpl) {
      let initialRisks = [];
      try {
        const raw = risksWrap.dataset.old || '';
        if (raw) {
          const parsed = JSON.parse(raw);
          if (Array.isArray(parsed)) {
            initialRisks = parsed;
          } else if (parsed && typeof parsed === 'object') {
            initialRisks = Object.values(parsed);
          }
        }
      } catch (_) {
        initialRisks = [];
      }

      initialRisks.forEach((riskData, idx) => buildRisk({
        ...(riskData || {}),
        __index: idx
      }));

      btnAddRisk?.addEventListener('click', () => buildRisk({}));
    }

    // ==================== DELIVERABLE MANAGER ====================
    const deliverablesWrap = document.getElementById('deliverablesWrap');
    const deliverableTpl = document.getElementById('deliverableTpl');
    const btnAddDeliverable = document.getElementById('btnAddDeliverable');
    let deliverableCounter = 0;

    const updateDeliverableOrders = () => {
      if (!deliverablesWrap) return;
      deliverablesWrap.querySelectorAll('.deliverable-card').forEach((card, idx) => {
        const orderEl = card.querySelector('.deliverable-order');
        if (orderEl) orderEl.textContent = idx + 1;
      });
    };

    const buildDeliverable = (data = {}) => {
      if (!deliverablesWrap || !deliverableTpl) return null;
      const index = typeof data.__index === 'number' ? data.__index : deliverableCounter++;
      if (index >= deliverableCounter) {
        deliverableCounter = index + 1;
      }
      const html = deliverableTpl.innerHTML.replace(/__INDEX__/g, index);
      const tmp = document.createElement('div');
      tmp.innerHTML = html.trim();
      const node = tmp.firstElementChild;
      if (!node) return null;

      const nameInput = node.querySelector(`[name="project_deliverables[${index}][name]"]`);
      if (nameInput && data.name) nameInput.value = data.name;

      const statusSelect = node.querySelector(`[name="project_deliverables[${index}][status_id]"]`);
      if (statusSelect) statusSelect.value = data.status_id || '';

      const completedInput = node.querySelector(`[name="project_deliverables[${index}][completed_at]"]`);
      if (completedInput) {
        if (data.completed_at) completedInput.value = toDateTimeValue(data.completed_at);
        setTimeout(() => initializeDeliverableDateTimePicker(completedInput), 50);
      }

      const verifiedInput = node.querySelector(`[name="project_deliverables[${index}][verified_at]"]`);
      if (verifiedInput) {
        if (data.verified_at) verifiedInput.value = toDateTimeValue(data.verified_at);
        setTimeout(() => initializeDeliverableDateTimePicker(verifiedInput), 50);
      }

      const verifiedBySelect = node.querySelector(`[name="project_deliverables[${index}][verified_by]"]`);
      if (verifiedBySelect) {
        const verifiedValue = typeof data.verified_by === 'string' ? data.verified_by.toLowerCase() : '';
        const foundVerified = verifiedByOptions.find(opt => String(opt).toLowerCase() === verifiedValue);
        if (foundVerified) verifiedBySelect.value = foundVerified;
      }

      const descInput = node.querySelector(`[name="project_deliverables[${index}][description]"]`);
      if (descInput && data.description) descInput.value = data.description;

      node.querySelector('.btn-remove-deliverable')?.addEventListener('click', () => {
        node.remove();
        updateDeliverableOrders();
      });

      deliverablesWrap.appendChild(node);
      updateDeliverableOrders();
      return node;
    };

    if (deliverablesWrap && deliverableTpl) {
      let initialDeliverables = [];
      try {
        const raw = deliverablesWrap.dataset.old || '';
        if (raw) {
          const parsed = JSON.parse(raw);
          if (Array.isArray(parsed)) {
            initialDeliverables = parsed;
          } else if (parsed && typeof parsed === 'object') {
            initialDeliverables = Object.values(parsed);
          }
        }
      } catch (_) {
        initialDeliverables = [];
      }

      initialDeliverables.forEach((deliverableData, idx) => buildDeliverable({
        ...(deliverableData || {}),
        __index: idx
      }));

      btnAddDeliverable?.addEventListener('click', () => buildDeliverable({}));
    }

    const convertDateFormat = (value) => {
      if (!value) return '';
      if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
      if (/^\d{2}\/\d{2}\/\d{4}$/.test(value)) {
        const [day, month, year] = value.split('/');
        return `${year}-${month}-${day}`;
      }
      return value;
    };

    form?.addEventListener('submit', (e) => {
      const inputs = form.querySelectorAll('input[type="text"][name*="start_date"], input[type="text"][name*="end_date"], input[type="text"][name*="completed_at"], input[type="text"][name*="verified_at"]');
      inputs.forEach(input => {
        if (input.value && input._flatpickr) {
          const converted = convertDateFormat(input.value);
          if (converted) {
            input.value = converted;
          }
        }
      });
    });

  });
</script>
@endpush
