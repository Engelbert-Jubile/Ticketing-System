{{-- LEGACY (fallback) resources/views/pages/project/on-progress.blade.php --}}
@extends('layouts.app')
@section('title','Project In Progress')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Back --}}
    @include('components.back', ['to' => route('projects.report')])

    <h1 class="text-2xl font-bold mb-4">Project – In Progress</h1>

    <div class="mb-4">
        <a href="{{ route('projects.create') }}"
           class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
            + Buat Project Baru
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <tr>
                    <th class="w-16 px-4 py-2 text-left">#</th>
                    <th class="px-4 py-2 text-left">Title</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Planning</th>
                    <th class="px-4 py-2 text-left">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($projects as $p)
                    @php
                        // ----- Badge Status (mapping terpusat)
                        $statusKey   = \App\Support\WorkflowStatus::normalize($p->status);
                        $statusLabel = \App\Support\WorkflowStatus::label($statusKey);

                        $badgeClasses = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ' . \App\Support\WorkflowStatus::badgeClass($statusKey);
                        $icon = '';
                        switch ($statusKey) {
                            case 'new':
                                $badgeClasses .= ' bg-gray-100 text-gray-800 ring-gray-300';
                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.036a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.036a1 1 0 00-1.175 0L6.89 16.283c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L3.255 8.72c-.783-.57-.38-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z"/></svg>';
                                break;
                            case 'in_progress':
                                $badgeClasses .= ' bg-yellow-100 text-yellow-800 ring-yellow-300';
                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16Zm.75-12a.75.75 0 00-1.5 0v4c0 .199.079.39.22.53l2.5 2.5a.75.75 0 101.06-1.06l-2.28-2.28V6z" clip-rule="evenodd"/></svg>';
                                break;
                            case 'confirmation':
                                $badgeClasses .= ' bg-blue-100 text-blue-800 ring-blue-300';
                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>';
                                break;
                            case 'revision':
                                $badgeClasses .= ' bg-purple-100 text-purple-800 ring-purple-300';
                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg>';
                                break;
                            case 'done':
                                $badgeClasses .= ' bg-green-100 text-green-800 ring-green-300';
                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16Zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.879 7.707 9.586A1 1 0 006.293 11l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
                                break;
                            default:
                                $badgeClasses .= ' bg-gray-100 text-gray-800 ring-gray-300';
                                $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.036a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.036a1 1 0 00-1.175 0L6.89 16.283c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L3.255 8.72c-.783-.57-.38-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z"/></svg>';
                                break;
                        }
                        // ----- Planning data (bisa string JSON atau array)
                        $planItems = [];

                        if (isset($p->planning)) {
                            if (is_string($p->planning)) {
                                $decoded = json_decode($p->planning, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $planItems = $decoded;
                                }
                            } elseif (is_iterable($p->planning)) {
                                $planItems = collect($p->planning)->toArray();
                            }
                        }

                        $planCount = is_array($planItems) ? count($planItems) : 0;

                        // Ringkas 3 item pertama
                        $parts = [];

                        foreach (array_slice($planItems, 0, 3) as $row) {
                            $title = trim((string)($row['title'] ?? ($row['name'] ?? '')));
                            $week  = trim((string)($row['week'] ?? ($row['period'] ?? '')));

                            $label = $title !== '' ? $title : 'Kegiatan';
                            if ($week !== '') {
                                $label .= ' (' . $week . ')';
                            }

                            if ($label !== '') {
                                $parts[] = $label;
                            }
                        }

                        $planningSummary = $planCount
                            ? ((count($parts) ? implode(' � ', $parts) : 'Rundown')
                                . ($planCount > 3 ? ' (+' . ($planCount - 3) . ')' : ''))
                            : 'Belum ada planning';
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/20 align-top">
                        <td class="px-4 py-2">
                            @if(method_exists($projects, 'currentPage'))
                                {{ ($projects->currentPage()-1)*$projects->perPage() + $loop->iteration }}
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </td>

                        <td class="px-4 py-2 font-medium">{{ $p->title }}</td>

                        <td class="px-4 py-2">
                            <span class="{{ $badgeClasses }}">{!! $icon !!} {{ $statusLabel }}</span>
                        </td>

                        {{-- Planning: ringkas + tombol Detail --}}
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-200 relative">
                            <div class="flex items-center gap-2">
                                <span>{{ $planningSummary }}</span>
                                @if($planCount)
                                    <button type="button"
                                            class="px-2 py-0.5 text-xs rounded border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 plan-toggle"
                                            data-target="plan-pop-{{ $p->id }}">
                                        Detail
                                    </button>
                                @endif
                            </div>

                            {{-- Popover Detail Planning --}}
                            @if($planCount)
                                <div id="plan-pop-{{ $p->id }}"
                                     class="plan-popover hidden absolute z-20 mt-2 w-[38rem] max-w-[90vw] right-4">
                                    <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                                        {{-- Header ringkas proyek --}}
                                        <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="mr-3">Timeline:
                                                <strong>
                                                    {{ optional($p->start_date)->format('d/m/Y') ?? '-' }}
                                                    – {{ optional($p->end_date)->format('d/m/Y') ?? '-' }}
                                                </strong>
                                            </span>
                                            {{-- Tambahan info lain bila suatu saat disimpan (sponsor/owner/lead/budget) --}}
                                        </div>

                                        <div class="overflow-auto max-h-72">
                                            <table class="min-w-full text-xs">
                                                <thead class="bg-gray-50 text-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                                    <tr>
                                                        <th class="px-2 py-1 text-left w-[55%]">Kegiatan</th>
                                                        <th class="px-2 py-1 text-left w-[15%]">Minggu/Periode</th>
                                                        <th class="px-2 py-1 text-left w-[15%]">Status</th>
                                                        <th class="px-2 py-1 text-left w-[15%]">Catatan</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                    @foreach($planItems as $row)
                                                        <tr>
                                                            <td class="px-2 py-1">{{ e($row['title'] ?? ($row['name'] ?? '—')) }}</td>
                                                            <td class="px-2 py-1">{{ e($row['week']  ?? '—') }}</td>
                                                            <td class="px-2 py-1">
                                                                @php $st = trim((string)($row['status'] ?? ($row['state'] ?? ''))); @endphp
                                                                @if($st !== '')
                                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] ring-1
                                                                        {{ $st === 'Selesai' ? 'bg-green-50 text-green-700 ring-green-200' :
                                                                           ($st === 'On Going' ? 'bg-yellow-50 text-yellow-700 ring-yellow-200' :
                                                                            'bg-gray-50 text-gray-700 ring-gray-200') }}">
                                                                        {{ e($st) }}
                                                                    </span>
                                                                @else
                                                                    —
                                                                @endif
                                                            </td>
                                                            <td class="px-2 py-1">{{ e($row['note']   ?? '—') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-2 flex items-center justify-between">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Total item: {{ $planCount }}
                                            </div>
                                            <a href="{{ route('projects.edit', ['project'=>$p->public_slug ?? $p->id, 'from'=>request()->fullUrl(), 'tab'=>'planning']) }}"
                                               class="text-xs text-amber-600 hover:underline">
                                                Kelola planning →
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-2 space-x-3">
                            <a href="{{ route('projects.show', ['project'=>$p->public_slug ?? $p->id, 'from'=>request()->fullUrl()]) }}"
                               class="text-blue-600 hover:underline">View</a>
                            <a href="{{ route('projects.edit', ['project'=>$p->public_slug ?? $p->id, 'from'=>request()->fullUrl(), 'tab'=>'planning']) }}"
                               class="text-amber-600 hover:underline">Kelola</a>
                            <a href="{{ route('projects.edit', ['project'=>$p->public_slug ?? $p->id, 'from'=>request()->fullUrl()]) }}"
                               class="text-green-600 hover:underline">Edit</a>
                            <form action="{{ route('projects.destroy',$p) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Hapus?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="from" value="{{ request()->fullUrl() }}">
                                <button class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            Tidak ada project in progress.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        @if(method_exists($projects, 'links'))
            {{ $projects->links() }}
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
/* Popover basic styling */
.plan-popover { filter: drop-shadow(0 10px 25px rgba(0,0,0,.15)); }
.dark .plan-popover { filter: drop-shadow(0 10px 25px rgba(0,0,0,.45)); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const openers = document.querySelectorAll('.plan-toggle');
  let openId = null;

  function closeAll() {
    document.querySelectorAll('.plan-popover').forEach(el => el.classList.add('hidden'));
    openId = null;
  }

  openers.forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = btn.getAttribute('data-target');
      const pop = id ? document.getElementById(id) : null;
      if (!pop) return;

      const willOpen = openId !== id;
      closeAll();
      if (willOpen) {
        pop.classList.remove('hidden');
        openId = id;
      }
      e.stopPropagation();
    });
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.plan-popover') && !e.target.closest('.plan-toggle')) {
      closeAll();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAll();
  });
});
</script>
@endpush





