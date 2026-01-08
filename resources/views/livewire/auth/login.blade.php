{{-- resources/views/auth/login.blade.php --}}
<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
  <div class="w-full max-w-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-6 text-center">Login</h1>

@php
  $currentLocale = app()->getLocale() ?? config('app.locale', 'en');
@endphp
<form id="login-form" method="POST" action="{{ route('login.store', ['locale' => $currentLocale]) }}" class="space-y-4">
      @csrf
      {{-- Email --}}
      <div>
        <label for="email" class="block mb-1 font-medium text-gray-700 dark:text-gray-300">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
               class="w-full px-3 py-2 border rounded
                      bg-white dark:bg-gray-700
                      border-gray-300 dark:border-gray-600
                      text-gray-900 dark:text-gray-100
                      focus:outline-none focus:ring-2 focus:ring-blue-500" />
        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
      </div>

      {{-- Password --}}
      <div>
        <label for="password" class="block mb-1 font-medium text-gray-700 dark:text-gray-300">Password</label>
        <input id="password" type="password" name="password" required
               class="w-full px-3 py-2 border rounded
                      bg-white dark:bg-gray-700
                      border-gray-300 dark:border-gray-600
                      text-gray-900 dark:text-gray-100
                      focus:outline-none focus:ring-2 focus:ring-blue-500" />
        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
      </div>

      {{-- Submit --}}
      <button type="submit"
              class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition">
        Login
      </button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-700 dark:text-gray-300">
      Belum punya akun?
      <a href="{{ route('register', ['locale' => $currentLocale]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
        Daftar di sini
      </a>
    </p>
    <!-- Loading overlay (ke dalam root agar Livewire tetap 1 root element) -->
    <style>
      :root{--lo-primary:#1d4ed8;--lo-text:#334155;--lo-text-strong:#0f172a;--lo-bg-surface:rgba(17,24,39,.58)}
      @media (prefers-color-scheme: dark){:root{--lo-primary:#60a5fa;--lo-text:#cbd5e1;--lo-text-strong:#e2e8f0;--lo-bg-surface:rgba(2,6,23,.72)}}
      #loginOverlay{position:fixed;inset:0;background:radial-gradient(ellipse at center, rgba(255,255,255,.10) 0%, rgba(255,255,255,0) 42%) , var(--lo-bg-surface);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);z-index:1050;opacity:0;visibility:hidden;transition:opacity .18s ease,visibility .18s ease;pointer-events:none}
      #loginOverlay.show{opacity:1;visibility:visible;pointer-events:auto}
      #loginOverlay .wrap{height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column}
      /* Size-only for SVG spinner; add drop-shadow for clarity */
      #loginOverlay .spinner-svg{width:64px;height:64px;display:block;filter:drop-shadow(0 2px 8px rgba(0,0,0,.28))}
      #loginOverlay .msg{margin-top:14px;color:var(--lo-text-strong);font-weight:600;font-size:14px;letter-spacing:.2px;text-shadow:0 1px 2px rgba(0,0,0,.18)}
    </style>
    <div id="loginOverlay" aria-hidden="true">
      <div class="wrap">
        <!-- SVG spinner: reliable rotation via animateTransform -->
        <svg class="spinner-svg" width="60" height="60" viewBox="0 0 50 50" aria-label="Loading" role="status">
          <defs>
            <linearGradient id="lo-g" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#93c5fd" stop-opacity="0.22"/>
              <stop offset="50%" stop-color="#1d4ed8" stop-opacity="1"/>
              <stop offset="100%" stop-color="#93c5fd" stop-opacity="0.22"/>
            </linearGradient>
          </defs>
          <circle cx="25" cy="25" r="20" fill="none" stroke="url(#lo-g)" stroke-width="6" stroke-linecap="round"
                  stroke-dasharray="110" stroke-dashoffset="80">
            <animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.9s" repeatCount="indefinite" />
          </circle>
        </svg>
        <div class="msg">Sedang masuk, mohon tunggu…</div>
      </div>
    </div>
    <script>
      (function(){
        var form = document.getElementById('login-form');
        if (!form) return;
        var overlay = document.getElementById('loginOverlay');
        var submitted = false;
        form.addEventListener('submit', function(e){
          if (submitted) return; // guard
          submitted = true;
          try{
            var btn = form.querySelector('[type="submit"]');
            if (btn){ btn.disabled = true; btn.style.opacity = '.7'; btn.innerHTML = 'Masuk…'; }
            if (overlay){ overlay.classList.add('show'); }
          }catch(_){}
          // Tahan sedikit agar animasi terlihat (smooth, anti-glitch)
          e.preventDefault();
          var MIN_OVERLAY_TIME = 700; // ms
          requestAnimationFrame(function(){
            requestAnimationFrame(function(){
              setTimeout(function(){ form.submit(); }, MIN_OVERLAY_TIME);
            });
          });
        });
      })();
    </script>
  </div>
</div>
