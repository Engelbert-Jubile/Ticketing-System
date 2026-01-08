@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
@php
    use App\Support\WorkflowStatus;

    $priorityInit = old('priority', $task->priority ?? '');
    $assigneeId   = old('assignee_id', $task->assignee_id);
    $assignedTo   = old('assigned_to', $task->assigned_to);

    $tz = config('app.timezone');
    $dueAtVal = old('due_at', '');
    if ($dueAtVal === '') {
        $raw = $task->due_at ?? $task->due_date ?? null;
        if (!empty($raw)) {
            try {
                $dueAtVal = \Carbon\Carbon::parse($raw)->timezone($tz)->format('d/m/Y H:i');
            } catch (\Throwable $e) {
                $dueAtVal = '';
            }
        }
    }

    $assigneeMode = old('assignee_mode') ?: ($assigneeId ? 'user' : ($assignedTo ? 'text' : 'none'));
    $users = $users ?? \App\Models\User::orderBy('email')->get();

    $statusLabels  = collect(WorkflowStatus::labels());
    $statusOptions = $statusLabels->keys()->all();
    $currentStatus = WorkflowStatus::normalize($task->status);
    $defaultStatus = WorkflowStatus::normalize(old('status', $currentStatus));
@endphp

<div class="container mx-auto px-4 py-6 max-w-2xl">
    {{-- kembali ke asal --}}
    @include('components.back', ['to' => request('from', route('tasks.index'))])

    <h1 class="text-2xl font-bold mb-6">Edit Task</h1>

    <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="from" value="{{ request('from') }}">

        {{-- Judul --}}
        <div>
            <label for="title" class="block font-medium mb-1">Judul</label>
            <input type="text" name="title" id="title" value="{{ old('title', $task->title) }}"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900
                          dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
            @error('title') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Deskripsi --}}
        <div>
            <label for="description" class="block font-medium mb-1">Deskripsi</label>
            <textarea name="description" id="description" rows="4"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900
                             dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $task->description) }}</textarea>
            @error('description') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Status --}}
        <div>
            <label for="status" class="block font-medium mb-1">Status</label>
            <select name="status" id="status"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900
               dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
                @foreach ($statusOptions as $s)
                    <option value="{{ $s }}" {{ $defaultStatus === $s ? 'selected' : '' }}>
                        {{ $statusLabels[$s] ?? WorkflowStatus::label($s) }}
                    </option>
                @endforeach
            </select>
            @error('status') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>


        {{-- Meta: Priority & Due --}}
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="priority" class="block font-medium mb-1">Priority</label>
                <select name="priority" id="priority"
                    class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900
                               dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">— Pilih —</option>
                    @foreach (['low'=>'Low','normal'=>'Normal','high'=>'High','critical'=>'Critical'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ $priorityInit === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
                @error('priority') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="due_at" class="block font-medium mb-1">Due</label>
                <input type="text" name="due_at" id="due_at" value="{{ $dueAtVal }}"
                    placeholder="dd/mm/yyyy hh:mm"
                    class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900
                              dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 flatpickr-due">
                @error('due_at') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Assignee --}}
        <div class="rounded-lg border p-3 border-gray-200 dark:border-gray-700">
            <div class="mb-2 font-medium">Assignee</div>

            <div class="flex flex-wrap items-center gap-4 mb-3 text-sm">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="assignee_mode" value="none" {{ $assigneeMode==='none' ? 'checked' : '' }}>
                    <span>Tidak ditetapkan</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="assignee_mode" value="user" {{ $assigneeMode==='user' ? 'checked' : '' }}>
                    <span>Pilih User</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="assignee_mode" value="text" {{ $assigneeMode==='text' ? 'checked' : '' }}>
                    <span>Ketik Nama</span>
                </label>
            </div>

            <div id="assigneeUserWrap" class="{{ $assigneeMode==='user' ? '' : 'hidden' }}">
                <label for="assignee_id" class="block text-sm text-gray-600 dark:text-gray-300 mb-1">User</label>
                <select name="assignee_id" id="assignee_id"
                    class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900
                               dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">— Pilih user —</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (string)$assigneeId === (string)$u->id ? 'selected' : '' }}>
                        {{ $u->name ?? $u->email }} {{ $u->email ? '— '.$u->email : '' }}
                    </option>
                    @endforeach
                </select>
                @error('assignee_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div id="assigneeTextWrap" class="{{ $assigneeMode==='text' ? '' : 'hidden' }} mt-3">
                <label for="assigned_to" class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Nama</label>
                <input type="text" name="assigned_to" id="assigned_to" value="{{ $assignedTo }}"
                    class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900
                              dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                    placeholder="Ketik nama penanggung jawab">
                @error('assigned_to') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Jika memilih <b>Pilih User</b>, kolom “Ketik Nama” akan diabaikan.
            </p>
        </div>

        <div class="pt-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Update
            </button>
            <a href="{{ request('from', route('tasks.index')) }}" class="ml-2 px-4 py-2 rounded border">
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
        // Due picker (24-hour)
        flatpickr(".flatpickr-due", {
            enableTime: true,
            dateFormat: "d/m/Y H:i",
            time_24hr: true,
            allowInput: true
        });

        // Toggle assignee mode
        const radios = document.querySelectorAll('input[name="assignee_mode"]');
        const wrapUser = document.getElementById('assigneeUserWrap');
        const wrapText = document.getElementById('assigneeTextWrap');
        const selUser = document.getElementById('assignee_id');
        const inpText = document.getElementById('assigned_to');

        function sync() {
            const v = document.querySelector('input[name="assignee_mode"]:checked')?.value || 'none';
            wrapUser.classList.toggle('hidden', v !== 'user');
            wrapText.classList.toggle('hidden', v !== 'text');
            if (v === 'user') {
                if (inpText) inpText.value = '';
            }
            if (v === 'text') {
                if (selUser) selUser.value = '';
            }
            if (v === 'none') {
                if (inpText) inpText.value = '';
                if (selUser) selUser.value = '';
            }
        }
        radios.forEach(r => r.addEventListener('change', sync));
        sync();
    });
</script>
@endpush
