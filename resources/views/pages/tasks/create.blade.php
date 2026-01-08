{{-- LEGACY (fallback) resources/views/pages/tasks/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Task Baru')

@section('content')
<div class="page-theme page-theme--task">
  <div class="page-shell page-shell--wide py-6">
    <div class="space-y-8">
    {{-- Back button --}}
    <div class="flex justify-center md:justify-start">
      @include('components.back', ['to' => request('from', route('dashboard')), 'text' => 'Dashboard', 'icon' => 'home'])
    </div>

    <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 p-6 text-white shadow-xl">
      <div class="relative z-[1] flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-3xl font-semibold">Buat Task Baru</h2>
          <p class="mt-3 max-w-2xl text-sm text-white/80">Tiga langkah untuk membuat task Anda: Informasi task, Data &amp; timeline, dan Lampiran pendukung.</p>
        </div>
        <div class="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 text-sm backdrop-blur">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
              <path d="M7.5 2.25A2.25 2.25 0 0 0 5.25 4.5v15a.75.75 0 0 0 1.06.67l4.19-2.094a.75.75 0 0 1 .66 0l4.19 2.094a.75.75 0 0 0 1.06-.67v-15A2.25 2.25 0 0 0 15.75 2.25h-8.25Z" />
            </svg>
          </div>
          <div>
            <div class="font-semibold">Status Task: {{ strtoupper(\App\Support\WorkflowStatus::default()) }}</div>
            <p class="text-xs text-white/80">Status dapat diperbarui setelah task dibuat.</p>
          </div>
        </div>
      </div>
      <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
    </div>

    {{-- Leave confirmation modal --}}
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

    @if (session('success'))
      <div class="mb-6 rounded border border-emerald-300 bg-emerald-50 text-emerald-800 px-4 py-3">
        {{ session('success') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-700">
        <div class="mb-1 font-semibold">Periksa kembali beberapa isian:</div>
        <ul class="list-disc list-inside space-y-0.5">
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form id="taskCreateForm"
      action="{{ route('tasks.store') }}"
      method="POST"
      enctype="multipart/form-data"
      class="space-y-8"
      data-has-old="{{ $errors->any() ? '1' : '' }}"
      data-success="{{ session('success') ? '1' : '' }}"
      data-default-status="{{ \App\Support\WorkflowStatus::default() }}"
      data-dashboard-url="{{ route('dashboard') }}"
    >
      @csrf
      <input type="hidden" name="from" value="{{ request()->fullUrl() }}">
      <input type="hidden" name="due_at" id="due_at">

      <div class="mb-6">
        <ol id="taskWizardSteps" class="flex flex-wrap justify-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
          <li data-step-label="1" class="wizard-step">Informasi Task</li>
          <li data-step-label="2" class="wizard-step">Data &amp; Timeline</li>
          <li data-step-label="3" class="wizard-step">Lampiran</li>
        </ol>
      </div>

      {{-- Step 1 --}}
      <div data-step="1" class="step-section space-y-6">
        <div class="grid gap-6 lg:grid-cols-2">
          <div class="rounded-2xl border border-gray-200 bg-white/90 p-6 shadow-sm backdrop-blur-sm transition-all dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-start gap-3">
              <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-200">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                  <path d="M16.5 4.5v15a.75.75 0 0 1-1.102.659L12 18.576l-3.398 1.583A.75.75 0 0 1 7.5 19.5v-15a.75.75 0 0 1 1.102-.659L12 5.424l3.398-1.583a.75.75 0 0 1 1.102.659Z" />
                </svg>
              </span>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Informasi Task</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Isi judul dan deskripsi untuk memudahkan pencarian dan tracking.</p>
              </div>
            </div>
            <label for="title" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Judul Task</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" required
              class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 shadow-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/40">
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Tuliskan poin utama task secara singkat.</p>
          </div>

          <div class="rounded-2xl border border-gray-200 bg-white/90 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Detail Deskripsi</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Berikan konteks tambahan dan kebutuhan task.</p>
              </div>
              <span id="descCounter" class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-200">0 kata</span>
            </div>
            <div id="descWrap" class="ql-card rounded-xl border border-gray-200 shadow-inner dark:border-gray-700">
              <div id="description_editor" class="min-h-[200px] rounded-xl bg-white dark:bg-gray-900"></div>
            </div>
            <textarea id="description" name="description" class="hidden">{{ old('description') }}</textarea>
          </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Relasi</h3>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Opsional: hubungkan task ini dengan ticket tertentu.</p>
          <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium">Ticket terkait</label>
              <select name="ticket_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">-- Tanpa ticket --</option>
                @foreach ($tickets as $ticket)
                  <option value="{{ $ticket->id }}" @selected(old('ticket_id') == $ticket->id)>{{ $ticket->ticket_no ?? ('Ticket #' . $ticket->id) }} â€” {{ \Illuminate\Support\Str::limit($ticket->title, 48) }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium">Output</label>
              <select id="output_type" name="output_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="task" @selected(old('output_type', 'task') === 'task')>Tetap sebagai Task</option>
                <option value="task_project" @selected(old('output_type') === 'task_project')>Otomatis buat Project</option>
              </select>
            </div>
          </div>
        </div>
        <div class="flex flex-col gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/60 px-5 py-4 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">
          <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-emerald-600 shadow dark:bg-emerald-500/20 dark:text-emerald-100">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                <path d="M7.5 3A1.5 1.5 0 0 0 6 4.5v12a1.5 1.5 0 0 0 2.2 1.3l3.8-2.1 3.8 2.1A1.5 1.5 0 0 0 18 16.5v-12A1.5 1.5 0 0 0 16.5 3h-9Z" />
              </svg>
            </span>
            <div>
              <div class="font-semibold">Langkah berikutnya</div>
              <p class="text-xs">Lanjut ke Data &amp; Timeline</p>
            </div>
          </div>
          <div class="text-right">
            <button type="button" data-next-step="2"
              class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-300 dark:focus:ring-offset-gray-900">
              <span>Lanjut ke Data &amp; Timeline</span>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                <path fill-rule="evenodd" d="M4.5 12a.75.75 0 0 1 .75-.75h11.69l-3.22-3.22a.75.75 0 1 1 1.06-1.06l4.5 4.5a.75.75 0 0 1 0 1.06l-4.5 4.5a.75.75 0 1 1-1.06-1.06l3.22-3.22H5.25A.75.75 0 0 1 4.5 12Z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      {{-- Step 2 --}}
      <div data-step="2" class="step-section hidden space-y-6">
        <div class="grid gap-6 lg:grid-cols-2">
          <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Data Task</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tentukan prioritas dan status task.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium">Prioritas</label>
                <select name="priority" required class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                  <option value="" @selected(old('priority', '')==='')>-- Pilih --</option>
                  @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'critical' => 'Critical'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority') === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">Status</label>
                <input type="hidden" name="status" value="{{ \App\Support\WorkflowStatus::default() }}">
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                  {{ \App\Support\WorkflowStatus::label(\App\Support\WorkflowStatus::default()) }}
                  <span class="block text-[11px] text-gray-500 dark:text-gray-400">Status awal selalu New saat membuat task.</span>
                </div>
              </div>
              <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-medium">Assign ke User (Bisa pilih lebih dari 1)</label>
                <div class="mt-4 space-y-4">
                  <div>
                    <label class="mb-1 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                      <span>Daftar User</span>
                      <span class="font-normal normal-case text-gray-400">Gunakan CTRL/Shift untuk pilih lebih dari satu</span>
                    </label>
                    <select id="taskAssigneesSelect" class="task-assignee-picker" multiple size="8">
                      @foreach ($users as $user)
                        @php
                          $assigneesVal = old('assignees', []);
                          if (is_string($assigneesVal)) {
                            try { $assigneesVal = json_decode($assigneesVal, true) ?? []; } catch (\Throwable) { $assigneesVal = []; }
                          }
                          $isSelected = is_array($assigneesVal) && in_array($user->id, $assigneesVal);
                        @endphp
                        <option value="{{ $user->id }}" @selected($isSelected)>{{ $user->display_name ?? $user->email }}</option>
                      @endforeach
                    </select>
                  </div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Gunakan tombol di bawah atau pilih langsung dari list di atas untuk assign ke user.</p>
                  <div id="taskAssigneeQuickList" class="task-quick-list">
                    @foreach ($users as $user)
                      <button type="button" class="task-quick-btn" data-user-id="{{ $user->id }}" data-user-label="{{ $user->display_name ?? $user->email }}">{{ $user->display_name ?? $user->email }}</button>
                    @endforeach
                  </div>
                </div>

                <div id="taskAssigneePills" class="mt-4 flex flex-wrap gap-2"></div>

                <div id="taskAssigneesWrap" class="mt-4 space-y-2"></div>

                <input type="hidden" id="assigneesInput" name="assignees" value="{{ json_encode(old('assignees', [])) }}">
              </div>

              @if(auth()->user() && (auth()->user()->hasAnyRole(['superadmin', 'Super Admin']) || auth()->user()->hasRole('Admin')))
              <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-medium">Requester (Hanya untuk Super Admin & Admin)</label>
                <select name="requester_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                  <option value="">-- Pilih Requester --</option>
                  @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('requester_id') == $user->id)>{{ $user->display_name ?? $user->email }}</option>
                  @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Jika tidak dipilih, yang membuat task akan menjadi requester.</p>
              </div>
              @endif
            </div>
          </div>

          <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Timeline</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Atur target selesai task.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
              <div class="space-y-2">
                <label class="mb-1 block text-sm font-medium" for="due_date">Due Date</label>
                <input id="due_date" name="due_date" type="text" required value="{{ old('due_date') }}" placeholder="dd/mm/yyyy" autocomplete="off"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
              </div>
              <div class="space-y-2">
                <label class="mb-1 block text-sm font-medium" for="due_time">Due Time</label>
                <input id="due_time" name="due_time" type="text" value="{{ old('due_time') }}" placeholder="hh:mm" autocomplete="off"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-400 focus:ring-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <p id="due_hint" class="text-xs text-gray-500 dark:text-gray-400"></p>
              </div>
            </div>
          </div>
        </div>

        <div id="projectFields" class="rounded-2xl border border-blue-100 bg-blue-50/70 p-6 shadow-sm dark:border-blue-500/30 dark:bg-blue-500/10 @if(old('output_type') !== 'task_project') hidden @endif">
          <h3 class="text-sm font-semibold text-blue-700 dark:text-blue-200">Opsi Project Otomatis</h3>
          <p class="mt-1 text-xs text-blue-600 dark:text-blue-300">Isi detail berikut jika ingin langsung membuat project dari task ini.</p>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <label class="mb-1 block text-sm font-medium">Judul Project</label>
              <input type="text" name="project_title" value="{{ old('project_title') }}" placeholder="Judul project"
                class="w-full rounded-lg border border-blue-200 px-3 py-2 shadow-sm focus:border-blue-400 focus:ring-blue-300 dark:border-blue-500/50 dark:bg-blue-500/10 dark:text-blue-100">
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium">Tanggal Mulai</label>
              <input type="date" name="project_start" value="{{ old('project_start') }}"
                class="w-full rounded-lg border border-blue-200 px-3 py-2 shadow-sm focus:border-blue-400 focus:ring-blue-300 dark:border-blue-500/50 dark:bg-blue-500/10 dark:text-blue-100">
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium">Tanggal Selesai</label>
              <input type="date" name="project_end" value="{{ old('project_end') }}"
                class="w-full rounded-lg border border-blue-200 px-3 py-2 shadow-sm focus:border-blue-400 focus:ring-blue-300 dark:border-blue-500/50 dark:bg-blue-500/10 dark:text-blue-100">
            </div>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
          <button type="button" data-prev-step="1" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Kembali</button>
          <div class="flex flex-col gap-2 text-sm md:flex-row md:items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">Pintasan: <kbd class="rounded border px-1">Ctrl/Cmd</kbd> + <kbd class="rounded border px-1">S</kbd></span>
            <div class="flex items-center gap-2">
              <button type="button" data-next-step="3" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lanjut</button>
            </div>
          </div>
        </div>
      </div>

      {{-- Step 3 --}}
      <div data-step="3" class="step-section hidden space-y-6">
        @include('partials.attachments', [
          'initialAttachments' => collect(),
          'toggleDefault' => true,
          'inputId' => 'task-attachments'
        ])
        <div class="attachments-actions mt-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
          <button type="button" data-prev-step="2" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Kembali</button>
          <div class="flex flex-col gap-2 text-sm md:flex-row md:items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">Pintasan: <kbd class="rounded border px-1">Ctrl/Cmd</kbd> + <kbd class="rounded border px-1">S</kbd></span>
            <div class="flex items-center gap-2">
              <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-white shadow hover:bg-green-700">
                <span>Simpan</span>
              </button>
              <button type="button" id="taskCancelBtn" data-cancel="{{ route('tasks.create') }}" data-confirm-title="Batal buat task?"
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-white shadow hover:bg-red-700">Batal</button>
              <a href="{{ request('from', route('tasks.index')) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Kembali</a>
            </div>
          </div>
          </div>
        </div>
      </div>
    </form>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
  .page-theme--task { --brandA: #10b981; --brandB: #14b8a6; }
  html{scroll-behavior:smooth}
  /* Allow the create page to scroll when content grows */
  .page-shell{min-height:100vh;overflow-y:auto}
  #taskWizardSteps{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:.5rem .6rem;margin:0 auto 1rem auto;padding:.25rem .5rem;max-width:100%;box-sizing:border-box}
  .wizard-step{display:inline-flex;align-items:center;gap:.55rem;padding:.5rem 1rem;border-radius:9999px;border:1px solid rgba(99,102,241,.25);background:rgba(255,255,255,.85);color:#475569;font-weight:600;line-height:1;white-space:nowrap;transition:all .2s ease;box-shadow:0 8px 16px -14px rgba(14,165,233,.35)}
  .wizard-step::before{content:attr(data-step-label);display:inline-flex;align-items:center;justify-content:center;width:1.6rem;height:1.6rem;min-width:1.6rem;border-radius:999px;background:rgba(79,70,229,.12);color:#4f46e5;font-weight:700}
  .wizard-step.is-active{color:#fff;border-color:transparent;background:linear-gradient(135deg,#4f46e5,#0ea5e9);box-shadow:0 16px 28px -24px rgba(14,165,233,.55)}
  .wizard-step.is-active::before{background:rgba(255,255,255,.35);color:#fff}
  .dark .wizard-step{background:rgba(15,23,42,.75);border-color:rgba(129,140,248,.35);color:#e0e7ff}
  .dark .wizard-step::before{background:rgba(129,140,248,.26);color:#c7d2fe}
  .dark .wizard-step.is-active{background:linear-gradient(135deg,#6366f1,#22d3ee)}
  .ql-card .ql-toolbar.ql-snow{border-radius:.75rem .75rem 0 0;border:none;border-bottom:1px solid rgba(148,163,184,.3)}
  .ql-card .ql-container.ql-snow{border:none;border-radius:0 0 .75rem .75rem;min-height:200px}
  #leaveGuardModal{opacity:0;pointer-events:none;transition:opacity .2s ease}
  #leaveGuardModal.modal-show{opacity:1;pointer-events:auto}
  body.modal-open{overflow:hidden}

  /* Actions sit below attachments and page can scroll */
  .attachments-actions{position:static;margin-top:1rem;padding-top:.75rem;border-top:1px solid rgba(148,163,184,.35);background:transparent}
  .dark .attachments-actions{border-color:rgba(148,163,184,.35)}

  .task-quick-list{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem}
  .task-quick-btn{padding:.5rem 1rem;border:1px dashed #d1d5db;border-radius:9999px;background:#fff;font-size:.875rem;font-weight:500;color:#374151;cursor:pointer;transition:all 200ms ease}
  .task-quick-btn:hover{border-color:#0ea5e9;background:#f0f9ff;color:#0369a1}
  .task-quick-btn[data-selected="true"]{border:1px solid #0ea5e9;background:#0ea5e9;color:#fff}
  .dark .task-quick-btn{background:#1f2937;border-color:#4b5563;color:#d1d5db}
  .dark .task-quick-btn:hover{border-color:#06b6d4;background:#083344;color:#06b6d4}
  .dark .task-quick-btn[data-selected="true"]{background:#0ea5e9;border-color:#0ea5e9;color:#fff}
  #taskAssigneePills{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem}
  .task-pill{display:inline-flex;align-items:center;gap:.5rem;padding:.375rem .75rem;background:#e0e7ff;border-radius:9999px;font-size:.875rem;font-weight:500;color:#3730a3}
  .task-pill .btn-remove-pill{margin-left:.25rem;padding:0;border:none;background:none;cursor:pointer;opacity:.7;transition:opacity 200ms ease;color:inherit}
  .task-pill .btn-remove-pill:hover{opacity:1}
  .dark .task-pill{background:#3730a3;color:#e0e7ff}

</style>
@endpush

@push('scripts')
<script>
  // Task Assignees - Sync between select, quick buttons, and display
  (function() {
    const selectEl = document.getElementById('taskAssigneesSelect');
    const quickBtnsContainer = document.getElementById('taskAssigneeQuickList');
    const pillsEl = document.getElementById('taskAssigneePills');
    const wrapEl = document.getElementById('taskAssigneesWrap');
    const inputEl = document.getElementById('assigneesInput');
    const quickBtns = document.querySelectorAll('.task-quick-btn');

    let selectedIds = [];

    try {
      const jsonVal = inputEl.value;
      if (jsonVal && jsonVal !== '[]') {
        selectedIds = JSON.parse(jsonVal);
      }
    } catch (e) {
      selectedIds = [];
    }

    function updateUI() {
      if (selectEl) {
        Array.from(selectEl.options).forEach(opt => {
          opt.selected = selectedIds.includes(parseInt(opt.value));
        });
      }

      quickBtns.forEach(btn => {
        const userId = parseInt(btn.dataset.userId);
        btn.dataset.selected = selectedIds.includes(userId) ? 'true' : 'false';
      });

      renderPills();
      renderCards();
    }

    function renderPills() {
      pillsEl.innerHTML = selectedIds.map(userId => {
        const btn = Array.from(quickBtns).find(b => parseInt(b.dataset.userId) === userId);
        const label = btn ? btn.dataset.userLabel : 'User #' + userId;
        return `<div class="task-pill"><span>${label}</span><button type="button" class="btn-remove-pill" data-user-id="${userId}">✕</button></div>`;
      }).join('');

      pillsEl.querySelectorAll('.btn-remove-pill').forEach(btn => {
        btn.addEventListener('click', e => {
          e.preventDefault();
          const userId = parseInt(btn.dataset.userId);
          selectedIds = selectedIds.filter(id => id !== userId);
          updateUI();
        });
      });

      updateInput();
    }

    function renderCards() {
      wrapEl.innerHTML = selectedIds.map(userId => {
        const btn = Array.from(quickBtns).find(b => parseInt(b.dataset.userId) === userId);
        const label = btn ? btn.dataset.userLabel : 'User #' + userId;
        return `
          <div class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/30">
            <div class="text-sm font-medium text-blue-900 dark:text-blue-100">${label}</div>
            <button type="button" class="btn-remove-card inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-100 dark:text-red-300 dark:hover:bg-red-900/30" data-user-id="${userId}">
              <span>Hapus</span>
            </button>
          </div>
        `;
      }).join('');

      wrapEl.querySelectorAll('.btn-remove-card').forEach(btn => {
        btn.addEventListener('click', e => {
          e.preventDefault();
          const userId = parseInt(btn.dataset.userId);
          selectedIds = selectedIds.filter(id => id !== userId);
          updateUI();
        });
      });
    }

    function updateInput() {
      inputEl.value = JSON.stringify(selectedIds);
    }

    function toggleUser(userId) {
      userId = parseInt(userId);
      const idx = selectedIds.indexOf(userId);
      if (idx >= 0) {
        selectedIds.splice(idx, 1);
      } else {
        selectedIds.push(userId);
      }
      updateUI();
    }

    if (selectEl) {
      selectEl.addEventListener('change', () => {
        selectedIds = Array.from(selectEl.selectedOptions).map(opt => parseInt(opt.value));
        updateUI();
      });
    }

    quickBtns.forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        toggleUser(btn.dataset.userId);
      });
    });

    updateUI();
  })();
</script>

@endpush

<script>
  (function(){
    // Selaraskan kunci dengan partial attachments (storageKey = 'filepond_tmp_'+inputId)
    const ATTACH_KEY = 'filepond_tmp_task-attachments';
    let dirty = false;
    try { document.addEventListener('create:dirty', () => { dirty = true; }, true); } catch(_){}
    const step1 = document.querySelector('[data-step="1"]');
    if (step1) {
      step1.addEventListener('input', () => { dirty = true; }, true);
      step1.addEventListener('change', () => { dirty = true; }, true);
    }

    const openModal = (targetUrl) => {
      const modal = document.getElementById('leaveGuardModal');
      const btnCancel = document.getElementById('leaveGuardCancel');
      const btnConfirm = document.getElementById('leaveGuardConfirm');
      if (!modal) { if (targetUrl) location.href = targetUrl; return; }
      modal.classList.remove('hidden');
      requestAnimationFrame(() => modal.classList.add('modal-show'));
      const cleanup = () => { modal.classList.remove('modal-show'); setTimeout(() => { if (!modal.classList.contains('modal-show')) modal.classList.add('hidden'); }, 180); };
      btnCancel?.addEventListener('click', (e)=>{ e.preventDefault(); cleanup(); }, { once: true });
      btnConfirm?.addEventListener('click', (e)=>{
        e.preventDefault();
        try {
          localStorage.removeItem('task:create:draft');
          localStorage.removeItem('task:create:step');
          localStorage.removeItem(ATTACH_KEY);
          if (window.attachmentDrafts && window.attachmentDrafts[ATTACH_KEY]) {
            try { window.attachmentDrafts[ATTACH_KEY].clear(); } catch(_){}
          }
        } catch(_){}
        cleanup();
        if (targetUrl) location.href = targetUrl;
      }, { once: true });
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
      } catch(_){ }
    }, true);

    try {
      const cancelBtn = document.getElementById('taskCancelBtn');
      cancelBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const url = cancelBtn.getAttribute('data-cancel') || '';
        openModal(url);
      });
    } catch(_){}

    // Clear drafts on successful save
    try {
      const form = document.getElementById('taskCreateForm');
      if (form && form.dataset && form.dataset.success === '1') {
        localStorage.removeItem('task:create:draft');
        localStorage.removeItem('task:create:step');
        localStorage.removeItem(ATTACH_KEY);
        if (window.attachmentDrafts && window.attachmentDrafts[ATTACH_KEY]) {
          try { window.attachmentDrafts[ATTACH_KEY].clear(); } catch(_){}
        }
      }
    } catch(_){}
  })();
</script>




