@php 
  $map = $statusLabelMap ?? [];
@endphp

<div class="overflow-x-auto">
  <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
    <thead class="bg-gray-100 dark:bg-gray-700">
      <tr>
        <th class="px-3 py-2 text-left">Ticket No</th>
        <th class="px-3 py-2 text-left">Title</th>
        <th class="px-3 py-2 text-left">Priority</th>
        <th class="px-3 py-2 text-left">Type</th>
        <th class="px-3 py-2 text-left">Status</th>
        <th class="px-3 py-2 text-left">Status ID</th>
        <th class="px-3 py-2 text-left">Due</th>
        <th class="px-3 py-2 text-left">Updated</th>
        <th class="px-3 py-2 text-left">Attachments</th>
        <th class="px-3 py-2 text-left">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($tickets as $t)
        @php
          $normalizedStatus = \App\Support\WorkflowStatus::normalize($t->status ?? 'new');
          $statusLabel      = \App\Support\WorkflowStatus::label($normalizedStatus);

          $tz = config('app.timezone');

          $dueSource = $t->due_at ?? $t->due_date ?? null;
          $dueDisplay = '—';
          if ($dueSource instanceof \Carbon\CarbonInterface) {
            $dueDisplay = $dueSource->timezone($tz)->format('d/m/Y');
          } elseif (!empty($dueSource)) {
            try {
              $dueDisplay = \Illuminate\Support\Carbon::parse($dueSource)
                ->timezone($tz)
                ->format('d/m/Y');
            } catch (\Throwable $e) {
              $dueDisplay = $dueSource;
            }
          }

          $updatedSource  = $t->updated_at ?? null;
          $updatedDisplay = '—';
          if ($updatedSource instanceof \Carbon\CarbonInterface) {
            $updatedDisplay = $updatedSource->timezone($tz)->format('d/m/Y H:i');
          } elseif (!empty($updatedSource)) {
            try {
              $updatedDisplay = \Illuminate\Support\Carbon::parse($updatedSource)
                ->timezone($tz)
                ->format('d/m/Y H:i');
            } catch (\Throwable $e) {
              $updatedDisplay = $updatedSource;
            }
          }
        @endphp
        <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
          <td class="px-3 py-2 font-mono text-sm">{{ $t->ticket_no }}</td>
          <td class="px-3 py-2">{{ $t->title }}</td>
          <td class="px-3 py-2 capitalize">
            <span class="status-badge" data-status="{{ $t->priority }}">{{ $t->priority }}</span>
          </td>
          <td class="px-3 py-2 capitalize">{{ $t->type }}</td>
          <td class="px-3 py-2">
            <span class="status-badge" data-status="{{ $normalizedStatus }}">{{ $statusLabel }}</span>
          </td>
          <td class="px-3 py-2">
            {{ $t->status_id }}
            @if(isset($map[$t->status_id]))
              <span class="text-xs text-gray-500">— {{ $map[$t->status_id] }}</span>
            @endif
          </td>
          <td class="px-3 py-2">
            {{ $dueDisplay }}
          </td>
          <td class="px-3 py-2">
            {{ $updatedDisplay }}
          </td>
          <td class="px-3 py-2">
            @php $atts = ($t->attachments ?? collect()); @endphp
            @if(method_exists($atts, 'isNotEmpty') ? $atts->isNotEmpty() : count($atts) > 0)
              <ul class="list-disc list-inside text-xs">
                @foreach($atts as $att)
                  <li>
                    <a href="{{ route('attachments.view', $att) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a>
                    <span class="text-slate-400">·</span>
                    <a href="{{ route('attachments.download', $att) }}" class="text-blue-600 hover:underline">Unduh</a>
                    <span class="text-slate-600 ml-1">{{ \Illuminate\Support\Str::limit($att->original_name, 24) }}</span>
                  </li>
                @endforeach
              </ul>
            @else
              <span class="text-gray-400">—</span>
            @endif
          </td>
          <td class="px-3 py-2 space-x-2">
            <a href="{{ route('tickets.show', ['ticket' => $t->id, 'from' => request()->fullUrl()]) }}" class="text-blue-600 hover:underline">View</a>
            <a href="{{ route('tickets.edit', ['ticket' => $t->id, 'from' => request()->fullUrl()]) }}" class="text-blue-600 hover:underline">Edit</a>
            <form action="{{ route('tickets.destroy', $t) }}" method="POST" class="inline" onsubmit="return confirm('Hapus ticket ini?')">
              @csrf @method('DELETE')
              <button class="text-red-600 hover:underline">Delete</button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="10" class="px-3 py-6 text-center text-gray-500">Tidak ada ticket.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

@php
  $isPaginator = $tickets instanceof \Illuminate\Pagination\AbstractPaginator
              || $tickets instanceof \Illuminate\Contracts\Pagination\Paginator;
@endphp
@if ($isPaginator && $tickets->hasPages())
  <div class="mt-3">
    {{ $tickets->withQueryString()->onEachSide(1)->links() }}
  </div>
@endif
