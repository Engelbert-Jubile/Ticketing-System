<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}"
  x-data="layout()"
  class="h-full">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title','Ticketing System')</title>

  {{-- Custom Header Styles --}}
  <link rel="stylesheet" href="{{ asset('css/header.css') }}">
  <link rel="stylesheet" href="{{ asset('css/header-fixes.css') }}">
  @stack('styles')

  {{-- LIVEWIRE x ALPINE HOOK (tambahan agar tak dobel Alpine) --}}
  <script>
    window.deferLoadingAlpine = function(callback) {
      window.addEventListener('alpine:initialized', callback);
    };
  </script>

  {{-- THEME BOOT --}}
  <script>
    (function() {
      try {
        document.documentElement.classList.add('booting');
        if (localStorage.getItem('darkMode') !== null && localStorage.getItem('theme') === null) {
          const legacyDark = localStorage.getItem('darkMode') === 'true';
          localStorage.setItem('theme', legacyDark ? 'dark' : 'light');
          localStorage.removeItem('darkMode');
        }
        let stored = localStorage.getItem('theme');
        if (!stored) {
          stored = 'light';
          localStorage.setItem('theme', 'light');
        }
        document.documentElement.classList.toggle('dark', stored === 'dark');
      } catch (_) {}
    })();
  </script>

  {{-- PRE-PAINT --}}
  <script>
    (function() {
      try {
        var forceClosed = localStorage.getItem('sb:forceClosed') === '1';
        var stored = localStorage.getItem('sidebarOpen');
        var mq = window.matchMedia('(min-width: 1024px)');
        var isLocked = localStorage.getItem('sidebarLocked') === 'true';
        var isOpen = stored !== null ? stored === 'true' : false;
        var rootStyles = getComputedStyle(document.documentElement);
        var maxW = rootStyles.getPropertyValue('--sb-max').trim() || '18rem';
        var miniW = rootStyles.getPropertyValue('--sb-mini').trim() || '4.75rem';
        var initialSpace = '0px';

        if (forceClosed) {
          isLocked = false;
          isOpen = false;
          localStorage.setItem('sidebarOpen', 'false');
          localStorage.setItem('sidebarLocked', 'false');
        }

        if (mq.matches) {
          if (localStorage.getItem('sidebarOpen') === null) {
            isOpen = false;
            localStorage.setItem('sidebarOpen', 'false');
          }
          if (!forceClosed && isLocked) {
            isOpen = true;
            localStorage.setItem('sidebarOpen', 'true');
          }
          initialSpace = isOpen ? maxW : miniW;
        } else {
          initialSpace = '0px';
        }
        document.documentElement.style.setProperty('--app-sidebar-space', initialSpace);
        document.documentElement.style.setProperty('--sb-pref-w', (mq.matches ? (isOpen ? maxW : miniW) : maxW));
      } catch (e) {}
    })();
  </script>

  <style>
    @media (prefers-reduced-motion: reduce) {
      * {
        animation: none !important;
        transition: none !important;
        scroll-behavior: auto !important
      }
    }

    :root {
      --topbar-h: 72px;
      --ease-smooth: cubic-bezier(.22, .61, .36, 1);
      --ease-bouncy: cubic-bezier(.34, 1.56, .64, 1);
      --sb-mini: 4.75rem;
      --app-sidebar-space: 0px;
      --sb-max: 18rem
    }

    .sb-spacer {
      transition: width .38s var(--ease-smooth)
    }

    [x-cloak] {
      display: none !important
    }

    .booting .sidebar,
    .booting .sb-spacer,
    .booting main {
      transition: none !important
    }

    @media (min-width:1024px) {
      .sb-pref-open .sb-spacer {
        width: var(--sb-max)
      }
    }

    .topbar {
      position: fixed;
      inset: 0 0 auto 0;
      width: 100%;
      background: linear-gradient(120deg, #2563eb 0%, #4338ca 45%, #1d4ed8 100%);
      color: #f8fafc;
      box-shadow: 0 20px 45px -28px rgba(30, 64, 175, .58);
      /* biarkan blur global bar tetap ada; yang kita hilangkan adalah blur pada icon/overlay */
      backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(255, 255, 255, .12);
      z-index: 40;
    }

    .dark .topbar {
      background: linear-gradient(120deg, rgba(15, 23, 42, .96) 0%, rgba(30, 41, 59, .92) 60%, rgba(30, 64, 175, .85) 100%);
      border-bottom-color: rgba(148, 163, 184, .18);
      box-shadow: 0 20px 45px -32px rgba(15, 23, 42, .9)
    }

    .topbar__inner {
      height: var(--topbar-h);
      margin-inline: auto;
      /* container dibuat sedikit lebih lebar & margin samping diperkecil agar brand lebih “nempel” kiri */
      width: min(1360px, 100% - 0.75rem);
      display: flex;
      align-items: center;
      gap: 1.5rem;
      flex-wrap: nowrap
    }

    .topbar__left,
    .topbar__right {
      display: flex;
      align-items: center;
      gap: 1rem;
      min-width: 0
    }

    .topbar__left {
      justify-content: flex-start;
      margin-left: 0.125rem
    }

    /* geser sedikit ke kiri */
    .topbar__right {
      margin-left: auto;
      gap: .9rem;
      align-items: center;
      flex: 0 0 auto;
      flex-wrap: nowrap;
      white-space: nowrap;
      min-width: max-content
    }

    .topbar__brand {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      color: inherit;
      font-weight: 900;
      font-size: 1.28rem;
      letter-spacing: -.02em
    }

    /* ikon “filled” agar lebih modern */
    .topbar__logo {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 26px;
      height: 26px;
      color: #fff;
      background: none !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      transition: transform .18s ease;
    }

    .topbar__logo i {
      font-size: 24px;
      line-height: 1
    }

    .topbar__logo:hover {
      transform: scale(1.06)
    }

    .dark .topbar__logo {
      color: #e6edff
    }

    /* teks brand digeser sedikit ke kiri seperti panah */
    .topbar__brand-text {
      margin-left: -0.55rem;
      white-space: nowrap
    }

    .topbar__search {
      flex: 1 1 auto;
      display: flex;
      justify-content: center;
      min-width: 0
    }

    .topbar-search {
      position: relative;
      width: 100%;
      max-width: 520px
    }

    .topbar-search input[type="search"] {
      width: 100%;
      border-radius: 999px;
      padding: .76rem 1rem .76rem 2.75rem;
      background: rgba(255, 255, 255, .12);
      border: 1px solid rgba(255, 255, 255, .18);
      color: inherit;
      font-size: .95rem;
      line-height: 1.35;
      transition: background-color .25s ease, border-color .25s ease, box-shadow .25s ease
    }

    .topbar-search input[type="search"]::placeholder {
      color: rgba(226, 232, 240, .7)
    }

    .topbar-search input[type="search"]:focus {
      outline: none;
      background: rgba(255, 255, 255, .18);
      border-color: rgba(255, 255, 255, .42);
      box-shadow: 0 0 0 3px rgba(255, 255, 255, .22)
    }

    .topbar-search__icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: rgba(226, 232, 240, .86);
      pointer-events: none;
      height: 20px;
    }

    .topbar-icon-btn {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 8px;
      background: transparent;
      color: inherit;
      border: 1px solid transparent;
      transition: all .2s ease;
      -webkit-tap-highlight-color: transparent;
      backdrop-filter: none;
    }

    .topbar-icon-btn:hover {
      background: rgba(255, 255, 255, .1);
      transform: translateY(-1px);
    }

    .dark .topbar-icon-btn:hover {
      background: rgba(255, 255, 255, .05);
    }

    .topbar-icon-btn.is-active {
      background: rgba(255, 255, 255, .15);
      border-color: rgba(255, 255, 255, .55);
      transform: translateY(0);
      color: #fff;
    }

    .dark .topbar-icon-btn.is-active {
      border-color: rgba(148, 163, 184, .55);
      color: #e0f2fe;
    }

    .topbar__welcome {
      display: none
    }

    @media (min-width:768px) {
      .topbar__welcome {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: center;
        gap: .15rem
      }
    }

    .topbar__welcome .welcome {
      font-size: .65rem;
      text-transform: uppercase;
      letter-spacing: .08em;
      opacity: .75
    }

    .topbar__welcome .name {
      font-weight: 600;
      font-size: .9rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis
    }

    .topbar__actions {
      display: flex !important;
      flex-direction: row !important;
      align-items: center !important;
      gap: .65rem;
      flex-wrap: nowrap !important;
      white-space: nowrap
    }

    .topbar__badge {
      position: absolute;
      top: -.35rem;
      right: -.1rem;
      min-width: 1.1rem;
      height: 1.1rem;
      padding-inline: .15rem;
      border-radius: 999px;
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: #fff;
      font-size: .58rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0 0 2px rgba(37, 99, 235, .75)
    }

    .dark .topbar__badge {
      box-shadow: 0 0 0 2px rgba(15, 23, 42, .9)
    }

    /* Dropdown clean: tanp blur */
    .topbar-dropdown {
      background: #ffffff !important;
      box-shadow: 0 28px 60px -28px rgba(15, 23, 42, .45) !important;
      z-index: 9010 !important
    }

    .dark .topbar-dropdown {
      background: #0f172a !important;
      box-shadow: 0 28px 60px -28px rgba(0, 0, 0, .85) !important
    }

    .dropdown-backdrop {
      position: fixed;
      inset: 0;
      z-index: 9000;
      background: rgba(2, 6, 23, .22);
      backdrop-filter: none;
      /* pastikan tidak blur */
    }

    .dark .dropdown-backdrop {
      background: rgba(0, 0, 0, .28)
    }

    @media (max-width:1023px) {
      .topbar__inner {
        width: min(100%, 100% - 1.5rem);
        padding-inline: 0
      }

      .topbar__brand-text {
        display: none
      }

      .topbar__search {
        display: none
      }

      .topbar__mobile-menu-btn {
        display: inline-flex !important
      }
    }

    .topbar__mobile-menu-btn {
      width: 2.75rem;
      height: 2.75rem;
      border-radius: 10px;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, .1);
      color: #fff;
      transition: background-color .2s ease
    }

    .topbar__mobile-menu-btn:hover {
      background: rgba(255, 255, 255, .2)
    }
  </style>

  @vite(['resources/css/app.css','resources/css/animations.css','resources/js/app.js','resources/js/legacy-entry.js'])
  @livewireStyles
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('fonts/remixicon/remixicon.css') }}">
  <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.7/quill.min.js" defer></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
  @stack('styles')
</head>

<body class="flex h-screen bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 overflow-x-hidden">
  <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:m-2 focus:p-2 focus:bg-yellow-300 focus:text-black rounded">Loncat ke konten</a>

  {{-- === Sidebar (satu-satunya) === --}}
  @include('layouts.sidebar')

  {{-- Root Alpine helpers for layout --}}
  {{-- layout() disediakan oleh legacy-entry.js (Alpine helper lama). --}}


  {{-- Initialize sidebar smoothly --}}
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      requestAnimationFrame(() => {
        document.querySelector('.sidebar')?.classList.add('initialized');
      });
    });
  </script>

  <nav class="topbar" role="banner"
    @sb:sync.window="if($event?.detail){ if(typeof $event.detail.locked!=='undefined') sidebarLocked=$event.detail.locked; if(typeof $event.detail.open!=='undefined') sidebarOpen=$event.detail.open; setAppSpace(); }">
    <div id="appTopbar" class="topbar__inner">
      <div class="topbar__left">
        <button type="button"
          class="topbar__mobile-menu-btn topbar-icon-btn sb-ripple lg:hidden"
          @click="ripple($event); onTopbarButton()"
          :aria-label="sidebarOpen ? 'Tutup menu' : 'Buka menu'"
          :aria-pressed="sidebarOpen.toString()">
          <span class="material-icons text-[22px]">menu</span>
        </button>

        <a href="{{ route('dashboard') }}" class="topbar__brand">
          <span class="topbar__logo" aria-hidden="true">
            <i class="material-icons">support</i>
          </span>
          <span class="topbar__brand-text">Ticketing System</span>
        </a>
      </div>

      <div class="topbar__search">
        <form action="{{ route('search') }}" method="GET" class="topbar-search" role="search" style="position:relative;display:flex;align-items:center;max-width:520px;width:100%;">
          <span class="material-icons topbar-search__icon" style="position:absolute;left:1.25rem;top:50%;transform:translateY(-50%);font-size:1.25rem;display:flex;align-items:center;justify-content:center;">search</span>
          <input name="query" value="{{ request('query') }}" type="search" placeholder="Cari ticket, task, project..." aria-label="Cari ticket, task, project" autocomplete="off" style="width:100%;border-radius:999px;padding:.76rem 1.1rem .76rem 2.9rem;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);color:inherit;font-size:.95rem;line-height:1.35;transition:background-color .25s ease,border-color .25s ease,box-shadow .25s ease;">
        </form>
      </div>

      @auth
      <div class="topbar__right">
        <div class="topbar__welcome text-right">
          <span class="welcome">Selamat datang</span>
          <span class="name">{{ auth()->user()->display_name }}</span>
        </div>

        <div class="topbar__actions">
          <div x-data="{ open: false }" class="relative" @click.away="open = false">
            @php
            $notifCount = auth()->user()->unreadNotifications()->count();
            $notifLabel = $notifCount > 99 ? '99+' : (string) $notifCount;
            @endphp
            <button type="button"
              @click="open = !open"
              class="header-action"
              :class="{ 'is-active': open }"
              :aria-expanded="open.toString()"
              aria-label="Notifikasi ({{ $notifCount }})"
              title="Notifikasi ({{ $notifCount }})">
              <i class="material-icons">notifications</i>
              @if($notifCount > 0)
              <span class="topbar__badge">{{ $notifLabel }}</span>
              @endif
            </button>
            <template x-if="open">
              <div x-cloak x-transition.opacity class="dropdown-backdrop" @click="open=false"></div>
            </template>
            <div x-cloak x-show="open" x-transition.opacity.scale.90
              class="topbar-dropdown fixed right-6 top-[calc(var(--topbar-h)+8px)]
                w-80 overflow-hidden rounded-2xl bg-white text-slate-800
                shadow-none ring-1 ring-slate-200 z-[100000]
                dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700">
              <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold uppercase tracking-wide dark:border-slate-700 dark:bg-slate-800">
                <span>Notifications</span>
                @if($notifCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                  @csrf
                  <button type="submit" class="text-xs font-semibold text-blue-600 transition hover:text-blue-500 dark:text-blue-300">Tandai semua</button>
                </form>
                @endif
              </div>
              @php
                $notifs = auth()->user()->notifications()->latest()->limit(15)->get();
              @endphp
              @if($notifs->isEmpty())
              <div class="px-5 py-6 text-center text-sm text-slate-500 dark:text-slate-300">Tidak ada notifikasi baru.</div>
              @else
              <ul class="max-h-96 overflow-auto divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($notifs as $n)
                @php
                  $unread = is_null($n->read_at);
                @endphp
                <li class="bg-white px-4 py-3 transition hover:bg-blue-50 dark:bg-slate-800 dark:hover:bg-slate-700">
                  <div class="flex items-start gap-3">
                    <span class="material-icons text-[22px] {{ $unread ? 'text-blue-500' : 'text-slate-400 dark:text-slate-500' }}">{{ $n->data['icon'] ?? 'notifications' }}</span>
                    <div class="flex-1 min-w-0 space-y-1">
                      <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $n->data['title'] ?? 'Activity' }}</span>
                        @if($unread)<span class="inline-flex h-2 w-2 rounded-full bg-blue-500"></span>@endif
                      </div>
                      <p class="text-xs text-slate-600 dark:text-slate-300 truncate">{{ $n->data['message'] ?? '' }}</p>
                      <p class="text-[11px] uppercase tracking-wide text-slate-400">{{ optional($n->created_at)->diffForHumans() }}</p>
                      @if(!empty($n->data['url']))<a href="{{ $n->data['url'] }}" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-300">Buka</a>@endif
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                      @if($unread)
                      <form method="POST" action="{{ route('notifications.mark', $n->id) }}">@csrf
                        <button title="Tandai sudah dibaca" class="topbar-icon-btn h-8 w-8 rounded-[8px] bg-blue-500/10 text-blue-600 hover:bg-blue-500/20 dark:bg-blue-400/15 dark:text-blue-200">
                          <span class="material-icons text-[18px]">check</span>
                        </button>
                      </form>
                      @endif
                      <form method="POST" action="{{ route('notifications.destroy', $n->id) }}">@csrf @method('DELETE')
                        <button title="Hapus" class="topbar-icon-btn h-8 w-8 rounded-[8px] bg-red-500/10 text-red-600 hover:bg-red-500/20 dark:bg-red-400/15 dark:text-red-300">
                          <span class="material-icons text-[18px]">close</span>
                        </button>
                      </form>
                    </div>
                  </div>
                </li>
                @endforeach
              </ul>
              @endif
            </div>
          </div>

          <div x-data="{ open: false }" class="relative" @click.away="open = false">
            <button type="button"
              @click="open = !open"
              class="topbar-icon-btn topbar-user-btn focus:outline-none"
              style="backdrop-filter:none;box-shadow:none;background:transparent;"
              :class="open ? 'is-active' : ''"
              :aria-expanded="open.toString()"
              aria-label="Pengaturan akun"
              title="Pengaturan akun">
              <span class="material-icons text-[22px]">account_circle</span>
            </button>
            <template x-if="open">
              <div x-cloak x-transition.opacity class="dropdown-backdrop" @click="open=false"></div>
            </template>
            <div x-cloak x-show="open" x-transition.opacity.scale.90
              class="topbar-dropdown fixed right-6 top-[calc(var(--topbar-h)+8px)]
                w-56 overflow-hidden rounded-2xl bg-white text-slate-800
                shadow-none ring-1 ring-slate-200 z-[100000]
                dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700">
              <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-500 uppercase tracking-wide dark:border-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800">Akun</div>
              <a href="{{ route('account.profile') }}" class="block px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-blue-50 hover:text-blue-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white">Profile</a>
              <a href="{{ route('account.change-password') }}" class="block px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-blue-50 hover:text-blue-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white">Change Password</a>
              <div class="border-t border-slate-200 dark:border-slate-700"></div>
              <form method="POST" action="{{ route('logout') }}" class="px-2 py-2">@csrf
                <button type="submit" class="w-full rounded-xl bg-red-500/10 px-4 py-2 text-left text-sm font-semibold text-red-600 transition hover:bg-red-500/20 dark:bg-red-400/10 dark:text-red-300 dark:hover:bg-red-400/20">Logout</button>
              </form>
            </div>
          </div>
        </div>
      </div>
      @endauth

    </div>
  </nav>

  {{-- Wrapper --}}
  <div class="flex-1 flex pt-[var(--topbar-h)]">
    {{-- Overlay mobile --}}
    <div x-show="!isDesktop && sidebarOpen" x-transition @click="toggleSidebar()" class="sidebar-overlay fixed inset-0 bg-black/50 z-30 sm:hidden"></div>

    {{-- Hover sentinel --}}
    <div class="fixed top-[var(--topbar-h)] left-0 bottom-0 z-30 hidden sm:block w-2" @mouseenter="onEdgeEnter()" @mouseleave="onEdgeLeave()"></div>

    {{-- Spacer --}}
    <div class="sb-spacer hidden lg:block shrink-0" style="width: var(--app-sidebar-space, 0px);"></div>

    {{-- Main content (scroll only this area) --}}
    <main id="main" class="flex-1 p-6 h-[calc(100vh-var(--topbar-h))] overflow-y-auto">
      @yield('content')
    </main>
  </div>

  @livewireScripts
  @stack('scripts')
  <script>
    // Remove booting class when ready
    (function() {
      var done = false;

      function clearBoot() {
        if (done) return;
        done = true;
        document.documentElement.classList.remove('booting');
      }
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', clearBoot, {
          once: true
        });
      } else {
        clearBoot();
      }
      document.addEventListener('livewire:navigated', clearBoot);
    })();
  </script>
</body>

</html>
