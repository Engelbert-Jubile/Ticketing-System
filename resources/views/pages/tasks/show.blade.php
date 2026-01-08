{{-- LEGACY (fallback) resources/views/pages/tasks/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Task Detail - ' . $task->title)

@section('content')
<div class="page-shell page-shell--wide py-6 space-y-6">

    {{-- Back button --}}
    <div class="flex justify-start">
        @include('components.back', ['to' => request('from', url()->previous() ?: route('tasks.index')), 'text' => 'Kembali', 'icon' => 'arrow_back'])
    </div>

    {{-- Header dengan Title dan Status --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 p-6 text-white shadow-xl">
        <div class="relative z-[1] flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="mb-2 text-sm font-semibold opacity-90">{{ $task->task_no }}</div>
                <h1 class="text-3xl font-bold mb-2">{{ $task->title }}</h1>
                @if($task->description)
                <div class="text-sm text-white/80 line-clamp-2">{{ strip_tags($task->description) }}</div>
                @endif
            </div>
            @include('components.task.promote-button', ['task' => $task, 'size' => 'md'])
        </div>
        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Content (2 columns) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Description Section --}}
            @if($task->description)
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-gray-100">Deskripsi</h2>
                <div class="prose max-w-none text-gray-700 dark:prose-invert dark:text-gray-300">
                    {!! $task->description !!}
                </div>
            </div>
            @endif

            {{-- Attachments Section --}}
            @if($task->attachments && $task->attachments->count() > 0)
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-gray-100">Lampiran ({{ $task->attachments->count() }})</h2>
                <div class="space-y-2">
                    @foreach($task->attachments as $att)
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex-1 truncate">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $att->original_name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format(($att->size ?? 0) / 1024, 1) }} KB</div>
                        </div>
                        <div class="flex items-center gap-2 ml-3">
                            <a href="{{ route('attachments.view', $att) }}" target="_blank"
                                class="inline-flex items-center gap-1 rounded-lg border border-blue-200 px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-900/30">
                                Lihat
                            </a>
                            <a href="{{ route('attachments.download', $att) }}"
                                class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800/60">
                                Unduh
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar (1 column) --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            @php
                $raw = $task->status ?? null;
                $statusValue = $raw instanceof \BackedEnum ? $raw->value : (string) $raw;
                $normalized  = \App\Support\WorkflowStatus::normalize($statusValue);
                $label       = \App\Support\WorkflowStatus::label($normalized);
                $badgeClass  = \App\Support\WorkflowStatus::badgeClass($normalized);
            @endphp
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Status</h3>
                <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold ring-1 ring-inset {{ $badgeClass }}">
                    {{ $label }}
                </span>
            </div>

            {{-- Priority Card --}}
            @if($task->priority)
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Prioritas</h3>
                @php
                    $priorityColor = match($task->priority) {
                        'critical' => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
                        'high' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300',
                        'normal' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
                        'low' => 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300',
                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-300',
                    };
                @endphp
                <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $priorityColor }}">
                    {{ ucfirst($task->priority) }}
                </span>
            </div>
            @endif

            {{-- Assignee Card --}}
            @php
                $assigneeNames = collect();
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
                            $assigneeNames->push($name);
                        }
                    }
                }

                if (!empty($assignedIds)) {
                    $usersMap = \App\Models\User::whereIn('id', $assignedIds)->get()->keyBy('id');
                    foreach ($assignedIds as $assignedId) {
                        $user = $usersMap->get($assignedId);
                        if ($user) {
                            $assigneeNames->push($user->display_name ?? $user->email ?? ('User #' . $assignedId));
                        }
                    }
                }

                if ($task->assignee_id && $task->assignee) {
                    $assigneeNames->prepend($task->assignee->display_name ?? $task->assignee->email);
                }

                $assigneeNames = $assigneeNames->filter()->unique()->values();
            @endphp
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Ditugaskan ke</h3>
                @if($assigneeNames->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach($assigneeNames as $assignee)
                            <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                {{ $assignee }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">— Tidak ada assignee —</span>
                @endif
            </div>

            {{-- Requester Card --}}
            @if($task->requester)
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Pembuat</h3>
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-sm font-semibold text-green-600 dark:bg-green-500/20 dark:text-green-200">
                        {{ substr($task->requester->display_name ?? $task->requester->email, 0, 1) }}
                    </div>
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $task->requester->display_name ?? $task->requester->email }}
                    </div>
                </div>
            </div>
            @endif

            {{-- Due Date Card --}}
            @if($task->due_at)
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Target Selesai</h3>
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $task->due_at->format('d M Y H:i') }}
                </div>
            </div>
            @endif

            {{-- Timeline Card --}}
            @if($task->start_date || $task->end_date)
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Timeline</h3>
                @if($task->start_date)
                <div class="mb-2">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Mulai:</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $task->start_date->format('d M Y') }}</div>
                </div>
                @endif
                @if($task->end_date)
                <div>
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Selesai:</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $task->end_date->format('d M Y') }}</div>
                </div>
                @endif
            </div>
            @endif

            {{-- Related Ticket Card --}}
            @if($task->ticket)
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Ticket Terkait</h3>
                <a href="{{ route('tickets.show', $task->ticket) }}" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-300">
                    {{ $task->ticket->ticket_no ?? ('Ticket #' . $task->ticket->id) }}
                </a>
            </div>
            @endif

            {{-- Related Project Card --}}
            @if($task->project)
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Project Terkait</h3>
                <a href="{{ route('projects.show', ['project' => $task->project->public_slug ?? $task->project->id]) }}" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-300">
                    {{ $task->project->title }}
                </a>
            </div>
            @endif

            {{-- Metadata Card --}}
            <div class="rounded-2xl border border-gray-200 bg-white/95 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Informasi</h3>
                <div class="space-y-2 text-xs">
                    <div>
                        <span class="font-semibold text-gray-600 dark:text-gray-400">Dibuat:</span>
                        <div class="text-gray-900 dark:text-gray-100">{{ $task->created_at?->format('d M Y H:i') }}</div>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600 dark:text-gray-400">Diperbarui:</span>
                        <div class="text-gray-900 dark:text-gray-100">{{ $task->updated_at?->format('d M Y H:i') }}</div>
                    </div>
                    @if($task->completed_at)
                    <div>
                        <span class="font-semibold text-gray-600 dark:text-gray-400">Selesai:</span>
                        <div class="text-gray-900 dark:text-gray-100">{{ $task->completed_at->format('d M Y H:i') }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="space-y-2">
                <a href="{{ route('tasks.edit', ['task' => $task->public_slug]) }}"
                    class="block w-full rounded-lg bg-blue-600 px-4 py-3 text-center font-semibold text-white hover:bg-blue-700 transition">
                    Edit Task
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
