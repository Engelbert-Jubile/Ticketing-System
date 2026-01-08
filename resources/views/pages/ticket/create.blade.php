{{-- resources/views/pages/ticket/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Tiket Baru')

@section('content')
<div class="page-theme page-theme--ticket">
  <div class="page-shell page-shell--wide py-6">
    <div class="space-y-8 mx-auto max-w-6xl">
      {{-- Back --}}
      <div class="flex justify-center md:justify-start">
        @include('components.back', ['to' => request('from', route('dashboard')), 'text' => 'Dashboard', 'icon' => 'home'])
      </div>

      <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-500 via-sky-500 to-cyan-500 p-6 text-white shadow-xl">
        <div class="relative z-[1] flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-3xl font-semibold">Buat Tiket Baru</h2>
            <p class="mt-3 max-w-2xl text-sm text-white/80">Tiga langkah untuk membuat tiket anda: Informasi tiket, Data &amp; detail, dan Lampiran file pendukung.</p>
          </div>
          <div class="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 text-sm backdrop-blur">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                <path d="M7.5 2.25A2.25 2.25 0 0 0 5.25 4.5v15a.75.75 0 0 0 1.06.67l4.19-2.094a.75.75 0 0 1 .66 0l4.19 2.094a.75.75 0 0 0 1.06-.67v-15A2.25 2.25 0 0 0 15.75 2.25h-8.25Z" />
              </svg>
            </div>
            <div>
              <div class="font-semibold">Status Tiket: Baru</div>
              <p class="text-xs text-white/80">Agent/Assigned dapat mengubah In Progress atau Confirmation. Requester dapat mengubah Revision atau Done.</p>
            </div>
          </div>
        </div>
        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
      </div>

      {{-- Wizard Steps --}}
      <div class="mb-6">
        <ol id="ticketWizardSteps" class="flex flex-wrap gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
          <li data-step-label="1" class="wizard-step is-active">Informasi Tiket</li>
          <li data-step-label="2" class="wizard-step">Data &amp; Detail</li>
          <li data-step-label="3" class="wizard-step">Lampiran</li>
        </ol>
      </div>

      <form id="ticketCreateForm" action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf

        {{-- STEP 1 placeholder ... actual fields are in your original file --}}
        <div data-step="1" class="step-section space-y-6">
          @include('partials.attachments', ['initialAttachments' => collect(), 'toggleDefault' => true, 'inputId' => 'ticket-attachments-temp'])
        </div>

        {{-- STEP 3 (width-constrained like step 2) --}}
        <div data-step="3" class="step-section hidden">
          <div class="space-y-6 mx-auto w-full max-w-5xl">
            @include('partials.attachments', ['initialAttachments' => collect(), 'toggleDefault' => true, 'inputId' => 'ticket-attachments'])
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .page-shell { min-height: 100vh; overflow-y: auto; }
  #ticketWizardSteps { display:flex; flex-wrap:wrap; justify-content:center; gap:.5rem .6rem; margin:0 auto 1rem; padding:.25rem .5rem; }
  .wizard-step{display:inline-flex;align-items:center;gap:.55rem;padding:.5rem 1rem;border-radius:9999px;border:1px solid rgba(79,70,229,.18);background:rgba(255,255,255,.8);color:#475569;font-weight:600;white-space:nowrap}
  .wizard-step.is-active{color:#fff;border-color:transparent;background:linear-gradient(135deg,var(--brandA,#4f46e5),var(--brandB,#0ea5e9))}
</style>
@endpush