{{-- LEGACY (fallback) resources/views/pages/project/index.blade.php --}}
@extends('layouts.app')
@section('title','Project Reports')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 transition-colors duration-300">

  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-start gap-3">
      <div class="shrink-0">
        @include('components.back', ['to' => route('dashboard')])
      </div>
      <div>
        <h1 class="text-2xl font-bold text-slate-900 transition-colors duration-300 dark:text-slate-100">Project Reports</h1>
        <p class="text-sm text-slate-500 transition-colors duration-300 dark:text-slate-300">Kelola project, pantau progres, dan tindak lanjuti dengan cepat.</p>
      </div>
    </div>
    <a href="{{ route('projects.create', ['from' => request()->fullUrl()]) }}"
      class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 via-indigo-500 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-purple-500/30 transition duration-300 hover:shadow-xl">
      <span class="material-icons text-[18px]">folder_open</span>
      Project Baru
    </a>
  </div>

  @if(session('success'))
    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700 transition-colors duration-300 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
      {{ session('success') }}
    </div>
  @endif

  @php
    // Get items from paginator
    $items = method_exists($projects, 'items') ? collect($projects->items()) : collect($projects);
    [$withTicket, $standalone] = $items->partition(fn($p) => !empty($p->ticket_id));
  @endphp

  {{-- SECTION 1: PROJECT DARI TICKET --}}
  <div class="mt-6">
    <div class="mb-4 rounded-lg border-2 border-blue-200 bg-blue-50 px-4 py-4 dark:border-blue-800 dark:bg-blue-900/30 transition-colors duration-300 cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-900/50"
         data-toggle-section="section-with-ticket" role="button" tabindex="0">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 flex-1">
          <span class="material-icons text-2xl text-blue-600 dark:text-blue-400">confirmation_number</span>
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <h2 class="text-lg font-bold text-blue-900 dark:text-blue-100">Project Berbasis Ticket</h2>
              <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">{{ $withTicket->count() }} items</span>
            </div>
          </div>
        </div>
        <div class="flex-shrink-0">
          <svg class="section-toggle-icon w-6 h-6 text-blue-900 dark:text-blue-100 transition-transform duration-300" 
               fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
          </svg>
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm transition-colors duration-300 dark:border-slate-700 dark:bg-gray-800 section-content hidden" id="section-with-ticket" data-section-id="section-with-ticket">
      <table class="report-table min-w-full text-sm">
        <thead>
          <tr class="text-xs font-semibold uppercase tracking-wider text-slate-600 transition-colors duration-300 dark:text-slate-200">
            <th class="px-4 py-3 text-left w-12">#</th>
            <th class="px-4 py-3 text-left">Project No</th>
            <th class="px-4 py-3 text-left">Title</th>
            <th class="px-4 py-3 text-left">Ticket</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Status ID</th>
            <th class="px-4 py-3 text-left">Due</th>
            <th class="px-4 py-3 text-left">Updated</th>
            <th class="px-4 py-3 text-left">Attachments</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>

      <tbody>
        @forelse($withTicket as $project)
          @php
            $statusValue = $project->status instanceof \BackedEnum ? $project->status->value : ($project->status ?? '');
            $statusValue = \App\Support\WorkflowStatus::normalize($statusValue);
            $statusLabel = \App\Support\WorkflowStatus::label($statusValue);

            $tz = config('app.timezone');
            $formatDate = function ($value) use ($tz) {
              if ($value instanceof \Carbon\CarbonInterface) {
                return $value->copy()->timezone($tz)->translatedFormat('d M Y H:i');
              }
              if (!empty($value)) {
                try {
                  return \Illuminate\Support\Carbon::parse($value, $tz)->translatedFormat('d M Y H:i');
                } catch (\Throwable $e) {
                  return (string) $value;
                }
              }
              return '—';
            };

            $updated = $formatDate($project->updated_at);

            $ticket = $project->relationLoaded('ticket') ? $project->ticket : $project->ticket()->with('attachments')->first();
            $ticketNo = $ticket?->ticket_no ?? '—';
            $ticketTitle = $ticket?->title ?? null;

            $statusId = \App\Support\WorkflowStatus::code($statusValue);

            $due = $formatDate($project->end_date ?: $ticket?->due_at ?: $ticket?->due_date);

            $descPlain = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($project->description ?? ''))));
            $descShort = $descPlain !== '' ? \Illuminate\Support\Str::limit($descPlain, 96) : '—';

            $projAtts = ($project->attachments instanceof \Illuminate\Support\Collection)
              ? $project->attachments
              : collect($project->attachments ?? []);
            $ticketAtts = ($ticket?->attachments instanceof \Illuminate\Support\Collection)
              ? $ticket->attachments
              : collect($ticket?->attachments ?? []);
            $attachments = $projAtts->merge($ticketAtts);
          @endphp

          <tr class="hover:bg-slate-50 transition-colors duration-300 dark:hover:bg-slate-800/60">
            <td class="px-4 py-3 text-center font-semibold text-blue-600 dark:text-blue-300">{{ $loop->iteration }}</td>
            <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-200">{{ $project->project_no ?? '—' }}</td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $project->title }}</div>
              @if($descPlain)
                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $descShort }}</div>
              @endif
            </td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">
              @if($ticketNo !== '—')
                <div>{{ $ticketNo }}</div>
                @if($ticketTitle)
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($ticketTitle, 40) }}</div>
                @endif
              @else
                <span>—</span>
              @endif
            </td>
            <td class="px-4 py-3">
              <span class="status-badge" data-status="{{ $statusValue }}">{{ $statusLabel }}</span>
            </td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $statusId ?? '—' }}</td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $due }}</td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $updated }}</td>
            <td class="px-4 py-3">
              @if($attachments->isNotEmpty())
                <ul class="space-y-1 text-xs text-slate-600 dark:text-slate-300">
                  @foreach($attachments->take(2) as $att)
                    <li class="flex items-center gap-1">
                      <span class="text-slate-400">•</span>
                      <a href="{{ route('attachments.view', $att) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a>
                      <span class="text-slate-400">·</span>
                      <a href="{{ route('attachments.download', $att) }}" class="text-blue-600 hover:underline">Unduh</a>
                      <span class="text-slate-500">{{ \Illuminate\Support\Str::limit($att->original_name ?? '', 24) }}</span>
                    </li>
                  @endforeach
                  @if($attachments->count() > 2)
                    <li class="text-slate-500">+{{ $attachments->count() - 2 }} lampiran lainnya</li>
                  @endif
                </ul>
              @else
                <span class="text-xs text-slate-400">—</span>
              @endif
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('projects.show', ['project' => $project->public_slug ?? $project->id, 'from' => request()->fullUrl()]) }}" class="text-blue-600 hover:underline">View</a>
                <a href="{{ route('projects.edit', ['project' => $project->public_slug ?? $project->id, 'from' => request()->fullUrl()]) }}" class="text-indigo-600 hover:underline">Edit</a>
                <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Hapus project ini?')" class="inline">
                  @csrf
                  @method('DELETE')
                  <input type="hidden" name="from" value="{{ request()->fullUrl() }}">
                  <button type="submit" class="text-red-600 hover:underline">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="px-4 py-10 text-center text-slate-500 transition-colors duration-300 dark:text-slate-300">Belum ada project dari ticket.</td>
          </tr>
        @endforelse
      </tbody>
        </table>
    </div>
  </div>

  {{-- SECTION 2: PROJECT MANDIRI --}}
  @if($standalone->isNotEmpty())
  <div class="mt-6">
    <div class="mb-4 rounded-lg border-2 border-emerald-200 bg-emerald-50 px-4 py-4 dark:border-emerald-800 dark:bg-emerald-900/30 transition-colors duration-300 cursor-pointer hover:bg-emerald-100 dark:hover:bg-emerald-900/50"
         data-toggle-section="section-standalone" role="button" tabindex="0">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 flex-1">
          <span class="material-icons text-2xl text-emerald-600 dark:text-emerald-400">folder_special</span>
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <h2 class="text-lg font-bold text-emerald-900 dark:text-emerald-100">Project Mandiri</h2>
              <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $standalone->count() }} items</span>
            </div>
          </div>
        </div>
        <div class="flex-shrink-0">
          <svg class="section-toggle-icon w-6 h-6 text-emerald-900 dark:text-emerald-100 transition-transform duration-300" 
               fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
          </svg>
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm transition-colors duration-300 dark:border-slate-700 dark:bg-gray-800 section-content hidden" id="section-standalone" data-section-id="section-standalone">
      <table class="report-table min-w-full text-sm">
        <thead>
          <tr class="text-xs font-semibold uppercase tracking-wider text-slate-600 transition-colors duration-300 dark:text-slate-200">
            <th class="px-4 py-3 text-left w-12">#</th>
            <th class="px-4 py-3 text-left">Project No</th>
            <th class="px-4 py-3 text-left">Title</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Status ID</th>
            <th class="px-4 py-3 text-left">Start</th>
            <th class="px-4 py-3 text-left">End</th>
            <th class="px-4 py-3 text-left">Updated</th>
            <th class="px-4 py-3 text-left">Attachments</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($standalone as $project)
            @php
              $statusValue = $project->status instanceof \BackedEnum ? $project->status->value : ($project->status ?? '');
              $statusValue = \App\Support\WorkflowStatus::normalize($statusValue);
              $statusLabel = \App\Support\WorkflowStatus::label($statusValue);

              $tz = config('app.timezone');
              $formatDate = function ($value, string $format = 'd/m/Y') use ($tz) {
                if ($value instanceof \Carbon\CarbonInterface) {
                  return $value->copy()->timezone($tz)->translatedFormat($format);
                }
                if (!empty($value)) {
                  try {
                    return \Illuminate\Support\Carbon::parse($value, $tz)->translatedFormat($format);
                  } catch (\Throwable $e) {
                    return (string) $value;
                  }
                }
                return '—';
              };

              $updated = $formatDate($project->updated_at, 'd M Y H:i');

              $planStart = null;
              $planEnd = null;
              if (!empty($project->planning) && is_array($project->planning)) {
                $planStart = $project->planning['start'] ?? null;
                $planEnd = $project->planning['end'] ?? null;
              }

              $actionStart = null;
              $actionEnd = null;
              if ($project->relationLoaded('actions')) {
                $actionStart = $project->actions
                  ->filter(fn ($action) => !empty($action->start_date))
                  ->min('start_date');
                $actionEnd = $project->actions
                  ->filter(fn ($action) => !empty($action->end_date))
                  ->max('end_date');
              }

              $start = $formatDate($project->start_date ?: $planStart ?: $actionStart);
              $end = $formatDate($project->end_date ?: $planEnd ?: $actionEnd);

              $statusId = \App\Support\WorkflowStatus::code($statusValue);

              $projAtts = ($project->attachments instanceof \Illuminate\Support\Collection)
                ? $project->attachments
                : collect($project->attachments ?? []);
            @endphp
            <tr class="hover:bg-slate-50 transition-colors duration-300 dark:hover:bg-slate-800/60">
              <td class="px-4 py-3 text-center font-semibold text-emerald-600 dark:text-emerald-400">{{ $loop->iteration }}</td>
              <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-200">{{ $project->project_no ?? '—' }}</td>
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $project->title }}</div>
                @php $descPlain = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($project->description ?? '')))); @endphp
                @if($descPlain)
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($descPlain, 90) }}</div>
                @endif
              </td>
              <td class="px-4 py-3">
                <span class="status-badge" data-status="{{ $statusValue }}">{{ $statusLabel }}</span>
              </td>
              <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $statusId ?? '—' }}</td>
              <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $start }}</td>
              <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $end }}</td>
              <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $updated }}</td>
              <td class="px-4 py-3">
                @if($projAtts->isNotEmpty())
                  <ul class="space-y-1 text-xs text-slate-600 dark:text-slate-300">
                    @foreach($projAtts->take(2) as $att)
                      <li class="flex items-center gap-1">
                        <span class="text-slate-400">•</span>
                        <a href="{{ route('attachments.view', $att) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a>
                        <span class="text-slate-400">·</span>
                        <a href="{{ route('attachments.download', $att) }}" class="text-blue-600 hover:underline">Unduh</a>
                        <span class="text-slate-500">{{ \Illuminate\Support\Str::limit($att->original_name ?? '', 24) }}</span>
                      </li>
                    @endforeach
                    @if($projAtts->count() > 2)
                      <li class="text-slate-500">+{{ $projAtts->count() - 2 }} lampiran lainnya</li>
                    @endif
                  </ul>
                @else
                  <span class="text-xs text-slate-400">—</span>
                @endif
              </td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                  <a href="{{ route('projects.show', ['project' => $project->public_slug ?? $project->id, 'from' => request()->fullUrl()]) }}" class="text-blue-600 hover:underline">View</a>
                  <a href="{{ route('projects.edit', ['project' => $project->public_slug ?? $project->id, 'from' => request()->fullUrl()]) }}" class="text-indigo-600 hover:underline">Edit</a>
                  <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Hapus project ini?')" class="inline">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="from" value="{{ request()->fullUrl() }}">
                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

</div>
@endsection

@push('styles')
<style>
  .report-table{border-collapse:separate;border-spacing:0;width:100%;}
  .report-table thead th{background:#f8fafc;border-bottom:2px solid #cbd5e1;color:#334155;}
  .report-table th,.report-table td{border-right:1px solid #e5e7eb;}
  .report-table th:last-child,.report-table td:last-child{border-right:0;}
  .report-table tbody tr{border-bottom:1px solid #e5e7eb;}
  .report-table tbody tr:nth-child(even){background:#fafafa;}
  .cell-index{background:#f1f5f9;text-align:center;font-weight:700;letter-spacing:.2px;}
  .report-table .meta-row td{border-right:0!important;}
  .dark .report-table thead th{background:#0f172a;border-color:#334155;color:#e2e8f0;}
  .dark .report-table th,.dark .report-table td,.dark .report-table tbody tr{border-color:#334155;}
  .dark .report-table tbody tr:nth-child(even){background:#0b1220;}
  .dark .cell-index{background:#0b1220;}
  .section-content.hidden {display: none;}
</style>
@endpush

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-section]').forEach(header => {
      header.addEventListener('click', function () {
        const sectionId = this.dataset.toggleSection;
        const content = document.getElementById(sectionId);
        const icon = this.querySelector('.section-toggle-icon');
        
        if (content) {
          content.classList.toggle('hidden');
          if (icon) {
            icon.style.transform = content.classList.contains('hidden') ? 'rotate(180deg)' : 'rotate(0deg)';
          }
        }
      });

      header.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.click();
        }
      });
    });
  });
</script>
@endpush

