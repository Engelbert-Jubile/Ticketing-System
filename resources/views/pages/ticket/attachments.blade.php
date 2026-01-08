@extends('layouts.app')
@section('title','Kelola Lampiran Ticket')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-6">
  @include('components.back', ['to' => request('from', route('tickets.index'))])
  <h1 class="text-2xl font-bold mb-4">Kelola Lampiran</h1>

  @if (session('success'))
    <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-2 text-green-700">
      {{ session('success') }}
    </div>
  @endif

  <form action="{{ route('tickets.attachments.update', $ticket) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf @method('PUT')

    @include('partials.attachments', [
      'initialAttachments' => $ticket->attachments ?? collect(),
      'toggleDefault' => true,
      'inputId' => 'ticket-reattach'
    ])

    <div class="flex items-center gap-2">
      <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-white shadow hover:bg-green-700">Simpan Lampiran</button>
      <a href="{{ route('tickets.edit', $ticket) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Kembali ke Edit</a>
    </div>
  </form>
</div>
@endsection

