{{-- resources/views/pages/ticket/report.blade.php --}}
@extends('layouts.app')

@section('title', 'Ticket Report')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 transition-colors duration-300">

  {{-- Back ke Dashboard --}}
  @include('components.back', ['to' => route('dashboard')])

  <h1 class="mb-4 text-2xl font-bold transition-colors duration-300">Ticket Report</h1>

  @if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-green-700
                dark:bg-green-900/30 dark:border-green-700 dark:text-green-200 transition-colors duration-300">
      {{ session('success') }}
    </div>
  @endif

  {{-- Filter Bar --}}
  <form method="GET" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-5 transition-colors duration-300">
    <div class="md:col-span-2">
      <input type="text" name="q" value="{{ $q ?? '' }}"
             class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900 placeholder-gray-400
                    dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-400 transition-colors duration-300"
             placeholder="Search title/description...">
    </div>
    <div>
      <select name="status"
              class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900
                     dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-300">
        <option value="">All Status</option>
        @foreach ($statuses as $s)
          @php $label = \App\Support\WorkflowStatus::label($s); @endphp
          <option value="{{ $s }}" {{ (isset($status) && $status === $s) ? 'selected' : '' }}>
            {{ $label }}
          </option>
        @endforeach
      </select>
    </div>
    <div>
      <input type="text" name="from" value="{{ $fromRaw ?? ($from ?? '') }}"
             class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900 placeholder-gray-400
                    dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-400 flatpickr-field transition-colors duration-300"
             placeholder="dd/mm/yyyy" autocomplete="off">
    </div>
    <div class="flex gap-2">
      <input type="text" name="to" value="{{ $toRaw ?? ($to ?? '') }}"
             class="w-full rounded-lg border px-3 py-2 border-gray-300 bg-white text-gray-900 placeholder-gray-400
                    dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-400 flatpickr-field transition-colors duration-300"
             placeholder="dd/mm/yyyy" autocomplete="off">
      <button class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 transition-colors duration-300">Filter</button>
    </div>
  </form>

  {{-- Table --}}
  <div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm dark:border-slate-700 dark:bg-gray-800">
    <table class="report-table min-w-full text-sm">
      <thead>
        <tr class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-200">
          <th class="px-4 py-3 text-left w-16">#</th>
          <th class="px-4 py-3 text-left">Title</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-left">Created</th>
          <th class="px-4 py-3 text-left">Updated</th>
          <th class="px-4 py-3 text-left">Attachments</th>
          <th class="px-4 py-3 text-left">Actions</th>
        </tr>
      </thead>

      <tbody>
        @forelse ($tickets as $ticket)
          @php
            $statusValue = $ticket->status instanceof \BackedEnum ? $ticket->status->value : $ticket->status;
            $statusValue = \App\Support\WorkflowStatus::normalize($statusValue);
            $label = \App\Support\WorkflowStatus::label($statusValue);

            $tz      = config('app.timezone');
            $created = $ticket->created_at?->timezone($tz)?->translatedFormat('d M Y H:i') ?? '—';
            $updated = $ticket->updated_at?->timezone($tz)?->translatedFormat('d M Y H:i') ?? '—';

            $ticketNo  = $ticket->ticket_no ?? $ticket->number ?? $ticket->code ?? '—';
            $priority  = $ticket->priority ?? '—';

            // === Assignees (multi) ===
            $assigneesArray = ($ticket->assignedUsers ?? collect())
                ->map(fn($u) => $u->name ?? $u->label ?? $u->email ?? ('User #'.$u->id))
                ->filter()->values()->all();
            $assignees = !empty($assigneesArray)
                ? implode(', ', $assigneesArray)
                : (optional($ticket->assignee)->name
                    ?? optional($ticket->assignedUser)->name
                    ?? ($ticket->assigned_to ?? '—'));

            $requester = optional($ticket->requester)->name
                      ?? optional($ticket->user)->name
                      ?? ($ticket->requester_name ?? '—');

            $dueRaw = $ticket->due_at ?? $ticket->due_date ?? null;
            $dueCarbon = null;
            if ($dueRaw instanceof \Carbon\CarbonInterface) {
              $dueCarbon = $dueRaw;
            } elseif (!empty($dueRaw)) {
              try {
                $dueCarbon = \Illuminate\Support\Carbon::parse($dueRaw);
              } catch (\Throwable $e) {
                $dueCarbon = null;
              }
            }
            $due = $dueCarbon
              ? $dueCarbon->timezone($tz)->format('d/m/Y')
              : ($dueRaw ?: '—');

            $projectTitle = optional($ticket->project)->title ?? '—';

            $descPlain = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($ticket->description ?? ''))));
            $descShort = $descPlain !== '' ? \Illuminate\Support\Str::limit($descPlain, 120) : '—';

            $rowKey = 'ticket-'.($ticket->id ?? $loop->index);
          @endphp

          {{-- Row utama --}}
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <td class="cell-index px-4 py-3">
              {{ $loop->iteration + ($tickets->currentPage()-1)*$tickets->perPage() }}
            </td>
            <td class="px-4 py-3">
              <div class="font-medium text-slate-900 dark:text-slate-100">{{ $ticket->title }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">
                @if($ticketNo !== '—') No: {{ $ticketNo }} @endif
                @if($projectTitle !== '—') • Project: {{ $projectTitle }} @endif
              </div>
              @if($descPlain)
                <div class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ $descShort }}</div>
              @endif
            </td>
            <td class="px-4 py-3">
              <span class="status-badge" data-status="{{ $statusValue }}">{{ $label }}</span>
            </td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $created }}</td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $updated }}</td>
            <td class="px-4 py-3">
              @php $atts = $ticket->attachments ?? collect(); @endphp
              @if($atts->isNotEmpty())
                <div class="flex flex-wrap gap-1.5">
                  @foreach($atts->take(2) as $att)
                    <a href="{{ route('attachments.view', $att) }}" target="_blank"
                       class="inline-flex items-center rounded border border-slate-200 px-2 py-0.5 text-xs text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800/60"
                       title="{{ $att->original_name }}">
                      View
                    </a>
                    <a href="{{ route('attachments.download', $att) }}"
                       class="inline-flex items-center rounded border border-slate-200 px-2 py-0.5 text-xs text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800/60">
                      Unduh
                    </a>
                  @endforeach
                  @php $more = $atts->count() - 2; @endphp
                  @if($more > 0)
                    <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-700 dark:text-slate-300">+{{ $more }} lagi</span>
                  @endif
                </div>
              @else
                <span class="text-slate-400 text-xs">—</span>
              @endif
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tickets.show', $ticket) }}"
                   class="rounded-lg border border-slate-200 px-3 py-1.5 text-slate-700 hover:bg-slate-50
                          dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800/60">
                  Detail
                </a>
                <a href="{{ route('tickets.edit', ['ticket' => $ticket->id, 'from' => request()->fullUrl()]) }}"
                   class="rounded-lg border border-blue-200 px-3 py-1.5 text-blue-700 hover:bg-blue-50
                          dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-900/30 transition-colors duration-300">
                  Edit
                </a>
                <form action="{{ route('tickets.destroy', $ticket) }}" method="POST" onsubmit="return confirm('Delete this ticket?')">
                  @csrf @method('DELETE')
                  <input type="hidden" name="from" value="{{ request()->fullUrl() }}">
                  <button type="submit"
                          class="rounded-lg border border-red-200 px-3 py-1.5 text-red-700 hover:bg-red-50
                                 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/30 transition-colors duration-300">
                    Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>

          {{-- Row meta --}}
          <tr class="meta-row bg-slate-50/60 dark:bg-gray-900/20">
            <td colspan="7" class="px-4 pb-4">
              <div class="rounded-lg border border-slate-200 p-3 text-xs text-slate-700 shadow-sm dark:border-slate-700 dark:text-slate-200">
                <div class="grid gap-3 md:grid-cols-3">
                  <div class="md:col-span-2">
                    <div class="mb-1 font-semibold">Description</div>
                    <div class="prose prose-sm max-w-none dark:prose-invert">
                      {!! $ticket->description ?: '<span class="text-slate-500">—</span>' !!}
                    </div>

                    @if($ticket->attachments && $ticket->attachments->isNotEmpty())
                    <div class="mt-3">
                      <div class="mb-1 font-semibold">Lampiran</div>
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
                  <div class="space-y-1">
                    <div><span class="opacity-60">Priority</span>: {{ $priority }}</div>
                    <div><span class="opacity-60">Assignees</span>: {{ $assignees }}</div>
                    <div><span class="opacity-60">Requester</span>: {{ $requester }}</div>
                    <div><span class="opacity-60">Due</span>: {{ $due }}</div>
                  </div>
                </div>
              </div>
            </td>
          </tr>

          {{-- Payload untuk modal --}}
          <script type="application/json" id="ticket-data-{{ $rowKey }}">
            {!! json_encode([
              'title'      => (string) $ticket->title,
              'status'     => (string) $label,
              'ticket_no'  => (string) $ticketNo,
              'project'    => (string) $projectTitle,
              'priority'   => (string) $priority,
              'assignees'  => (string) $assignees,
              'requester'  => (string) $requester,
              'due'        => (string) $due,
              'created'    => (string) $created,
              'updated'    => (string) $updated,
              'description'=> strip_tags((string) $ticket->description),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
          </script>
        @empty
          <tr>
            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
              Tidak ada ticket untuk laporan.
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

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
  body, .transition-colors { transition: background-color .3s ease, color .3s ease, border-color .3s ease; }

  .report-table{ border-collapse: separate; border-spacing:0; width:100%; }
  .report-table thead th{ background:#f8fafc; border-bottom:2px solid #cbd5e1; color:#334155; }
  .report-table th, .report-table td{ border-right:1px solid #e5e7eb; }
  .report-table th:last-child, .report-table td:last-child{ border-right:0; }
  .report-table tbody tr{ border-bottom:1px solid #e5e7eb; }
  .report-table tbody tr:nth-child(even){ background:#fafafa; }

  .cell-index{ text-align:center; font-weight:700; letter-spacing:.2px; background:#f1f5f9; }
  .report-table tbody tr:nth-child(6n+1) .cell-index{ background:#e0f2fe; color:#075985; }
  .report-table tbody tr:nth-child(6n+2) .cell-index{ background:#dcfce7; color:#065f46; }
  .report-table tbody tr:nth-child(6n+3) .cell-index{ background:#fef9c3; color:#854d0e; }
  .report-table tbody tr:nth-child(6n+4) .cell-index{ background:#fae8ff; color:#6b21a8; }
  .report-table tbody tr:nth-child(6n+5) .cell-index{ background:#fee2e2; color:#991b1b; }
  .report-table tbody tr:nth-child(6n+6) .cell-index{ background:#ede9fe; color:#4c1d95; }

  .report-table .meta-row td{ border-right:0 !important; }

  .dark .report-table thead th{ background:#0f172a; border-color:#334155; color:#e2e8f0; }
  .dark .report-table th, .dark .report-table td, .dark .report-table tbody tr{ border-color:#334155; }
  .dark .report-table tbody tr:nth-child(even){ background:#0b1220; }
  .dark .cell-index{ background:#0b1220; }

  .dark .report-table tbody tr:nth-child(6n+1) .cell-index{ background:#082f49; color:#bae6fd; }
  .dark .report-table tbody tr:nth-child(6n+2) .cell-index{ background:#052e16; color:#bbf7d0; }
  .dark .report-table tbody tr:nth-child(6n+3) .cell-index{ background:#422006; color:#fef08a; }
  .dark .report-table tbody tr:nth-child(6n+4) .cell-index{ background:#3b0764; color:#f5d0fe; }
  .dark .report-table tbody tr:nth-child(6n+5) .cell-index{ background:#450a0a; color:#fecaca; }
  .dark .report-table tbody tr:nth-child(6n+6) .cell-index{ background:#2e1065; color:#ddd6fe; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  flatpickr(".flatpickr-field", { dateFormat: "d/m/Y", allowInput: true });

  const modal    = document.getElementById('ticketModal');
  const backdrop = document.getElementById('ticketModalBackdrop');
  const card     = document.getElementById('ticketModalCard');
  const titleEl  = document.getElementById('ticketModalTitle');
  const subEl    = document.getElementById('ticketModalSub');
  const bodyEl   = document.getElementById('ticketModalBody');
  const closeBtn = document.getElementById('ticketModalClose');

  function openModal(data){
    titleEl.textContent = data.title || 'Ticket';
    subEl.textContent   = data.status ? `Status: ${data.status}` : '';

    const items = [
      ['Ticket No', data.ticket_no],
      ['Project',   data.project],
      ['Priority',  data.priority],
      ['Assignees', data.assignees],
      ['Requester', data.requester],
      ['Due',       data.due],
      ['Created',   data.created],
      ['Updated',   data.updated],
    ].filter(([k,v]) => v && v !== '—');

    let html = '';
    html += `<div class="grid gap-3 md:grid-cols-3">`;
    html += `<div class="md:col-span-2">
               <div class="mb-1 font-semibold">Description</div>
               <div class="rounded-lg border border-gray-200 p-3 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-200 whitespace-pre-wrap">${
                 (data.description || '—')
               }</div>
             </div>`;
    html += `<div class="space-y-1 text-sm text-gray-700 dark:text-gray-200">`;
    for (const [k,v] of items){ html += `<div><span class="opacity-60">${k}</span>: ${v}</div>`; }
    html += `</div></div>`;
    bodyEl.innerHTML = html;

  // modal removed; no-op
  }

  // closeModal removed

  // handlers removed

  // handlers removed
});
</script>
@endpush
