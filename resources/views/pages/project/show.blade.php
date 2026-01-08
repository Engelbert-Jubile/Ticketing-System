{{-- LEGACY (fallback) resources/views/pages/project/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Project Detail')

@section('content')
<div class="mx-auto max-w-6xl flex flex-col gap-6 px-4 py-6">
    <div class="w-fit">
        @include('components.back', [
            'to' => request('from', route('projects.report'))
        ])
    </div>

    @php
        $statusValue = $project->status instanceof \BackedEnum ? $project->status->value : ($project->status ?? '');
        $statusValue = \App\Support\WorkflowStatus::normalize($statusValue);
        $statusLabel = \App\Support\WorkflowStatus::label($statusValue);

        $tz = config('app.timezone');
        $created = $project->created_at?->timezone($tz)?->translatedFormat('d M Y H:i') ?? '—';
        $updated = $project->updated_at?->timezone($tz)?->translatedFormat('d M Y H:i') ?? '—';
        $start   = $project->start_date instanceof \Carbon\CarbonInterface ? $project->start_date->format('d/m/Y') : ($project->start_date ?: '—');
        $end     = $project->end_date instanceof \Carbon\CarbonInterface ? $project->end_date->format('d/m/Y') : ($project->end_date ?: '—');

        $ticket = $project->relationLoaded('ticket') ? $project->ticket : $project->ticket()->with('attachments')->first();
        $ticketNo = $ticket?->ticket_no ?? '—';
        $ticketTitle = $ticket?->title ?? null;

        $projAtts = ($project->attachments instanceof \Illuminate\Support\Collection)
            ? $project->attachments
            : collect($project->attachments ?? []);
        $ticketAtts = ($ticket?->attachments instanceof \Illuminate\Support\Collection)
            ? $ticket->attachments
            : collect($ticket?->attachments ?? []);
        $attachments = $projAtts->merge($ticketAtts);
    @endphp

    <!-- PROJECT OVERVIEW -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-colors duration-300 dark:border-slate-700 dark:bg-gray-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $project->title }}</h1>
                @if($ticketTitle)
                    <p class="text-sm text-slate-500 dark:text-slate-400">Ticket terkait: {{ $ticketTitle }} ({{ $ticketNo }})</p>
                @elseif($ticketNo !== '—')
                    <p class="text-sm text-slate-500 dark:text-slate-400">Ticket No: {{ $ticketNo }}</p>
                @endif
            </div>
            <div class="flex flex-col gap-2 items-end text-sm">
                <span class="status-badge" data-status="{{ $statusValue }}">{{ $statusLabel }}</span>
                <span class="rounded border border-slate-300 px-2 py-1 text-slate-600 dark:border-slate-600 dark:text-slate-300">Status ID: {{ $project->status_id ?? '—' }}</span>
            </div>
        </div>

        <div class="mt-6 grid gap-3 text-sm text-slate-700 dark:text-slate-200 sm:grid-cols-2 lg:grid-cols-3">
            <div><span class="font-semibold">Project No</span>: {{ $project->project_no ?? '—' }}</div>
            <div><span class="font-semibold">Created</span>: {{ $created }}</div>
            <div><span class="font-semibold">Start</span>: {{ $start }}</div>
            <div><span class="font-semibold">End</span>: {{ $end }}</div>
            <div><span class="font-semibold">Updated</span>: {{ $updated }}</div>
            <div><span class="font-semibold">Ticket</span>: {{ $ticketNo }}</div>
        </div>

        <div class="mt-6 space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-600 dark:text-slate-300 mb-2">Deskripsi</h2>
                <div class="prose prose-sm max-w-none text-slate-700 dark:prose-invert dark:text-slate-200">
                    {!! $project->description ? str_replace(['<p>', '</p>'], '', $project->description) : '<span class="text-slate-400">—</span>' !!}
                </div>
            </div>
        </div>
    </div>

    <!-- TEAM PROJECT -->
    @if($project->pics && count($project->pics) > 0)
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-gray-800">
        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">Tim Project</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($project->pics as $pic)
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                <div class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ $pic->user?->name ?? 'User #' . $pic->user_id }}</div>
                <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">{{ $pic->position ?? '—' }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- ACTION PLAN -->
    @if($project->actions && count($project->actions) > 0)
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-gray-800">
        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">Action Plan</h2>
        <div class="space-y-6">
            @foreach($project->actions as $action)
            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ $action->title }}</h3>
                        <div class="text-xs text-slate-600 dark:text-slate-400 mt-1 space-y-1">
                            <div>Status: <span class="font-semibold">{{ $action->status_id ?? '—' }}</span></div>
                            <div>Progress: <span class="font-semibold">{{ $action->progress ?? 0 }}%</span></div>
                            <div>Mulai: <span class="font-semibold">{{ $action->start_date ? $action->start_date->format('d/m/Y') : '—' }}</span></div>
                            <div>Selesai: <span class="font-semibold">{{ $action->end_date ? $action->end_date->format('d/m/Y') : '—' }}</span></div>
                        </div>
                    </div>
                </div>
                @if($action->description)
                <div class="text-sm text-slate-700 dark:text-slate-300 mb-3 p-2 bg-white/50 rounded dark:bg-gray-900/30">
                    {!! $action->description !!}
                </div>
                @endif
                
                <!-- SUB-ACTIONS -->
                @if($action->subactions && count($action->subactions) > 0)
                <div class="mt-4 pl-4 border-l-2 border-indigo-300 dark:border-indigo-700 space-y-3">
                    <div class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">SUB-AKSI:</div>
                    @foreach($action->subactions as $sub)
                    <div class="rounded bg-white p-3 dark:bg-gray-900">
                        <div class="font-semibold text-sm text-slate-900 dark:text-slate-100">{{ $sub->title }}</div>
                        <div class="text-xs text-slate-600 dark:text-slate-400 mt-1 grid grid-cols-2 gap-2">
                            <div>Status: {{ $sub->status_id ?? '—' }}</div>
                            <div>Progress: {{ $sub->progress ?? 0 }}%</div>
                            <div>Mulai: {{ $sub->start_date ? $sub->start_date->format('d/m/Y') : '—' }}</div>
                            <div>Selesai: {{ $sub->end_date ? $sub->end_date->format('d/m/Y') : '—' }}</div>
                        </div>
                        @if($sub->description)
                        <div class="text-xs text-slate-600 dark:text-slate-400 mt-2 p-2 bg-slate-50 rounded dark:bg-gray-800">
                            {!! $sub->description !!}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- RINCIAN BUDGET -->
    @if($project->costs && count($project->costs) > 0)
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-gray-800">
        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">Rincian Budget</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20">
                        <th class="text-left px-3 py-2 font-semibold">Item Biaya</th>
                        <th class="text-left px-3 py-2 font-semibold">Kategori</th>
                        <th class="text-right px-3 py-2 font-semibold">Estimasi</th>
                        <th class="text-right px-3 py-2 font-semibold">Aktual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach($project->costs as $cost)
                    <tr class="hover:bg-slate-50 dark:hover:bg-gray-700/30">
                        <td class="px-3 py-2">{{ $cost->cost_item ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $cost->category ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ $cost->estimated_cost ? number_format($cost->estimated_cost, 0, ',', '.') : '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ $cost->actual_cost ? number_format($cost->actual_cost, 0, ',', '.') : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- MITIGASI RESIKO -->
    @if($project->risks && count($project->risks) > 0)
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-gray-800">
        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">Mitigasi Resiko</h2>
        <div class="space-y-4">
            @foreach($project->risks as $risk)
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 dark:border-rose-800 dark:bg-rose-900/20">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ $risk->name }}</h3>
                    <div class="text-xs space-x-1">
                        <span class="inline-block px-2 py-1 bg-rose-200 text-rose-800 rounded dark:bg-rose-700 dark:text-rose-100">Status: {{ $risk->status_id ?? '—' }}</span>
                    </div>
                </div>
                <div class="text-sm text-slate-700 dark:text-slate-300 space-y-2">
                    <div><span class="font-semibold">Dampak:</span> {{ ucfirst($risk->impact ?? '—') }}</div>
                    <div><span class="font-semibold">Kemungkinan:</span> {{ ucwords(str_replace('_', ' ', $risk->likelihood ?? '—')) }}</div>
                    @if($risk->description)
                    <div><span class="font-semibold">Deskripsi:</span> {!! $risk->description !!}</div>
                    @endif
                    @if($risk->mitigation_plan)
                    <div><span class="font-semibold">Mitigasi:</span> {!! $risk->mitigation_plan !!}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- DELIVERABLES -->
    @if($project->deliverables && count($project->deliverables) > 0)
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-gray-800">
        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">Deliverables</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($project->deliverables as $deliverable)
            <div class="rounded-lg border border-teal-200 bg-teal-50 p-4 dark:border-teal-800 dark:bg-teal-900/20">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ $deliverable->name }}</h3>
                <div class="text-xs text-slate-600 dark:text-slate-400 mt-2 space-y-1">
                    <div>Status: <span class="font-semibold">{{ $deliverable->status_id ?? '—' }}</span></div>
                    <div>Selesai Pada: <span class="font-semibold">{{ $deliverable->completed_at ? $deliverable->completed_at->format('d/m/Y H:i') : '—' }}</span></div>
                    <div>Verifikasi Pada: <span class="font-semibold">{{ $deliverable->verified_at ? $deliverable->verified_at->format('d/m/Y H:i') : '—' }}</span></div>
                    <div>Verified By: <span class="font-semibold">{{ ucfirst($deliverable->verified_by ?? '—') }}</span></div>
                </div>
                @if($deliverable->description)
                <div class="text-xs text-slate-600 dark:text-slate-400 mt-2 p-2 bg-white/50 rounded dark:bg-gray-900/30">
                    {!! $deliverable->description !!}
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- LAMPIRAN -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-gray-800">
        <h2 class="text-sm font-semibold text-slate-600 dark:text-slate-300 mb-3">Lampiran</h2>
        @if($attachments->isNotEmpty())
            <ul class="space-y-2">
                @foreach($attachments as $att)
                <li class="flex items-center justify-between p-3 rounded-lg border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-gray-700/30 text-sm">
                    <div class="flex-1">
                        @if(!empty($att->original_name))
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $att->original_name }}</span>
                        @else
                            <span class="font-medium text-slate-700 dark:text-slate-200">Lampiran</span>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('attachments.view', $att) }}" target="_blank" class="px-3 py-1 rounded bg-blue-600 text-white text-xs hover:bg-blue-700">Lihat</a>
                        <a href="{{ route('attachments.download', $att) }}" class="px-3 py-1 rounded bg-blue-600 text-white text-xs hover:bg-blue-700">Unduh</a>
                    </div>
                </li>
                @endforeach
            </ul>
        @else
            <p class="text-slate-400 text-sm">Belum ada lampiran.</p>
        @endif
    </div>

    <!-- ACTION BUTTONS -->
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('projects.edit', [
            'project' => $project->public_slug ?? $project->id,
            'from'    => request('from'),
            'src'     => 'detail'
        ]) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
            Edit Project
        </a>
        @if($ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700/40">
                Lihat Ticket
            </a>
        @endif
    </div>
</div>
@endsection
