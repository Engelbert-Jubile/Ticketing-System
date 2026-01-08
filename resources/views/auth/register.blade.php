@php
$recaptcha = app(\App\Services\Security\RecaptchaVerifier::class);
$unitOptions = $units ?? \App\Support\UserUnitOptions::values();
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Register &mdash; TICKORA</title>
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
  @vite(['resources/css/app.css'])
  @livewireStyles
  <style>
    :root {
      --brand-blue: #123b7a;
      --brand-orange: #f5862a;
      --brand-k-stem: 38%;
      --brand-k-epsilon: 1px;
    }

    .brand-accent-k {
      position: relative;
      display: inline-block;
      color: var(--brand-orange);
      -webkit-text-fill-color: currentColor;
      line-height: 1;
    }

    .brand-accent-k::after {
      content: attr(data-letter);
      position: absolute;
      inset: 0;
      color: var(--brand-blue);
      -webkit-text-fill-color: currentColor;
      clip-path: inset(0 calc(100% - (var(--brand-k-stem) + var(--brand-k-epsilon))) 0 0);
      pointer-events: none;
    }

    .unit-select .unit-menu {
      max-height: 0;
      opacity: 0;
      transform: translateY(-6px) scale(.985);
      pointer-events: none;
      overflow: hidden;
      transition: max-height .22s ease, opacity .18s ease, transform .18s ease;
    }

    .unit-select[data-open="true"] .unit-menu {
      max-height: 18rem;
      opacity: 1;
      transform: translateY(0) scale(1);
      pointer-events: auto;
      overflow: auto;
    }

    .unit-select .unit-chevron {
      transition: transform .18s ease;
    }

    .unit-select[data-open="true"] .unit-chevron {
      transform: rotate(180deg);
    }

    @media (prefers-reduced-motion: reduce) {
      .unit-select .unit-menu {
        transition: none;
      }

      .unit-select .unit-chevron {
        transition: none;
      }
    }
  </style>
  @if ($recaptcha->isEnabled())
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  @endif
</head>

<body class="font-sans antialiased">
  <div class="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
      <div class="absolute -top-24 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-blue-200/70 blur-3xl dark:bg-blue-900/30"></div>
      <div class="absolute -bottom-28 -left-28 h-80 w-80 rounded-full bg-amber-200/70 blur-3xl dark:bg-amber-900/25"></div>
      <div class="absolute top-1/3 -right-28 h-96 w-96 rounded-full bg-indigo-200/60 blur-3xl dark:bg-indigo-900/25"></div>
    </div>

    <div class="relative flex min-h-screen items-center justify-center p-4">
      <div class="w-full max-w-xl">
        <div class="mb-7 flex flex-col items-center text-center">
          <div class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/75 ring-1 ring-slate-200/70 shadow-sm shadow-blue-200/50 backdrop-blur dark:bg-slate-900/70 dark:ring-slate-800/70 dark:shadow-none">
            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 12a3.25 3.25 0 1 0-3.25-3.25A3.25 3.25 0 0 0 12 12Z" stroke="var(--brand-blue)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="var(--brand-blue)" stroke-width="1.8" stroke-linecap="round" />
              <path d="M18 7v6M15 10h6" stroke="var(--brand-orange)" stroke-width="1.8" stroke-linecap="round" />
            </svg>
          </div>

          <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl" style="color: var(--brand-blue);">
            TIC<span class="brand-accent-k" data-letter="K">K</span>ORA
          </h1>
          <p class="mt-2 text-sm font-medium tracking-wide" style="color: var(--brand-blue);">
            Ticket Management System
          </p>
        </div>

        <div class="rounded-3xl bg-white/85 p-8 shadow-xl shadow-blue-200/40 ring-1 ring-slate-200/70 backdrop-blur dark:bg-slate-900/70 dark:ring-slate-800/70 dark:shadow-none sm:p-10">
          <div class="mb-6 space-y-1 text-center">
            <h2 class="text-2xl font-semibold tracking-tight">Daftar Akun Baru</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">Buat akun untuk mulai mengelola ticket dan pekerjaan tim.</p>
          </div>

          @php
            $currentLocale = app()->getLocale() ?? config('app.locale', 'en');
          @endphp
          <form id="register-form" method="POST" action="{{ route('register.store', ['locale' => $currentLocale]) }}" class="space-y-5">
            @csrf
            @php
            $lockedDomain = '@kftd.co.id';
            $oldEmail = old('email', '');
            $oldEmailLocal = '';
            if (is_string($oldEmail) && $oldEmail !== '') {
            $oldEmailLower = strtolower(trim($oldEmail));
            if (str_ends_with($oldEmailLower, $lockedDomain)) {
            $oldEmailLocal = substr($oldEmailLower, 0, -strlen($lockedDomain));
            } else {
            $atPos = strpos($oldEmailLower, '@');
            $oldEmailLocal = $atPos === false ? $oldEmailLower : substr($oldEmailLower, 0, $atPos);
            }
            }
            @endphp

            <div class="space-y-1">
              <label for="username" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Username</label>
              <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="currentColor" stroke-width="1.8" />
                    <path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                  </svg>
                </span>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required autocomplete="username"
                  class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none" />
              </div>
              @error('username') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div class="space-y-1">
                <label for="first_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Nama Depan</label>
                <div class="relative">
                  <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M6.5 19.5h11A2.5 2.5 0 0 0 20 17V7A2.5 2.5 0 0 0 17.5 4.5h-11A2.5 2.5 0 0 0 4 7v10A2.5 2.5 0 0 0 6.5 19.5Z" stroke="currentColor" stroke-width="1.8" />
                      <path d="M8 9h8M8 12h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                  </span>
                  <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name"
                    class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none" />
                </div>
                @error('first_name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
              </div>

              <div class="space-y-1">
                <label for="last_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Nama Belakang</label>
                <div class="relative">
                  <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M6.5 19.5h11A2.5 2.5 0 0 0 20 17V7A2.5 2.5 0 0 0 17.5 4.5h-11A2.5 2.5 0 0 0 4 7v10A2.5 2.5 0 0 0 6.5 19.5Z" stroke="currentColor" stroke-width="1.8" />
                      <path d="M8 9h8M8 12h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                  </span>
                  <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name"
                    class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none" />
                </div>
                @error('last_name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
              </div>
            </div>

            <div class="space-y-1">
              <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Email</label>
              <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8" />
                    <path d="m5.5 7 6.1 4.07a1 1 0 0 0 1.1 0L18.8 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>
                <input id="email" type="hidden" name="email" value="{{ ($oldEmailLocal ?? '') ? (($oldEmailLocal ?? '') . ($lockedDomain ?? '@kftd.co.id')) : '' }}" />
                <input id="email_local" type="text" inputmode="email" autocapitalize="none" spellcheck="false" value="{{ $oldEmailLocal ?? '' }}" required autocomplete="username"
                  aria-label="Email (tanpa domain)"
                  class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 pr-28 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none" />
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-semibold tracking-wide text-slate-500 dark:text-slate-300">{{ $lockedDomain ?? '@kftd.co.id' }}</span>
              </div>
              @error('email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1">
              <label for="unit" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Unit</label>
              <div class="unit-select relative" id="unitSelect" data-open="false">
                <input id="unit" type="hidden" name="unit" value="{{ old('unit') }}" />
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4.5 20V6.5A2.5 2.5 0 0 1 7 4h10a2.5 2.5 0 0 1 2.5 2.5V20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    <path d="M8 9h.01M12 9h.01M16 9h.01M8 13h.01M12 13h.01M16 13h.01" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" />
                    <path d="M9 20v-4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4" stroke="currentColor" stroke-width="1.8" />
                  </svg>
                </span>

                <button id="unitButton" type="button"
                  class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 pr-10 text-left text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition hover:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none dark:hover:bg-slate-950/55"
                  aria-haspopup="listbox" aria-expanded="false" aria-controls="unitListbox">
                  <span id="unitButtonLabel" class="{{ old('unit') ? '' : 'text-slate-500 dark:text-slate-400' }}">{{ old('unit') ?: 'Pilih Unit' }}</span>
                </button>

                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                  <svg class="unit-chevron h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>

                <div id="unitListbox" role="listbox" tabindex="-1"
                  class="unit-menu absolute left-0 right-0 top-full z-30 mt-2 rounded-2xl border border-slate-200 bg-white/95 shadow-xl shadow-slate-200/60 ring-1 ring-slate-200/60 backdrop-blur dark:border-slate-800 dark:bg-slate-950/85 dark:shadow-none">
                  <div class="py-1">
                    <button type="button" class="unit-option w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-900/50"
                      data-unit-option data-value="" role="option" aria-selected="{{ old('unit') ? 'false' : 'true' }}">
                      Pilih Unit
                    </button>
                    @foreach($unitOptions as $unit)
                    <button type="button" class="unit-option w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-900/50"
                      data-unit-option data-value="{{ $unit }}" role="option" aria-selected="{{ old('unit') === $unit ? 'true' : 'false' }}">
                      {{ $unit }}
                    </button>
                    @endforeach
                  </div>
                </div>
              </div>
              <p id="unitClientError" class="hidden text-sm text-red-600"></p>
              @error('unit') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div class="space-y-1">
                <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Password</label>
                <div class="relative">
                  <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M7 10V8.2a5 5 0 0 1 10 0V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                      <path d="M6.5 10h11A2.5 2.5 0 0 1 20 12.5v5A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-5A2.5 2.5 0 0 1 6.5 10Z" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                  </span>
                  <input id="password" type="password" name="password" required autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 pr-12 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none" />
                  <button type="button"
                    class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-500 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 dark:text-slate-300 dark:hover:text-white"
                    data-password-toggle="password"
                    aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan password">
                    <svg data-eye="closed" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 9c-.633.873-1 1.918-1 3 0 2.761 2.239 5 5 5 1.082 0 2.127-.367 3-1"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5.5 6.5C3.372 8.183 2 10 2 12c0 0 4 7 10 7 1.422 0 2.765-.3 4-.82"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M18.5 17.5C20.628 15.817 22 14 22 12c0 0-4-7-10-7-1.422 0-2.765.3-4 .82"></path>
                    </svg>
                    <svg data-eye="open" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"></path>
                      <circle cx="12" cy="12" r="3.25" stroke-linecap="round" stroke-linejoin="round"></circle>
                    </svg>
                  </button>
                </div>
                @error('password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
              </div>

              <div class="space-y-1">
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Konfirmasi Password</label>
                <div class="relative">
                  <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M7 10V8.2a5 5 0 0 1 10 0V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                      <path d="M6.5 10h11A2.5 2.5 0 0 1 20 12.5v5A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-5A2.5 2.5 0 0 1 6.5 10Z" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                  </span>
                  <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 pr-12 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none" />
                  <button type="button"
                    class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-500 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 dark:text-slate-300 dark:hover:text-white"
                    data-password-toggle="password_confirmation"
                    aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan password">
                    <svg data-eye="closed" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 9c-.633.873-1 1.918-1 3 0 2.761 2.239 5 5 5 1.082 0 2.127-.367 3-1"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5.5 6.5C3.372 8.183 2 10 2 12c0 0 4 7 10 7 1.422 0 2.765-.3 4-.82"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M18.5 17.5C20.628 15.817 22 14 22 12c0 0-4-7-10-7-1.422 0-2.765.3-4 .82"></path>
                    </svg>
                    <svg data-eye="open" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"></path>
                      <circle cx="12" cy="12" r="3.25" stroke-linecap="round" stroke-linejoin="round"></circle>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            @if ($recaptcha->isEnabled())
            <div class="space-y-2">
              <div class="flex justify-center">
                <div class="g-recaptcha" data-sitekey="{{ $recaptcha->siteKey() }}"></div>
              </div>
              @error('g-recaptcha-response') <p class="text-center text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            @endif

            <button type="submit"
              class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm shadow-emerald-600/25 transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 focus-visible:ring-offset-2 focus-visible:ring-offset-white disabled:opacity-70 dark:focus-visible:ring-offset-slate-900">
              <span class="!text-white">Daftar</span>
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M15.5 21v-1.2a4.8 4.8 0 0 0-4.8-4.8H6.8A4.8 4.8 0 0 0 2 19.8V21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M8.75 11.5a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="m16 13.5 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
          </form>

          <div class="mt-6 flex flex-col items-center gap-3 text-center text-sm text-slate-600 dark:text-slate-300">
            <a wire:navigate.hover href="{{ url('/') }}" class="font-medium text-slate-700 underline-offset-4 hover:underline dark:text-slate-200">Kembali ke halaman welcome</a>
            <p>
              Sudah punya akun?
              <a wire:navigate.hover href="{{ route('login', ['locale' => $currentLocale]) }}" class="font-semibold text-blue-700 underline-offset-4 hover:underline dark:text-blue-300">Login di sini</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    :root {
      --lo-primary: #10b981;
      --lo-text: #334155;
      --lo-text-strong: #0f172a;
      --lo-bg-surface: rgba(17, 24, 39, .58)
    }

    @media (prefers-color-scheme: dark) {
      :root {
        --lo-primary: #34d399;
        --lo-text: #cbd5e1;
        --lo-text-strong: #e2e8f0;
        --lo-bg-surface: rgba(2, 6, 23, .72)
      }
    }

    #registerOverlay {
      position: fixed;
      inset: 0;
      background: radial-gradient(ellipse at center, rgba(255, 255, 255, .10) 0%, rgba(255, 255, 255, 0) 42%), var(--lo-bg-surface);
      backdrop-filter: blur(3px);
      -webkit-backdrop-filter: blur(3px);
      z-index: 1050;
      opacity: 0;
      visibility: hidden;
      transition: opacity .18s ease, visibility .18s ease;
      pointer-events: none
    }

    #registerOverlay.show {
      opacity: 1;
      visibility: visible;
      pointer-events: auto
    }

    #registerOverlay .wrap {
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column
    }

    #registerOverlay .spinner-svg {
      width: 64px;
      height: 64px;
      display: block;
      filter: drop-shadow(0 2px 8px rgba(0, 0, 0, .28))
    }

    #registerOverlay .msg {
      margin-top: 14px;
      color: var(--lo-text-strong);
      font-weight: 600;
      font-size: 14px;
      letter-spacing: .2px;
      text-shadow: 0 1px 2px rgba(0, 0, 0, .18)
    }
  </style>

  <div id="registerOverlay" aria-hidden="true">
    <div class="wrap">
      <svg class="spinner-svg" width="60" height="60" viewBox="0 0 50 50" aria-label="Loading" role="status">
        <defs>
          <linearGradient id="ro-g" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#a7f3d0" stop-opacity="0.22" />
            <stop offset="50%" stop-color="#10b981" stop-opacity="1" />
            <stop offset="100%" stop-color="#a7f3d0" stop-opacity="0.22" />
          </linearGradient>
        </defs>
        <circle cx="25" cy="25" r="20" fill="none" stroke="url(#ro-g)" stroke-width="6" stroke-linecap="round" stroke-dasharray="110" stroke-dashoffset="80">
          <animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.9s" repeatCount="indefinite" />
        </circle>
      </svg>
      <div class="msg">Sedang mendaftar, mohon tunggu&hellip;</div>
    </div>
  </div>

  <script>
    (function() {
      document.querySelectorAll('[data-password-toggle]').forEach(function(btn) {
        var targetId = btn.getAttribute('data-password-toggle');
        var input = document.getElementById(targetId);
        if (!input) return;
        btn.addEventListener('click', function() {
          var nextType = input.getAttribute('type') === 'password' ? 'text' : 'password';
          input.setAttribute('type', nextType);
          var visible = nextType === 'text';
          btn.setAttribute('aria-pressed', visible ? 'true' : 'false');
          var label = visible ? 'Sembunyikan password' : 'Tampilkan password';
          btn.setAttribute('aria-label', label);
          btn.setAttribute('title', label);
          var openIcon = btn.querySelector('[data-eye="open"]');
          var closedIcon = btn.querySelector('[data-eye="closed"]');
          if (openIcon) openIcon.classList.toggle('hidden', !visible);
          if (closedIcon) closedIcon.classList.toggle('hidden', visible);
        });
      });
    })();
  </script>

  <script>
    (function() {
      var selectRoot = document.getElementById('unitSelect');
      var input = document.getElementById('unit');
      var button = document.getElementById('unitButton');
      var buttonLabel = document.getElementById('unitButtonLabel');
      var listbox = document.getElementById('unitListbox');
      var clientError = document.getElementById('unitClientError');
      if (!selectRoot || !input || !button || !buttonLabel || !listbox) return;

      var options = Array.prototype.slice.call(selectRoot.querySelectorAll('[data-unit-option]'));

      function setOpen(nextOpen) {
        selectRoot.setAttribute('data-open', nextOpen ? 'true' : 'false');
        button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
        if (!nextOpen) {
          listbox.setAttribute('tabindex', '-1');
        }
      }

      function isOpen() {
        return selectRoot.getAttribute('data-open') === 'true';
      }

      function setSelectedValue(value) {
        input.value = value || '';
        var hasValue = Boolean(value);
        buttonLabel.textContent = hasValue ? value : 'Pilih Unit';
        buttonLabel.classList.toggle('text-slate-500', !hasValue);
        buttonLabel.classList.toggle('dark:text-slate-400', !hasValue);
        options.forEach(function(optionButton) {
          var selected = optionButton.getAttribute('data-value') === (value || '');
          optionButton.setAttribute('aria-selected', selected ? 'true' : 'false');
          optionButton.classList.toggle('bg-slate-100', selected);
          optionButton.classList.toggle('dark:bg-slate-900/60', selected);
        });
        if (clientError) clientError.classList.add('hidden');
      }

      function focusSelectedOption() {
        var selected = options.find(function(optionButton) {
          return optionButton.getAttribute('data-value') === (input.value || '');
        }) || options[0];
        if (selected) selected.focus();
      }

      function openMenu() {
        if (isOpen()) return;
        setOpen(true);
        requestAnimationFrame(function() {
          focusSelectedOption();
        });
      }

      function closeMenu() {
        if (!isOpen()) return;
        setOpen(false);
      }

      function toggleMenu() {
        if (isOpen()) closeMenu();
        else openMenu();
      }

      button.addEventListener('click', function() {
        toggleMenu();
      });

      button.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
          e.preventDefault();
          openMenu();
        }
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggleMenu();
        }
      });

      options.forEach(function(optionButton, index) {
        optionButton.addEventListener('click', function() {
          setSelectedValue(optionButton.getAttribute('data-value') || '');
          closeMenu();
          button.focus();
        });

        optionButton.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            e.preventDefault();
            closeMenu();
            button.focus();
            return;
          }
          if (e.key === 'ArrowDown') {
            e.preventDefault();
            var next = options[index + 1] || options[0];
            next.focus();
            return;
          }
          if (e.key === 'ArrowUp') {
            e.preventDefault();
            var prev = options[index - 1] || options[options.length - 1];
            prev.focus();
            return;
          }
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            optionButton.click();
          }
        });
      });

      document.addEventListener('click', function(e) {
        if (!isOpen()) return;
        if (selectRoot.contains(e.target)) return;
        closeMenu();
      });

      document.addEventListener('keydown', function(e) {
        if (!isOpen()) return;
        if (e.key === 'Escape') {
          e.preventDefault();
          closeMenu();
          button.focus();
        }
      });

      if (input.value) {
        setSelectedValue(input.value);
      } else {
        setSelectedValue('');
      }
    })();
  </script>

  <script>
    (function() {
      var lockedDomain = '@kftd.co.id';
      var form = document.getElementById('register-form');
      if (!form) return;
      var localInput = document.getElementById('email_local');
      var hiddenInput = document.getElementById('email');
      if (!localInput || !hiddenInput) return;

      function normalizeLocal(value) {
        var v = String(value || '').trim().toLowerCase();
        if (!v) return '';
        if (v.includes('@')) v = v.split('@')[0];
        v = v.replace(/\s+/g, '');
        return v;
      }

      function syncHidden() {
        var local = normalizeLocal(localInput.value);
        localInput.value = local;
        hiddenInput.value = local ? (local + lockedDomain) : '';
      }

      localInput.addEventListener('input', syncHidden);
      localInput.addEventListener('blur', syncHidden);
      form.addEventListener('submit', function() {
        syncHidden();
      });

      syncHidden();
    })();
  </script>

  <script>
    (function() {
      var form = document.getElementById('register-form');
      if (!form) return;
      var overlay = document.getElementById('registerOverlay');
      var submitted = false;
      form.addEventListener('submit', function(e) {
        var unitInput = document.getElementById('unit');
        var unitClientError = document.getElementById('unitClientError');
        var unitButton = document.getElementById('unitButton');
        if (unitInput && !unitInput.value) {
          e.preventDefault();
          if (unitClientError) {
            unitClientError.textContent = 'Unit wajib dipilih.';
            unitClientError.classList.remove('hidden');
          }
          if (unitButton) unitButton.focus();
          return;
        }
        if (submitted) return;
        submitted = true;
        try {
          var btn = form.querySelector('[type="submit"]');
          if (btn) {
            btn.disabled = true;
            btn.style.opacity = '.7';
            btn.setAttribute('aria-busy', 'true');
            var label = btn.querySelector('span');
            if (label) {
              label.textContent = 'Mendaftar...';
            } else {
              btn.textContent = 'Mendaftar...';
            }
          }
          if (overlay) {
            overlay.classList.add('show');
          }
        } catch (_) {}
        e.preventDefault();
        var MIN_OVERLAY_TIME = 700;
        requestAnimationFrame(function() {
          requestAnimationFrame(function() {
            setTimeout(function() {
              form.submit();
            }, MIN_OVERLAY_TIME);
          });
        });
      });
    })();
  </script>

  @livewireScripts
</body>

</html>
