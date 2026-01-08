{{-- resources/views/pages/ticket/on-progress.blade.php --}}
@extends('layouts.app')

@section('title', 'Tickets - In Progress')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Back --}}
    @include('components.back', ['to' => route('dashboard')])

    <h1 class="text-2xl font-bold mb-2">Tickets - In Progress</h1>
    <p class="text-gray-600 mb-4">List of tickets currently in progress.</p>

    <div class="overflow-hidden">
        <table class="w-full table-auto border-collapse">
            <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="border p-2 w-12">#</th>
                    <th class="border p-2">Title</th>
                    <th class="border p-2">Description</th>
                    <th class="border p-2 w-48">Status</th>
                    <th class="border p-2 w-40">Updated</th>
                    <th class="border p-2 w-[210px]">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($tickets as $ticket)
                    @php
                        $status = $ticket->status instanceof \BackedEnum ? $ticket->status->value : ($ticket->status ?? 'new');
                        $status = \App\Support\WorkflowStatus::normalize($status);
                        $label  = \App\Support\WorkflowStatus::label($status);
                    @endphp

                    <tr class="hover:bg-gray-50">
                        <td class="border p-2 align-middle">{{ $loop->iteration }}</td>

                        <td class="border p-2 align-middle">
                            <span class="font-medium text-gray-900 truncate block">
                                {{ $ticket->title }}
                            </span>
                        </td>

                        <td class="border p-2 align-middle">
                            <span class="truncate block text-gray-700">{{ strip_tags($ticket->description) }}</span>
                        </td>

                        <td class="border p-2 align-middle">
                            <span class="status-badge" data-status="{{ $status }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $label }}
                            </span>
                        </td>

                        <td class="border p-2 align-middle whitespace-nowrap text-gray-700">
                            {{ $ticket->updated_at?->diffForHumans() }}
                        </td>

                        <td class="border p-2 align-middle">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('tickets.show', ['locale' => app()->getLocale(), 'ticket' => $ticket->id, 'from' => request()->fullUrl()]) }}"
                                   class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold
                                          border border-blue-300 text-blue-700 hover:bg-blue-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View
                                </a>

                                <div class="relative">
                                    @php
                                        $currentUser = auth()->user();
                                        $statusActions = collect([
                                            \App\Support\WorkflowStatus::IN_PROGRESS,
                                            \App\Support\WorkflowStatus::CONFIRMATION,
                                            \App\Support\WorkflowStatus::REVISION,
                                            \App\Support\WorkflowStatus::DONE,
                                            \App\Support\WorkflowStatus::NEW,
                                        ])->filter(fn($st) => $currentUser && $ticket->canUserSetStatus($currentUser, $st))
                                          ->mapWithKeys(fn($st) => [$st => \App\Support\WorkflowStatus::label($st)]);
                                    @endphp

                                    @if ($statusActions->isNotEmpty())
                                        <select onchange="if(this.value){ window.location.href=this.value; }"
                                                class="w-44 appearance-none rounded-full border border-gray-300 bg-white px-3 py-1 text-xs
                                                       text-gray-900 pr-7">
                                            <option disabled selected>Change status</option>
                                            @foreach($statusActions as $value => $labelAction)
                                                <option value="{{ route('tickets.status.change', ['locale' => app()->getLocale(), 'ticket' => $ticket->id, 'status' => $value, 'from' => request()->fullUrl()]) }}">
                                                    {{ $labelAction }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <svg class="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                                             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-gray-500">
                            No tickets are currently in progress.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
</div>
@endsection







