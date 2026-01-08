{{-- LEGACY: migrated to Inertia resources/js/Pages/Tickets/Show.vue --}}
@extends('layouts.app')
@section('title','Detail Ticket')

@section('content')
<div class="container mx-auto px-4 py-6">
  @include('components.back', ['to' => request('from', route('tickets.index'))])

  <h2 class="text-2xl font-bold mb-4">{{ $ticket->title }}</h2>

  <div class="grid md:grid-cols-2 gap-4 mb-6">
    <div class="space-y-2">
      <div><span class="font-semibold">Ticket No:</span> {{ $ticket->ticket_no ?? '-' }}</div>
      <div><span class="font-semibold">Priority:</span> {{ ucfirst($ticket->priority) }}</div>
      <div><span class="font-semibold">Type:</span> {{ ucfirst($ticket->type) }}</div>
      <div><span class="font-semibold">Status:</span>
        <span class="status-badge" data-status="{{ $ticket->status }}">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span>
      </div>
      <div><span class="font-semibold">Due Date:</span> {{ optional($ticket->due_date)->format('Y-m-d') }}</div>
      <div><span class="font-semibold">Finish Date:</span> {{ optional($ticket->finish_date)->format('Y-m-d') ?: '-' }}</div>
      <div><span class="font-semibold">SLA:</span> {{ $ticket->sla ? ucfirst($ticket->sla) : '-' }}</div>
    </div>
    <div class="space-y-2">
      <div><span class="font-semibold">Requester:</span> {{ optional($ticket->requester)->name ?? '-' }}</div>
      <div><span class="font-semibold">Agent:</span> {{ optional($ticket->agent)->name ?? '-' }}</div>
      <div><span class="font-semibold">Assigned ID:</span> {{ $ticket->assigned_id ?? '-' }}</div>
      <div><span class="font-semibold">Letter No:</span> {{ $ticket->letter_no ?? '-' }}</div>
      <div><span class="font-semibold">Reason:</span> {{ $ticket->reason ?? '-' }}</div>
    </div>
  </div>

  <div class="card p-4">
    <div class="font-semibold mb-2">Deskripsi</div>
    <div class="prose max-w-none dark:prose-invert">{!! $ticket->description !!}</div>
  </div>

  @if($ticket->attachments && $ticket->attachments->isNotEmpty())
  <div class="card p-4 mt-4">
    <div class="font-semibold mb-2">Lampiran</div>
    <ul class="list-disc list-inside space-y-1">
      @foreach($ticket->attachments as $att)
      <li>
        <a href="{{ route('attachments.view', $att) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a>
        <span class="text-slate-400">·</span>
        <a href="{{ route('attachments.download', $att) }}" class="text-blue-600 hover:underline">Unduh</a>
        <span class="text-slate-600 ml-2">
          {{ $att->original_name }} ({{ number_format(($att->size ?? 0)/1024, 0) }} KB)
        </span>
      </li>
      @endforeach
    </ul>
  </div>
  @endif
</div>
@endsection
