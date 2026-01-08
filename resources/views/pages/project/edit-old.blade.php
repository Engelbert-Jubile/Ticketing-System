@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-3xl">
    @php
    $originFrom = request('from');
    $backTo = request('src') === 'detail'
    ? route('projects.show', ['project' => $project->public_slug ?? $project->id, 'from' => $originFrom])
    : ($originFrom ?: (url()->previous() ?: route('projects.report')));
    @endphp

    @include('components.back', ['to' => $backTo])

    <h1 class="text-2xl font-bold mb-4">Edit Project</h1>

    <form action="{{ route('projects.update', $project) }}" method="POST"
        class="space-y-6 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        @csrf
        @method('PUT')

        @if(!empty($originFrom))
        <input type="hidden" name="from" value="{{ $originFrom }}">
        @endif

        {{-- TITLE --}}
        <div>
            <label class="block mb-1">Title</label>
            <input type="text" name="title" value="{{ old('title', $project->title) }}"
                class="w-full border p-2 rounded @error('title') border-red-600 @enderror">
            @error('title')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        {{-- DESCRIPTION --}}
        <div>
            <label class="block mb-1">Description</label>
            <textarea name="description" rows="4"
                class="w-full border p-2 rounded @error('description') border-red-600 @enderror">{{ old('description', $project->description) }}</textarea>
            @error('description')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        {{-- STATUS --}}
        <div>
            <label class="block mb-1">Status</label>
            @php $cur = old('status', $project->status); @endphp
            <select name="status"
                class="w-full border p-2 rounded @error('status') border-red-600 @enderror" required>
                <option value="new" {{ $cur === 'new' ? 'selected' : '' }}>New</option>
                <option value="in_progress" {{ $cur === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="confirmation" {{ $cur === 'confirmation' ? 'selected' : '' }}>Confirmation</option>
                <option value="revision" {{ $cur === 'revision' ? 'selected' : '' }}>Revision</option>
                <option value="done" {{ $cur === 'done' ? 'selected' : '' }}>Done</option>
            </select>
            @error('status')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        @isset($project->project_no)
        <div>
            <label class="block mb-1">No Project</label>
            <input type="text" name="project_no" value="{{ old('project_no', $project->project_no) }}"
                class="w-full border p-2 rounded @error('project_no') border-red-600 @enderror" maxlength="11">
            @error('project_no')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>
        @endisset

        @isset($tickets)
        <div>
            <label class="block mb-1">Ticket (opsional)</label>
            <select name="ticket_id" class="w-full border p-2 rounded @error('ticket_id') border-red-600 @enderror">
                <option value="">— Tidak mengubah Ticket —</option>
                @foreach ($tickets as $t)
                <option value="{{ $t->id }}"
                    {{ (string) old('ticket_id', $project->ticket_id) === (string) $t->id ? 'selected' : '' }}>
                    {{ $t->ticket_no }} (ID: {{ $t->id }})
                </option>
                @endforeach
            </select>
            @error('ticket_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>
        @endisset

        {{-- =========================  TIMELINE  ========================= --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-3 text-lg font-semibold">Timeline</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block mb-1">Start Date</label>
                    <input type="text"
                        name="timeline[start]"
                        value="{{ old('timeline.start', optional($project->start_date)->format('d/m/Y')) }}"
                        class="flatpickr-date w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm
                                  focus:border-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        placeholder="dd/mm/yyyy" autocomplete="off">
                </div>
                <div>
                    <label class="block mb-1">End Date</label>
                    <input type="text"
                        name="timeline[end]"
                        value="{{ old('timeline.end', optional($project->end_date)->format('d/m/Y')) }}"
                        class="flatpickr-date w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm
                                  focus:border-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        placeholder="dd/mm/yyyy" autocomplete="off">
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Format: dd/mm/yyyy</p>
        </div>

        {{-- =========================  PLANNING  ========================= --}}
        <div id="planning-section" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-3 text-lg font-semibold">Planning</h2>

            <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                Tambahkan aktivitas/milestone (contoh: “Workshop”, “Master data collection”, dll).
            </p>

            <div class="mb-2 flex items-center gap-2">
                <button type="button" id="btnAddPlan"
                    class="rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">+ Tambah Baris</button>
                <button type="button" id="btnClearPlan"
                    class="rounded border border-amber-300 px-3 py-1.5 text-xs text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900/30">Clear</button>
            </div>

            <div id="planWrap" class="space-y-2"
                data-old='@json(old("planning", $project->planning ?? []))'>
            </div>

            <template id="planRowTpl">
                <div class="grid gap-2 md:grid-cols-12 items-start">
                    <div class="md:col-span-5">
                        <input type="text" name="planning[__INDEX__][title]" placeholder="Judul / Aktivitas"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm
                                      focus:border-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                    </div>
                    <div class="md:col-span-2">
                        <input type="text" name="planning[__INDEX__][week]" placeholder="Minggu (cth: 1–2)"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm
                                      focus:border-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                    </div>
                    <div class="md:col-span-3">
                        <select name="planning[__INDEX__][status]"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm
                                       focus:border-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
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
                        <input type="text" name="planning[__INDEX__][note]" placeholder="Catatan (opsional)"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm
                                      focus:border-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                    </div>
                </div>
            </template>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Simpan
            </button>
            <a href="{{ $backTo }}" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Datepicker for timeline
        if (window.flatpickr) {
            flatpickr(".flatpickr-date", {
                dateFormat: "d/m/Y",
                allowInput: true
            });
        }

        // auto-scroll ke Planning bila ?tab=planning
        try {
            const url = new URL(window.location.href);
            if (url.searchParams.get('tab') === 'planning') {
                document.getElementById('planning-section')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        } catch (e) {}

        const wrap = document.getElementById('planWrap');
        const tpl = document.getElementById('planRowTpl');
        const btnAdd = document.getElementById('btnAddPlan');
        const btnClear = document.getElementById('btnClearPlan');
        let idx = 0;

        function wireRow(row) {
            row.querySelector('.btn-remove-plan')?.addEventListener('click', () => row.remove());
        }

        function addRow(data = {}) {
            const html = tpl.innerHTML.replaceAll('__INDEX__', idx);
            const div = document.createElement('div');
            div.innerHTML = html.trim();
            const row = div.firstElementChild;

            row.querySelector(`[name="planning[${idx}][title]"]`).value = data.title || '';
            row.querySelector(`[name="planning[${idx}][week]"]`).value = data.week || '';
            row.querySelector(`[name="planning[${idx}][status]"]`).value = data.status || '';
            row.querySelector(`[name="planning[${idx}][note]"]`).value = data.note || '';

            wireRow(row);
            wrap.appendChild(row);
            idx++;
        }

        // restore
        let old = [];
        try {
            old = JSON.parse(wrap.dataset.old || '[]');
        } catch (e) {
            old = [];
        }
        if (Array.isArray(old) && old.length) {
            old.forEach(o => addRow(o || {}));
        } else {
            addRow();
        }

        btnAdd?.addEventListener('click', () => addRow());
        btnClear?.addEventListener('click', () => {
            wrap.innerHTML = '';
            idx = 0;
            addRow();
        });
    });
</script>
@endpush
