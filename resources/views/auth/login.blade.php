@php
$recaptcha = app(\App\Services\Security\RecaptchaVerifier::class);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Login &mdash; TICKORA</title>
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
      <div class="w-full max-w-md">
        <div class="mb-7 flex flex-col items-center text-center">
          <div class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/75 ring-1 ring-slate-200/70 shadow-sm shadow-blue-200/50 backdrop-blur dark:bg-slate-900/70 dark:ring-slate-800/70 dark:shadow-none">
            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M7 7.5V6.75C7 5.231 8.231 4 9.75 4h4.5C15.769 4 17 5.231 17 6.75V7.5" stroke="var(--brand-blue)" stroke-width="1.8" stroke-linecap="round" />
              <path d="M6.25 7.5h11.5c.966 0 1.75.784 1.75 1.75v8.25A2.5 2.5 0 0 1 17 20H7a2.5 2.5 0 0 1-2.5-2.5V9.25c0-.966.784-1.75 1.75-1.75Z" stroke="var(--brand-blue)" stroke-width="1.8" />
              <path d="M8 12h8M8 15h5" stroke="var(--brand-orange)" stroke-width="1.8" stroke-linecap="round" />
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
            <h2 class="text-2xl font-semibold tracking-tight">Masuk ke akun Anda</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">Kelola ticket, task, dan project dalam satu dashboard.</p>
          </div>

          @if (session('status'))
          <div class="mb-5 rounded-2xl border border-amber-200/70 bg-amber-50/80 px-4 py-3 text-sm font-medium text-amber-900 ring-1 ring-amber-200/60 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
            {{ session('status') }}
          </div>
          @endif

          @php
            $currentLocale = app()->getLocale() ?? config('app.locale', 'en');
          @endphp
          <form id="login-form" method="POST" action="{{ route('login.store', ['locale' => $currentLocale]) }}" class="space-y-5">
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
              <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Email</label>
              <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8" />
                    <path d="m5.5 7 6.1 4.07a1 1 0 0 0 1.1 0L18.8 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>
                <input id="email" type="hidden" name="email" value="{{ ($oldEmailLocal ?? '') ? (($oldEmailLocal ?? '') . ($lockedDomain ?? '@kftd.co.id')) : '' }}" />
                <input id="email_local" type="text" inputmode="email" autocapitalize="none" spellcheck="false" value="{{ $oldEmailLocal ?? '' }}" required autofocus autocomplete="username"
                  aria-label="Email (tanpa domain)"
                  class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 pr-28 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none" />
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-semibold tracking-wide text-slate-500 dark:text-slate-300">{{ $lockedDomain ?? '@kftd.co.id' }}</span>
              </div>
              @error('email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1">
              <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Password</label>
              <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 10V8.2a5 5 0 0 1 10 0V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    <path d="M6.5 10h11A2.5 2.5 0 0 1 20 12.5v5A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-5A2.5 2.5 0 0 1 6.5 10Z" stroke="currentColor" stroke-width="1.8" />
                  </svg>
                </span>
                <input id="password" type="password" name="password" required autocomplete="current-password"
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

            @if ($recaptcha->isEnabled())
            <div class="space-y-2">
              <div class="flex justify-center">
                <div class="g-recaptcha" data-sitekey="{{ $recaptcha->siteKey() }}"></div>
              </div>
              @error('g-recaptcha-response') <p class="text-center text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            @endif

            <button type="submit"
              class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 focus-visible:ring-offset-2 focus-visible:ring-offset-white disabled:opacity-70 dark:focus-visible:ring-offset-slate-900">
              <span class="!text-white">Login</span>
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M10 17l5-5-5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M15 12H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
          </form>

          <div class="mt-6 flex flex-col items-center gap-3 text-center text-sm text-slate-600 dark:text-slate-300">
            <a wire:navigate.hover href="{{ route('welcome', ['locale' => $currentLocale]) }}" class="font-medium text-slate-700 underline-offset-4 hover:underline dark:text-slate-200">Kembali ke halaman welcome</a>
            <p>
              Belum punya akun?
              <a wire:navigate.hover href="{{ route('register', ['locale' => $currentLocale]) }}" class="font-semibold text-blue-700 underline-offset-4 hover:underline dark:text-blue-300">Daftar di sini</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    :root {
      --lo-primary: #4f46e5;
      --lo-text: #0f172a;
      --lo-muted: #475569;
    }

    #loginOverlay {
      position: fixed;
      inset: 0;
      display: grid;
      place-items: center;
      background: rgba(15, 23, 42, 0.35);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      z-index: 1050;
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: opacity .2s ease, visibility .2s ease;
    }

    #loginOverlay.show {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
    }

    #loginOverlay .panel {
      min-width: 260px;
      min-height: 170px;
      padding: 22px;
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.85);
      box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
      text-align: center;
      border: 1px solid rgba(79, 70, 229, 0.12);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }

    #loginOverlay .loader {
      width: 72px;
      height: 72px;
      margin: 0;
      position: relative;
    }

    #loginOverlay .loader .ring {
      position: absolute;
      inset: 0;
      border-radius: 50%;
      border: 4px solid rgba(79, 70, 229, 0.18);
      border-top-color: #4f46e5;
      border-right-color: #38bdf8;
      animation: overlay-spin 0.85s linear infinite;
      transform-origin: 50% 50%;
    }

    #loginOverlay .title {
      margin: 0;
      font-size: 15px;
      font-weight: 700;
      color: var(--lo-text);
      letter-spacing: 0.1px;
      width: 100%;
    }

    #loginOverlay .subtitle {
      margin: 4px 0 0;
      font-size: 13px;
      color: var(--lo-muted);
    }

    @keyframes overlay-spin {
      to {
        transform: rotate(360deg);
      }
    }
  </style>

  <div id="loginOverlay" aria-hidden="true">
    <div class="panel">
      <div class="loader" aria-hidden="true"><span class="ring"></span></div>
      <p class="title">Sedang masuk…</p>
      <p class="subtitle">Membuka Dashboard Anda.</p>
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
      var lockedDomain = '@kftd.co.id';
      var form = document.getElementById('login-form');
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
      var form = document.getElementById('login-form');
      if (!form) return;
      var overlay = document.getElementById('loginOverlay');
      var submitted = false;
      form.addEventListener('submit', function(e) {
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
              label.textContent = 'Masuk...';
            } else {
              btn.textContent = 'Masuk...';
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
