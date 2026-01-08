@extends('layouts.app')

@section('title', 'Hasil Pencarian untuk "' . e($query) . '"')

@push('styles')
<style>
    .status-badge,
    .priority-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25em 0.6em;
        font-size: 0.75rem;
        font-weight: 500;
        border-radius: 9999px;
        line-height: 1;
        background-color: var(--bg-color, #E5E7EB);
        color: var(--text-color, #374151);
    }

    .badge-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        margin-right: 0.4em;
        background-color: var(--text-color, #374151);
    }

    .search-description p {
        margin: 0;
        padding: 0;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="mb-8">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors mb-3">
            <span class="material-icons">arrow_back</span>
            Kembali
        </a>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">
            Hasil Pencarian: <span class="text-blue-600">"{{ e($query) }}"</span>
        </h1>
    </div>

    @if($tickets->isEmpty() && $tasks->isEmpty() && $projects->isEmpty())
    <div class="flex flex-col items-center justify-center text-center bg-white dark:bg-gray-800 rounded-lg shadow-md p-12">
        <span class="material-icons text-7xl text-gray-400 dark:text-gray-500 mb-4">search_off</span>
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300">Tidak Ada Hasil Ditemukan</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-2">Coba gunakan kata kunci lain yang lebih umum.</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 search-grid">

        @foreach($projects as $project)
        <a data-card-link="1" wire:navigate.hover href="{{ route('projects.show', ['project' => $project->public_slug ?? $project->id]) }}" class="flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <span class="material-icons text-blue-500">inventory_2</span>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 truncate" title="{{ e($project->title) }}">{{ e($project->title) }}</h3>
                </div>
            </div>
            <div class="p-5 flex-grow search-description">
                <div class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3">{{ strip_tags($project->description) }}</div>
            </div>
            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-lg border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-medium">PROJECT</span>
                    <span>Dibuat: {{ $project->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </a>
        @endforeach

        @foreach($tickets as $ticket)
        <a data-card-link="1" wire:navigate.hover href="{{ route('tickets.show', $ticket) }}" class="flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <span class="material-icons text-green-500">confirmation_number</span>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 truncate" title="{{ e($ticket->title) }}">{{ e($ticket->title) }}</h3>
                </div>
            </div>
            <div class="p-5 flex-grow search-description">
                <div class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3">{{ strip_tags($ticket->description) }}</div>
            </div>
            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-lg border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    @if($ticket->statusRelation)
                    <span class="status-badge" style="--bg-color: {{ $ticket->statusRelation->bg_color }}; --text-color: {{ $ticket->statusRelation->text_color }};">
                        <span class="badge-dot"></span>
                        {{ e($ticket->statusRelation->name) }}
                    </span>
                    @endif
                    @if($ticket->priorityRelation)
                    <span class="priority-badge" style="--bg-color: {{ $ticket->priorityRelation->bg_color }}; --text-color: {{ $ticket->priorityRelation->text_color }};">
                        {{ e($ticket->priorityRelation->name) }}
                    </span>
                    @endif
                    <span>{{ $ticket->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </a>
        @endforeach

        {{-- =================== PATCH DITERAPKAN DI SINI =================== --}}
        @foreach($tasks as $task)
        <a data-card-link="1" wire:navigate.hover href="{{ route('tasks.show', ['taskSlug' => $task->public_slug]) }}" class="flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <span class="material-icons text-purple-500">task_alt</span>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 truncate" title="{{ e($task->title) }}">{{ e($task->title) }}</h3>
                </div>
            </div>
            <div class="p-5 flex-grow search-description">
                <div class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3">{{ strip_tags($task->description) }}</div>
            </div>
            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-lg border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    @if($task->status)
                    @php
                        $statusValueRaw = $task->status instanceof \BackedEnum ? $task->status->value : $task->status;
                        $statusValue = strtolower((string) $statusValueRaw);
                        $bgColor = match($statusValue) {
                            'new' => 'bg-blue-100 text-blue-800',
                            'in_progress', 'on_progress', 'pending' => 'bg-yellow-100 text-yellow-800',
                            'confirmation' => 'bg-indigo-100 text-indigo-800',
                            'revision' => 'bg-rose-100 text-rose-800',
                            'completed', 'done' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-gray-100 text-gray-800',
                            default => 'bg-gray-100 text-gray-800',
                        };
                    @endphp
                    <span class="status-badge {{ $bgColor }}">
                        {{ $task->status_label }}
                    </span>
                    @endif
                    @if($task->requester)
                    <span class="font-medium">Oleh: {{ e($task->requester->display_name) }}</span>
                    @endif
                    <span>{{ $task->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </a>
        @endforeach
        {{-- ===================== AKHIR PATCH ===================== --}}

    </div>
    @endif
</div>
@endsection



@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  var cards = document.querySelectorAll('.search-grid a[data-card-link]');
  cards.forEach(function(a){
    // bubble listener: if prevented by inner element, force navigate
    a.addEventListener('click', function(ev){
      if (ev.defaultPrevented) {
        window.location.href = a.href;
      }
    });
    // keyboard accessibility
    a.setAttribute('role','link');
    a.setAttribute('tabindex','0');
    a.addEventListener('keydown', function(e){
      if (e.key === 'Enter') { window.location.href = a.href; }
    });
  });
});
</script>
@endpush
