@extends('layouts.app')
@section('title','Edit Ticket')

@section('content')
@php
  $slaOptions       = $slaOptions       ?? ($slas ?? ['ontime','late']);
  $statusOptions    = $statusOptions    ?? $statusLabelMap ?? ['NEW'=>'New','INPR'=>'In Progress','CONF'=>'Confirmation','REVI'=>'Revision','DONE'=>'Done'];
  // Tentukan opsi status sesuai peran user terhadap tiket
  $allStatuses = ['new','in_progress','confirmation','revision','done'];
  $statuses = [];
  if (auth()->check()) {
    $user = auth()->user();
    if ($ticket->isAgent($user)) {
      $statuses = array_merge($statuses, ['in_progress','confirmation']);
    }
    if ($ticket->isRequester($user)) {
      $statuses = array_merge($statuses, ['revision','done']);
    }
  }
  $statuses = array_values(array_unique(array_merge(['new'], $statuses)));
  $users            = isset($users) ? \Illuminate\Support\Collection::wrap($users) : collect();
  $preAssignedIds   = $ticket->assignedUsers->pluck('id')->all();
  $assignedSelected = (array) old('assigned_user_ids', $preAssignedIds);

  // Prefill time (fallback 00:00)
  $dueTime    = old('due_time')    ?? ($ticket->due_at    ? \Carbon\Carbon::parse($ticket->due_at)->format('H:i')
                                      : (optional($ticket->due_date)?->format('H:i') ?? '00:00'));
  $finishTime = old('finish_time') ?? ($ticket->finish_at ? \Carbon\Carbon::parse($ticket->finish_at)->format('H:i')
                                      : (optional($ticket->finish_date)?->format('H:i') ?? '00:00'));

  $lockCoreFields = false;
  if (auth()->check()) {
    $viewer = auth()->user();
    $lockCoreFields = !\App\Support\RoleHelpers::userIsSuperAdmin($viewer)
      && ($ticket->isRequester($viewer) || $ticket->isAgent($viewer));
  }
@endphp

<div class="mx-auto max-w-7xl px-4 py-6">
  @include('components.back', ['to' => request('from', route('tickets.index'))])

  <div class="mb-4 flex items-center justify-between">
    <h1 class="text-2xl font-bold">Edit Ticket</h1>
    <div class="hidden md:flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
      <span>ID: {{ $ticket->id }}</span><span>â€¢</span>
      <span>Created {{ optional($ticket->created_at)?->diffForHumans() }}</span><span>â€¢</span>
      <span>Updated {{ optional($ticket->updated_at)?->diffForHumans() }}</span>
    </div>
  </div>

  <div class="mb-4">
    <a href="{{ route('tickets.attachments.manage', $ticket) }}"
       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
      Kelola Lampiran
    </a>
  </div>

  

  @if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-700 dark:border-red-700 dark:bg-red-900/20 dark:text-red-200">
      <div class="font-semibold mb-1">Periksa kembali beberapa isian:</div>
      <ul class="list-disc list-inside space-y-0.5">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form id="ticketForm" action="{{ route('tickets.update', $ticket) }}" method="POST" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-12">
    @csrf @method('PUT')

    <input type="hidden" name="from" value="{{ request('from', route('tickets.index')) }}">
    <input type="hidden" name="assigned_id" id="fallback_assigned_id" value="">
    <input type="hidden" name="go_back" id="go_back" value="0">
    <input type="hidden" id="back_to" value="{{ request('from', route('tickets.index')) }}" data-fallback="{{ route('tickets.index') }}">

    {{-- Hidden utk kombinasikan tanggal+jam --}}
    <input type="hidden" name="due_at" id="due_at">
    <input type="hidden" name="finish_at" id="finish_at">

    {{-- KIRI --}}
    <div class="md:col-span-7 space-y-4">
      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <label for="title" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Title</label>
        <input id="title" type="text" name="title"
               value="{{ old('title', $ticket->title) }}"
               required
               @if($lockCoreFields) readonly @endif
               class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-400 focus:ring-blue-200 dark:border-gray-700 dark:focus:border-blue-500 dark:focus:ring-blue-500/30
               @if($lockCoreFields) bg-gray-100 text-gray-500 cursor-not-allowed dark:bg-gray-900/60 dark:text-gray-400 @else dark:bg-gray-900 dark:text-gray-100 @endif">
        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

        <div class="mt-5">
          <div class="mb-2 flex items-center justify-between">
            <label for="description" class="text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
            <span id="descCounter" class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600 dark:bg-blue-900/40 dark:text-blue-200">0 kata</span>
          </div>
          <div id="descWrap" class="ql-card rounded-lg border border-gray-200 shadow-inner dark:border-gray-700">
            <div id="description_editor" class="min-h-[180px] rounded-lg bg-white dark:bg-gray-900"></div>
          </div>
          <textarea id="description" name="description" class="hidden">{{ old('description', $ticket->description) }}</textarea>
          @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
      </div>

      {{-- Title/Info --}}
      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
          <div>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Data &amp; Details</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Ubah status, prioritas, dan peran yang terkait dengan ticket.</p>
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 md:text-right">
            <p>Agent/Assigned dapat mengubah status ke <strong>In Progress</strong> atau <strong>Confirmation</strong>. Requester dapat mengubah ke <strong>Revision</strong> atau <strong>Done</strong>.</p>
          </div>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium">Status</label>
            <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required>
              @foreach ($statuses as $s)
                <option value="{{ $s }}" @selected(old('status',$ticket->status)===$s)>{{ \Illuminate\Support\Str::of($s)->replace('_',' ')->title() }}</option>
              @endforeach
            </select>
            @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium">Status Code</label>
            <select name="status_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
              <option value="">-- None --</option>
              @foreach ($statusOptions as $code => $label)
                <option value="{{ $code }}" @selected(old('status_id',$ticket->status_id)===$code)>{{ $code }} – {{ is_string($label) ? $label : ucwords(str_replace('_',' ',$label)) }}</option>
              @endforeach
            </select>
            @error('status_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">Prioritas</label>
            <select name="priority" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required @if($lockCoreFields) disabled @endif>
              @foreach ($priorities as $p)
                <option value="{{ $p }}" @selected(old('priority',$ticket->priority)===$p)>{{ ucfirst($p) }}</option>
              @endforeach
            </select>
            @error('priority') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">Tipe</label>
            <select name="type" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" required @if($lockCoreFields) disabled @endif>
              @foreach ($types as $t)
                <option value="{{ $t }}" @selected(old('type',$ticket->type)===$t)>{{ ucfirst($t) }}</option>
              @endforeach
            </select>
            @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">SLA</label>
            <select name="sla" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" @if($lockCoreFields) disabled @endif>
              <option value="">-- Pilih --</option>
              @foreach ($slaOptions as $s)
                <option value="{{ $s }}" @selected(old('sla',$ticket->sla)===$s)>{{ strtoupper($s) }}</option>
              @endforeach
            </select>
            @error('sla') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>
          @if ($canPickRequester)
            <div>
              <label class="mb-1 block text-sm font-medium">Requester</label>
              <select name="requester_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">-- Pilih --</option>
                @foreach ($users as $u)
                  @php $label = $u->label ?? ($u->name ?? ($u->email ?? 'User #'.$u->id)); @endphp
                  <option value="{{ $u->id }}" @selected(old('requester_id',$ticket->requester_id)==$u->id)>{{ $label }}</option>
                @endforeach
              </select>
              @error('requester_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
          @else
            @php
              $authUser = auth()->user();
              $currentRequester = old('requester_id', $ticket->requester_id ?? $authUser?->id);
              $requesterLabel = optional($ticket->requester)->label ?? ($authUser?->display_name ?? $authUser?->name ?? $authUser?->email ?? 'User');
            @endphp
            <input type="hidden" name="requester_id" value="{{ $currentRequester }}">
            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium">Requester</label>
              <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                {{ $requesterLabel }}
                <span class="block text-[11px] text-gray-500 dark:text-gray-400">Requester otomatis mengikuti akun pembuat ticket.</span>
              </div>
            </div>
          @endif
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium">Agent (opsional)</label>
            <select name="agent_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
              <option value="">-- Pilih --</option>
              @foreach ($users as $u)
                @php $label = $u->label ?? ($u->name ?? ($u->email ?? 'User #'.$u->id)); @endphp
                <option value="{{ $u->id }}" @selected(old('agent_id',$ticket->agent_id)==$u->id)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      {{-- Dates + Times (modern) --}}
      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid gap-4">
          {{-- DUE --}}
          <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div class="mb-2 flex items-center justify-between">
              <label class="text-sm font-medium">Due</label>
              <span id="due_hint" class="text-xs text-gray-500 dark:text-gray-400"></span>
            </div>
            <div class="grid gap-2 md:grid-cols-2">
              <input type="text" name="due_date" id="due_date" autocomplete="off"
                     value="{{ old('due_date', optional($ticket->due_date)->format('d/m/Y')) }}"
                     class="flatpickr-field w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="dd/mm/yyyy" required @if($lockCoreFields) disabled @endif>
              <input type="text" name="due_time" id="due_time" autocomplete="off"
                     value="{{ $dueTime }}"
                     class="timepicker w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="HH:mm" @if($lockCoreFields) disabled @endif>
            </div>

            {{-- Quick chips --}}
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
              <div class="opacity-60">Tanggal cepat:</div>
              <button type="button" class="btn-chip" data-date="today">Hari ini</button>
              <button type="button" class="btn-chip" data-date="tomorrow">Besok</button>
              <button type="button" class="btn-chip" data-date="+3d">+3 hari</button>
              <button type="button" class="btn-chip" data-date="next_mon">Senin depan</button>

              <div class="ml-3 opacity-60">Jam cepat:</div>
              <button type="button" class="btn-chip" data-time="09:00">09:00</button>
              <button type="button" class="btn-chip" data-time="12:00">12:00</button>
              <button type="button" class="btn-chip" data-time="17:00">17:00</button>
              <button type="button" class="btn-chip" data-time="23:59">23:59</button>
              <button type="button" class="btn-chip" data-time="now">Sekarang</button>
            </div>
          </div>

          {{-- FINISH --}}
          <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div class="mb-2 flex items-center justify-between">
              <label class="text-sm font-medium">Finish (opsional)</label>
              <div class="flex items-center gap-2">
                <button type="button" id="btnCopyDue" class="btn-mini">Finish = Due</button>
                <button type="button" id="btnClearFinish" class="btn-mini">Clear</button>
              </div>
            </div>
            <div class="grid gap-2 md:grid-cols-2">
              <input type="text" name="finish_date" id="finish_date" autocomplete="off"
                     value="{{ old('finish_date', optional($ticket->finish_date)->format('d/m/Y')) }}"
                     class="flatpickr-field w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="dd/mm/yyyy" @if($lockCoreFields) disabled @endif>
              <input type="text" name="finish_time" id="finish_time" autocomplete="off"
                     value="{{ $finishTime }}"
                     class="timepicker w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="HH:mm" @if($lockCoreFields) disabled @endif>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Sticky actions --}}
    <div class="md:col-span-12">
      <div class="sticky bottom-3 z-10 rounded-xl border border-gray-200 bg-white/90 p-3 shadow-lg backdrop-blur dark:border-gray-700 dark:bg-gray-900/80">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="text-xs text-gray-500 dark:text-gray-400">
            Pintasan: <kbd class="rounded border px-1">Ctrl/Cmd</kbd> + <kbd class="rounded border px-1">S</kbd> simpan,
            <kbd class="rounded border px-1">Esc</kbd> batal
          </div>
          <div class="flex items-center gap-2">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Save</button>
            <button type="button" id="btnSaveBack" class="rounded-lg border border-blue-300 px-4 py-2 text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-300 dark:hover:bg-blue-900/30">Save &amp; Back</button>
            <a href="{{ request('from', route('tickets.index')) }}" class="rounded-lg border px-4 py-2 dark:border-gray-700">Cancel</a>
          </div>
        </div>
      </div>
    </div>
    {{-- Lampiran (inline) --}}
    <div class="md:col-span-12">
      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-3 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Lampiran</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Unggah lampiran baru atau hapus yang lama. Perubahan disimpan saat menekan Simpan.</p>
          </div>
        </div>
        @include('partials.attachments', [
          'initialAttachments' => $ticket->attachments ?? collect(),
          'toggleDefault' => true,
          'inputId' => 'ticket-edit-attachments'
        ])
      </div>
    </div>

  </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
<style>
  .ql-card .ql-toolbar.ql-snow{border-radius:.5rem .5rem 0 0;border:none;border-bottom:1px solid rgba(148,163,184,.3)}
  .ql-card .ql-container.ql-snow{border:none;border-radius:0 0 .5rem .5rem}
  .chip{display:inline-flex;align-items:center;gap:.35rem;border-radius:9999px;padding:.2rem .6rem;font-size:.75rem;border:1px solid rgba(148,163,184,.6);background:rgba(241,245,249,.7)}
  .chip button{line-height:0;border:0;background:transparent;cursor:pointer;opacity:.7}
  .dark .chip{background:rgba(2,6,23,.5);border-color:#334155;color:#e2e8f0}

  .btn-chip{padding:.25rem .55rem;border:1px solid #cbd5e1;border-radius:9999px;background:#f8fafc}
  .btn-chip:hover{background:#eef2ff}
  .dark .btn-chip{background:#0b1220;border-color:#334155;color:#e2e8f0}
  .dark .btn-chip:hover{background:#111827}

  .btn-mini{font-size:.75rem;padding:.25rem .5rem;border:1px solid #cbd5e1;border-radius:.375rem;background:#f8fafc}
  .btn-mini:hover{background:#eef2ff}
  .dark .btn-mini{background:#0b1220;border-color:#334155;color:#e2e8f0}
  .dark .btn-mini:hover{background:#111827}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // ==== BACK URL
  var backEl = document.getElementById('back_to');
  var BACK_TO = backEl ? (backEl.value || backEl.getAttribute('data-fallback') || '/') : '/';

  // ==== QUILL
  var wrap = document.getElementById('descWrap');
  var elEditor = document.getElementById('description_editor');
  var elHidden = document.getElementById('description');
  if (wrap && elEditor && elHidden && window.Quill) {
    var quill = elEditor.__quill;
    if (!quill) {
      quill = new Quill(elEditor, {
        theme: 'snow',
        placeholder: 'Tulis detail tiket di siniâ€¦',
        modules: { toolbar: [[{ header:[1,2,3,false]}],['bold','italic','underline','strike','blockquote'],[{ align:[] }],[{ list:'ordered'},{ list:'bullet'},{ indent:'-1'},{ indent:'+1'}],['link','code-block','clean']] }
      });
      elEditor.__quill = quill;
    }

    if (elHidden.value && elHidden.value.trim()) {
      quill.clipboard.dangerouslyPasteHTML(elHidden.value);
    }

    if (!elEditor.dataset.quillBound) {
      var sync = function(){
        elHidden.value = quill.root.innerHTML;
        var t = quill.getText(); if (t && t.slice(-1)==='\n') t = t.slice(0,-1);
        var words = t && t.trim() ? t.trim().split(/\s+/).length : 0;
        var cnt = document.getElementById('descCounter'); if (cnt) cnt.textContent = words+' kata';
      };

      quill.on('text-change', sync);
      quill.on('selection-change', function(r){ wrap.classList.toggle('ring-2', !!r); });
      elHidden.closest('form')?.addEventListener('submit', sync, {capture:true});
      elEditor.dataset.quillBound = '1';
      sync();
    }
  }

  // ==== FLATPICKR
  if (window.flatpickr) {
    flatpickr(".flatpickr-field", { dateFormat: "d/m/Y", allowInput: true });
    flatpickr("#due_time",   { enableTime:true, noCalendar:true, dateFormat:"H:i", time_24hr:true, minuteIncrement:5, allowInput:true });
    flatpickr("#finish_time",{ enableTime:true, noCalendar:true, dateFormat:"H:i", time_24hr:true, minuteIncrement:5, allowInput:true });
  }

  // ==== Due hint (hari ke jatuh tempo)
  var dueDateEl = document.getElementById('due_date');
  var dueTimeEl = document.getElementById('due_time');
  var dueHint   = document.getElementById('due_hint');
  function updateDueHint(){
    if (!dueDateEl || !dueDateEl.value){ if (dueHint) dueHint.textContent=''; return; }
    var p = (dueDateEl.value||'').split('/'); if (p.length!==3){ dueHint.textContent=''; return; }
    var d = p[2]+'-'+p[1]+'-'+p[0]+'T'+((dueTimeEl&&dueTimeEl.value)||'00:00')+':00';
    var due = new Date(d);
    var now = new Date();
    var diffMs = due - now;
    var diffDays = Math.floor(diffMs / (1000*60*60*24));
    var diffHours = Math.floor((diffMs % (1000*60*60*24)) / (1000*60*60));
    if (isNaN(diffMs)){ dueHint.textContent=''; return; }
    var label = diffMs < 0 ? 'Overdue ' + Math.abs(diffDays) + 'h+' :
                (diffDays>0 ? ('Dalam '+diffDays+' hari '+diffHours+' jam') :
                 (diffHours>=0 ? ('Dalam '+diffHours+' jam') : 'Hari ini'));
    dueHint.textContent = label;
  }
  dueDateEl?.addEventListener('change', updateDueHint);
  dueTimeEl?.addEventListener('change', updateDueHint);
  updateDueHint();

  // ==== Quick chips
  function setDateFromChip(key){
    var base = new Date(); base.setHours(0,0,0,0);
    if (key==='tomorrow') base.setDate(base.getDate()+1);
    else if (key==='+3d') base.setDate(base.getDate()+3);
    else if (key==='next_mon') {
      var day = base.getDay(); // 0..6
      var add = (8 - day) % 7; if (add===0) add = 7;
      base.setDate(base.getDate()+add);
    } // 'today' => no change
    var dd  = String(base.getDate()).padStart(2,'0');
    var mm  = String(base.getMonth()+1).padStart(2,'0');
    var yyy = base.getFullYear();
    dueDateEl.value = dd+'/'+mm+'/'+yyy;
    dueDateEl.dispatchEvent(new Event('change', {bubbles:true}));
  }
  function setTimeFromChip(val, inputEl){
    var t = val;
    if (val==='now'){ var n=new Date(); t = String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0'); }
    inputEl.value = t;
    inputEl.dispatchEvent(new Event('change', {bubbles:true}));
  }
  document.querySelectorAll('.btn-chip').forEach(btn=>{
    btn.addEventListener('click', function(){
      if (this.dataset.date) setDateFromChip(this.dataset.date);
      if (this.dataset.time) setTimeFromChip(this.dataset.time, dueTimeEl);
    });
  });

  // ==== Finish helpers
  document.getElementById('btnCopyDue')?.addEventListener('click', function(){
    var fd = document.getElementById('finish_date'), ft=document.getElementById('finish_time');
    if (fd && dueDateEl) fd.value = dueDateEl.value;
    if (ft && dueTimeEl) ft.value = dueTimeEl.value;
    fd?.dispatchEvent(new Event('change',{bubbles:true}));
    ft?.dispatchEvent(new Event('change',{bubbles:true}));
  });
  document.getElementById('btnClearFinish')?.addEventListener('click', function(){
    var fd = document.getElementById('finish_date'), ft=document.getElementById('finish_time');
    if (fd) fd.value = '';
    if (ft) ft.value = '';
  });

  // ==== Assignees (toggle + chips + search)
  var sel = document.getElementById('assigned_user_ids');
  var chipsWrap = document.getElementById('assigneeChips');
  var search = document.getElementById('assigneeSearch');
  var clearBtn = document.getElementById('assigneeClear');

  function selectedOptionsCompat(el){ if (el.selectedOptions) return el.selectedOptions; var arr=[]; for(var i=0;i<el.options.length;i++){ if(el.options[i].selected) arr.push(el.options[i]); } return arr; }
  function renderChips(){
    if (!sel || !chipsWrap) return; chipsWrap.innerHTML='';
    var selected = selectedOptionsCompat(sel);
    for (var i=0;i<selected.length;i++){
      (function(opt){ var chip=document.createElement('span'); chip.className='chip';
        chip.innerHTML='<span>'+opt.text+'</span><button type="button" aria-label="remove">&times;</button>';
        chip.querySelector('button').addEventListener('click', function(){ opt.selected=false; renderChips(); });
        chipsWrap.appendChild(chip);
      })(selected[i]);
    }
  }
  if (sel){
    sel.addEventListener('mousedown', function(e){ var opt=e.target.closest && e.target.closest('option'); if(!opt) return; e.preventDefault(); opt.selected=!opt.selected; sel.dispatchEvent(new Event('change',{bubbles:true})); });
    sel.addEventListener('change', renderChips); renderChips();
  }
  search?.addEventListener('input', function(){ var q=(search.value||'').toLowerCase().trim(); if(!sel) return; for(var i=0;i<sel.options.length;i++){ var o=sel.options[i]; var lbl=((o.dataset.label||o.text)+'').toLowerCase(); o.hidden = q && lbl.indexOf(q)===-1; } });
  clearBtn?.addEventListener('click', function(){ search.value=''; search.dispatchEvent(new Event('input')); });

  // ==== Compose *_at sebelum submit
  var form = document.getElementById('ticketForm');
  function toIsoDate(dmy){ var p=(dmy||'').split('/'); if(p.length!==3) return null; return p[2]+'-'+p[1].padStart(2,'0')+'-'+p[0].padStart(2,'0'); }
  function composeDateTime(dateStr, timeStr){ var d=toIsoDate(dateStr); if(!d) return ''; var t=(timeStr||'00:00'); if(!/^\d{2}:\d{2}$/.test(t)) t='00:00'; return d+' '+t+':00'; }
  form?.addEventListener('submit', function(){
    // fallback assigned_id
    var fb  = document.getElementById('fallback_assigned_id');
    var first=''; if(sel){ var s=selectedOptionsCompat(sel); if(s.length) first=s[0].value; } if (fb) fb.value = first;

    // set hidden *_at
    var due_at    = document.getElementById('due_at');
    var finish_at = document.getElementById('finish_at');
    if (due_at)    due_at.value    = composeDateTime(dueDateEl?.value,  dueTimeEl?.value);
    if (finish_at) finish_at.value = composeDateTime(document.getElementById('finish_date')?.value, document.getElementById('finish_time')?.value);
  });

  // Shortcuts
  document.getElementById('btnSaveBack')?.addEventListener('click', function(){ var gb=document.getElementById('go_back'); if(gb) gb.value='1'; form?.requestSubmit?.() ?? form?.submit(); });
  document.addEventListener('keydown', function(e){ var ctrl=e.ctrlKey||e.metaKey; if(ctrl && String(e.key).toLowerCase()==='s'){ e.preventDefault(); form?.requestSubmit?.() ?? form?.submit(); } if(String(e.key)==='Escape'){ window.location.href = BACK_TO; } });
});
</script>
@endpush
