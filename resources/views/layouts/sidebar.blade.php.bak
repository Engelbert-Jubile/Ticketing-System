<nav
  x-data="(function(){
    const mq = window.matchMedia('(min-width: 1024px)');
    const get = (k, d) => { try { const v = localStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch (_) { return d; } };
    const set = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (_) {} };

    // Ensure store exists (fallback if main store init failed)
    if (window.Alpine && !Alpine.store('sb')) {
      const store = window.Alpine.reactive({
        openKey: null,
        set(k){ this.openKey = k; try { if (k === null) localStorage.removeItem('sb:openKey'); else localStorage.setItem('sb:openKey', k); } catch (_) {} },
        toggle(k){ this.set(this.openKey === k ? null : k); },
        closeAll() { this.set(null); }
      });
      Alpine.store('sb', store);
    }

    const emit = (state) => {
      try { window.dispatchEvent(new CustomEvent('sb:sync', { detail: state })); } catch (_) {}
    };

    const shouldForceClosed = (function(){
      try {
        if (!localStorage.getItem('sb:forceClosed')) return false;
        localStorage.removeItem('sb:forceClosed');
        return true;
      } catch (_) {
        return false;
      }
    })();

    return {
      isDesktop: mq.matches,
      sidebarOpen: (function(){
        if (shouldForceClosed) {
          return false;
        }
        const cached = get('sidebarOpen', null);
        if (cached === null) return false;
        return cached;
      })(),
      sidebarLocked: (function(){
        if (shouldForceClosed) return false;
        return get('sidebarLocked', false);
      })(),
      hoverGate: false,

      handleCommand(cmd){
        if (!cmd) return;
        if (cmd === 'toggle-lock') {
          this.toggleLock();
          return;
        }
        if (cmd === 'toggle-sidebar') {
          // Dijalankan hanya di mobile
          if (this.isDesktop) return;
          this.sidebarOpen = !this.sidebarOpen;
          set('sidebarOpen', this.sidebarOpen);
          emit({ locked: this.sidebarLocked, open: this.sidebarOpen });
        }
      },
      ripple(e){
        try{
          const el = e.currentTarget;
          const ink = document.createElement('span');
          ink.className = 'ripple-ink';
          Object.assign(ink.style, { position:'absolute', inset:'0', borderRadius:'inherit', pointerEvents:'none', transform:'translateZ(0)' });
          el.style.position = el.style.position || 'relative';
          el.appendChild(ink);
          setTimeout(() => ink.remove(), 260);
        }catch(_){ }
      },
      toggleLock(){
        this.sidebarLocked = !this.sidebarLocked;
        set('sidebarLocked', this.sidebarLocked);
        if (this.sidebarLocked) {
          this.sidebarOpen = true;
        } else if (this.isDesktop) {
          this.sidebarOpen = false;
        }
        set('sidebarOpen', this.sidebarOpen);
        emit({ locked: this.sidebarLocked, open: this.sidebarOpen });
      },
      toggleMobileSidebar(){
        if (this.isDesktop) return;
        this.sidebarOpen = !this.sidebarOpen;
        set('sidebarOpen', this.sidebarOpen);
        emit({ locked: this.sidebarLocked, open: this.sidebarOpen });
      },
      navTo(url){
        try{
          if (window.Livewire?.navigate) { window.Livewire.navigate(url); }
          else { window.location.href = url; }
        }catch(_){ window.location.href = url; }
      },
      badgePing(){},
      iconAnim(){},
      resetSidebarState(){
        this.sidebarLocked = false;
        this.sidebarOpen = false;
        try {
          set('sidebarOpen', false);
          set('sidebarLocked', false);
          localStorage.removeItem('sb:openKey');
          localStorage.removeItem('sb:selected');
        } catch (_) {}
        try {
          if (window.Alpine && window.Alpine.store('sb')) {
            window.Alpine.store('sb').set(null);
          }
        } catch (_) {}
        emit({ locked: this.sidebarLocked, open: this.sidebarOpen });
      },
      markSidebarForFreshSession(){
        try {
          localStorage.setItem('sb:forceClosed', '1');
          localStorage.setItem('sidebarOpen', 'false');
          localStorage.setItem('sidebarLocked', 'false');
          localStorage.removeItem('sb:openKey');
          localStorage.removeItem('sb:selected');
        } catch (_) {}
      },
      applySidebarFreshState(){
        try {
          if (!window.localStorage?.getItem('sb:forceClosed')) return;
          window.localStorage.removeItem('sb:forceClosed');
        } catch (_) {
          return;
        }
        this.resetSidebarState();
      },
      watchAuthState(){
        if (shouldForceClosed) {
          this.resetSidebarState();
        } else {
          this.applySidebarFreshState();
        }
        const targetAction = @json(route('logout'));
        const normalize = (action) => {
          if (!action) return '';
          try {
            const url = new URL(action, window.location.origin);
            return url.origin + url.pathname;
          } catch (_) {
            return action;
          }
        };
        const targetNormalized = normalize(targetAction);
        const bound = new WeakSet();
        const handler = () => { this.markSidebarForFreshSession(); };
        const bind = (form) => {
          if (!form || bound.has(form)) return;
          bound.add(form);
          form.addEventListener('submit', handler, { passive: true });
          form.addEventListener('click', (ev) => {
            const submitter = ev.target?.closest('button[type="submit"], input[type="submit"]');
            if (submitter) handler();
          }, { passive: true });
        };
        const scan = () => {
          document.querySelectorAll('form').forEach((form) => {
            if (normalize(form.getAttribute('action') || form.action) === targetNormalized) {
              bind(form);
            }
          });
        };
        scan();
        try {
          const observer = new MutationObserver(() => scan());
          observer.observe(document.body || document.documentElement, { childList: true, subtree: true });
        } catch (_) {}
        try {
          const proto = HTMLFormElement.prototype;
          if (!Object.prototype.hasOwnProperty.call(proto, '__sbLogoutSubmitPatched')) {
            const nativeSubmit = proto.submit;
            Object.defineProperty(proto, '__sbLogoutSubmitPatched', {
              value: true,
              writable: false,
              configurable: false
            });
            proto.submit = function(...args){
              try {
                if (normalize(this.getAttribute('action') || this.action) === targetNormalized) {
                  handler();
                }
              } catch (_) {}
              return nativeSubmit.apply(this, args);
            };
          }
        } catch (_) {}
      },
      onSidebarEnter(){
        if (!this.isDesktop) return;
        if (!this.sidebarLocked) {
          this.sidebarOpen = true;
          // Penting: sinkronkan ke layout supaya spacer melebar
          emit({ locked: this.sidebarLocked, open: this.sidebarOpen });
        }
      },
      onSidebarLeave(){
        if (!this.isDesktop) return;
        if (!this.sidebarLocked) {
          this.sidebarOpen = false;
          // Penting: sinkronkan ke layout supaya spacer mengecil
          emit({ locked: this.sidebarLocked, open: this.sidebarOpen });
        }
      },

      init(){
        const handler = (ev) => {
          this.isDesktop = ev.matches;
          if (!this.isDesktop) {
            this.sidebarLocked = false;
            this.sidebarOpen = false;
          } else {
            if (get('sidebarLocked', false)) this.sidebarLocked = true;
            if (!this.sidebarLocked) this.sidebarOpen = false;
            else this.sidebarOpen = true;
            if (get('sidebarOpen', null) === null) this.sidebarOpen = false;
          }
          emit({ locked: this.sidebarLocked, open: this.sidebarOpen });
        };
        mq.addEventListener ? mq.addEventListener('change', handler) : mq.addListener(handler);

        this.$watch('sidebarOpen', v => {
          set('sidebarOpen', v);
          emit({ locked: this.sidebarLocked, open: v });
        });
        this.$watch('sidebarLocked', v => {
          emit({ locked: v, open: this.sidebarOpen });
        });

        emit({ locked: this.sidebarLocked, open: this.sidebarOpen });

        this.watchAuthState();
      }
    };
  })()"
  x-init="init()"
  @sb:command.window="handleCommand($event.detail)"
  @sb:sync.window="if($event?.detail){ if(typeof $event.detail.locked!=='undefined') sidebarLocked=$event.detail.locked; if(typeof $event.detail.open!=='undefined') sidebarOpen=$event.detail.open; }"
  class="sidebar fixed inset-y-0 left-0 z-[999999] bg-white dark:bg-slate-900 overflow-hidden
         transform-gpu will-change-transform flex flex-col
         -translate-x-full lg:translate-x-0"
  style="top: var(--topbar-h);"
  :class="[
    isDesktop
      ? (sidebarOpen ? 'translate-x-0' : 'mini')
      : (sidebarOpen ? 'translate-x-0' : '-translate-x-full'),
    (isDesktop && !sidebarOpen) ? 'mini' : '',
    ((!sidebarOpen && isDesktop && !sidebarLocked) || (hoverGate && !sidebarOpen)) ? 'no-hover' : 'hover-ready'
  ]"
  :style="isDesktop ? {'--sb-w': (sidebarOpen ? 'var(--sb-max)' : 'var(--sb-mini)')} : {'--sb-w':'var(--sb-max)'}"
  aria-label="Sidebar Navigation"
  @mouseenter="onSidebarEnter()"
  @mouseleave="onSidebarLeave()">

  <style>
    .sidebar {
      width: var(--sb-w, var(--sb-pref-w, var(--sb-max)));
      min-width: var(--sb-w, var(--sb-pref-w, var(--sb-max)));
      box-sizing: border-box;
      transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), width 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      will-change: transform;
      contain: size layout style;
      isolation: isolate;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    }
    /* Hide scrollbars within sidebar content, keep scrolling */
    .sidebar .flex-1 {
      -ms-overflow-style: none; /* IE/Edge */
      scrollbar-width: none;    /* Firefox */
    }
    .sidebar .flex-1::-webkit-scrollbar {
      width: 0;
      height: 0;
      display: none;            /* Chrome/Safari */
    }

    .sidebar.mini .menu .nav-li>a,
    .sidebar.mini .menu .nav-li>button,
    .sidebar.mini .menu .nav-li>form>button {
      justify-content: center;
      padding-left: .5rem;
      padding-right: .5rem
    }

    .sidebar .nav-label {
      margin-left: .5rem
    }

    .sidebar.mini .nav-label {
      display: none !important
    }

    .sidebar .nav-li {
      position: relative;
      display: block
    }

    .sidebar .menu>li.nav-li::before,
    .sidebar .menu>li.nav-li::after {
      content: none !important
    }

    .sidebar .nav-li:hover>ul a,
    .sidebar .nav-li:hover>ul .material-icons {
      color: inherit !important
    }

    .sidebar ul ul a:hover {
      background-color: rgb(253 230 138) !important;
      color: black !important
    }

    .dark .sidebar ul ul a:hover {
      background-color: rgb(245 158 11) !important;
      color: white !important
    }

    .sidebar .nav-li>a .material-icons,
    .sidebar .nav-li>button .material-icons,
    .sidebar .nav-li>form>button .material-icons {
      color: currentColor !important
    }

    .sidebar [x-cloak] {
      display: none !important
    }

    .sidebar.no-hover .nav-li>a:hover,
    .sidebar.no-hover .nav-li>button:hover {
      background: transparent !important
    }

    .sidebar.no-hover .menu>li.nav-li:hover,
    .sidebar.no-hover .menu>li.nav-li:focus-within {
      outline: 4px solid rgba(0, 0, 0, .08);
      outline-offset: -4px;
      border-radius: .75rem
    }

    .sidebar.no-hover .nav-li:not(.allow-hover)>a::before,
    .sidebar.no-hover .nav-li:not(.allow-hover)>button::before,
    .sidebar.no-hover .nav-li:not(.allow-hover)>form>button::before {
      width: 0 !important;
      opacity: 0 !important;
      transform: none !important
    }

    .sidebar.no-hover.mini .nav-li>.is-active::before {
      inset: auto;
      left: 50%;
      top: 50%;
      width: var(--mini-badge, 2.75rem) !important;
      height: var(--mini-badge, 2.75rem) !important;
      border-radius: 9999px;
      opacity: 1 !important;
      transform: translate(-50%, -50%) scale(1) !important
    }

    .sidebar.no-hover .menu>li:not(.allow-hover),
    .sidebar.no-hover .menu>li.nav-li--theme {
      pointer-events: none !important
    }

    .sidebar.no-hover .nav-li--theme>*:hover {
      background: transparent !important
    }

    .sidebar.no-hover .nav-li--theme>a::before,
    .sidebar.no-hover .nav-li--theme>button::before,
    .sidebar.no-hover .nav-li--theme>form>button::before {
      width: 0 !important;
      opacity: 0 !important;
      transform: none !important
    }

    .sidebar .nav-li>button[data-sb-key="menu.tasks"],
    .sidebar .nav-li>button[data-sb-key="menu.account"] {
      transition: background-color .18s ease, color .18s ease
    }

    .sidebar .nav-li>button[data-sb-key="menu.tasks"]:hover {
      background-color: rgba(37, 99, 235, .12) !important;
      color: #1e3a8a !important;
    }

    .dark .sidebar .nav-li>button[data-sb-key="menu.tasks"]:hover {
      background-color: rgba(59, 130, 246, .26) !important;
      color: #e0f2fe !important;
    }

    .sidebar .nav-li>button[data-sb-key="menu.account"]:hover {
      background-color: rgba(124, 58, 237, .12) !important;
      color: #3730a3 !important;
    }

    .dark .sidebar .nav-li>button[data-sb-key="menu.account"]:hover {
      background-color: rgba(129, 140, 248, .26) !important;
      color: #ede9fe !important;
    }

    .sidebar .nav-li--logout>form>button {
      transition: background-color .18s ease, color .18s ease
    }
  </style>

  <style>
    .sidebar .nav-li>button[data-sb-key="menu.tasks"]:hover,
    .sidebar .nav-li>button[data-sb-key="menu.tasks"].is-active:hover {
      background-color: rgba(37, 99, 235, .12) !important;
      color: #1e3a8a !important;
    }

    .dark .sidebar .nav-li>button[data-sb-key="menu.tasks"]:hover,
    .dark .sidebar .nav-li>button[data-sb-key="menu.tasks"].is-active:hover {
      background-color: rgba(59, 130, 246, .26) !important;
      color: #e0f2fe !important;
    }

    .sidebar .nav-li>button[data-sb-key="menu.account"]:hover,
    .sidebar .nav-li>button[data-sb-key="menu.account"].is-active:hover {
      background-color: rgba(124, 58, 237, .12) !important;
      color: #3730a3 !important;
    }

    .dark .sidebar .nav-li>button[data-sb-key="menu.account"]:hover,
    .dark .sidebar .nav-li>button[data-sb-key="menu.account"].is-active:hover {
      background-color: rgba(129, 140, 248, .26) !important;
      color: #ede9fe !important;
    }
  </style>

  {{-- ============ HEADER + HAMBURGER LOCK ============ --}}
  <style>
    .hb-btn {
      width: 2.25rem;
      height: 2.25rem;
      border-radius: .5rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      outline: 2px solid transparent;
      outline-offset: 2px
    }

    .hb-svg {
      width: 24px;
      height: 24px;
      display: block
    }

    .hb-line {
      fill: none;
      stroke: currentColor;
      stroke-width: 2.4;
      stroke-linecap: round;
      vector-effect: non-scaling-stroke
    }

    .hb-top,
    .hb-bot {
      transform-origin: 50% 50%
    }

    .hb-mid {
      opacity: 1;
      transform-origin: 50% 50%
    }

    [aria-pressed="true"] .hb-top {
      transform: translateY(6px) rotate(45deg)
    }

    [aria-pressed="true"] .hb-mid {
      opacity: 0;
      transform: scaleX(0)
    }

    [aria-pressed="true"] .hb-bot {
      transform: translateY(-6px) rotate(-45deg)
    }

    .hb-top,
    .hb-mid,
    .hb-bot {
      transition: transform .28s var(--ease-smooth, cubic-bezier(.22, .61, .36, 1)), opacity .2s ease
    }

    .hb-wrap.is-locked {
      background: linear-gradient(135deg, #2563eb, #7c3aed);
      color: #fff;
      box-shadow: 0 12px 32px -18px rgba(79, 70, 229, .65)
    }

    .hb-wrap:not(.is-locked) {
      background: rgba(37, 99, 235, .12);
      color: #1d4ed8;
      box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .18)
    }

    .dark .hb-wrap:not(.is-locked) {
      background: rgba(37, 99, 235, .18);
      color: #dbeafe;
      box-shadow: inset 0 0 0 1px rgba(96, 165, 250, .35)
    }

    .hb-wrap:not(.is-locked):hover {
      background: rgba(37, 99, 235, .18)
    }

    .dark .hb-wrap:not(.is-locked):hover {
      background: rgba(59, 130, 246, .28)
    }

    @media (prefers-reduced-motion:reduce) {

      .hb-top,
      .hb-mid,
      .hb-bot {
        transition: none
      }
    }
  </style>

  <div class="sticky top-0 z-10 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl
              px-3 py-3 flex items-center justify-between border-b border-slate-200/60 dark:border-slate-700/60">
    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300 nav-label"
      x-show="sidebarOpen" x-transition.opacity>Navigation</span>

    <div class="flex items-center gap-1">
      <button type="button"
        class="hb-btn sb-ripple hb-wrap transition-colors"
        :class="isDesktop ? (sidebarLocked ? 'is-locked' : '') : (sidebarOpen ? 'is-locked' : '')"
        :aria-pressed="isDesktop ? sidebarLocked.toString() : sidebarOpen.toString()"
        :title="isDesktop ? (sidebarLocked ? 'Buka kunci sidebar' : 'Kunci sidebar') : (sidebarOpen ? 'Tutup sidebar' : 'Buka sidebar')"
        @click="ripple($event); isDesktop ? toggleLock() : toggleMobileSidebar()">
        <svg class="hb-svg" viewBox="0 0 28 28" aria-hidden="true">
          <path class="hb-line hb-top" d="M6 8.5 H22" />
          <path class="hb-line hb-mid" d="M6 14 H22" />
          <path class="hb-line hb-bot" d="M6 19.5 H22" />
        </svg>
        <span class="sr-only" x-text="isDesktop ? (sidebarLocked ? 'Unlock sidebar' : 'Lock sidebar') : (sidebarOpen ? 'Close sidebar' : 'Open sidebar')"></span>
      </button>
    </div>
  </div>

  <div class="flex-1 overflow-y-auto px-3 py-3">

    @php
    // Fallback: hitung jumlah item "In Progress" untuk badge sidebar
    try {
      $actor = auth()->user();
      $statusScope = array_unique(array_merge(
        \App\Support\WorkflowStatus::equivalents(\App\Support\WorkflowStatus::IN_PROGRESS),
        \App\Support\WorkflowStatus::equivalents(\App\Support\WorkflowStatus::CONFIRMATION),
      ));

      if (!isset($ticketsInProgressCount)) {
        $ticketsInProgressCount = \App\Support\UnitVisibility::scopeTickets(\App\Models\Ticket::query(), $actor)
          ->whereIn('status', $statusScope)
          ->count();
      }

      if (!isset($tasksInProgressCount)) {
        $tasksInProgressCount = \App\Support\UnitVisibility::scopeTasks(\App\Models\Task::query(), $actor)
          ->whereIn('status', $statusScope)
          ->count();
      }

      if (!isset($projectsInProgressCount)) {
        $projectsInProgressCount = \App\Support\UnitVisibility::scopeProjects(\App\Domains\Project\Models\Project::query(), $actor)
          ->whereIn('status', $statusScope)
          ->count();
      }
    } catch (\Throwable $e) { /* ignore count errors */ }
    @endphp

    @php
    $linkBase='block px-4 py-2.5 rounded-xl text-sm font-medium transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400/60';
    $linkHover='hover:bg-blue-50/70 hover:text-blue-700 dark:hover:bg-slate-700/60 dark:hover:text-white';
    $linkActive='bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-500 text-white shadow-lg ring-1 ring-indigo-400/40';
    $btnBase='w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400/60';
    $btnHover='hover:bg-blue-50/70 hover:text-blue-700 dark:hover:bg-slate-700/60 dark:hover:text-white';
    $btnActive='bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-500 text-white shadow-lg ring-1 ring-indigo-400/40';
    @endphp

    @php
    $ticketsReportActive = request()->routeIs(
      'tickets.index',
      'tickets.report',
      'tickets.show',
      'tickets.edit',
      'tickets.status.change',
      'tickets.attachments.manage',
      'tickets.attachments.update'
    );

    $tasksReportActive = request()->routeIs(
      'tasks.index',
      'tasks.report',
      'tasks.show',
      'tasks.view',
      'tasks.edit'
    );

    $projectsReportActive = request()->routeIs(
      'projects.report',
      'projects.show',
      'projects.edit'
    );

    $slaReportActive = request()->routeIs('dashboard.sla');

    $isSuperAdmin = \App\Support\RoleHelpers::userIsSuperAdmin(auth()->user());
    @endphp

    @php
    $currentGroupKey=null;
    if (request()->routeIs('tickets.*')) $currentGroupKey='menu.tickets';
    elseif (request()->routeIs('tasks.*')) $currentGroupKey='menu.tasks';
    elseif (request()->routeIs('projects.*')) $currentGroupKey='menu.projects';
    elseif (request()->routeIs('users.*')) $currentGroupKey='menu.users';
    elseif (request()->routeIs('account.*')) $currentGroupKey='menu.account';
    @endphp

    <ul
      x-data="{
      sel:(function(){try{return localStorage.getItem('sb:selected')||location.href}catch(_){return location.href}})(),
      setSel(v){this.sel=v;try{localStorage.setItem('sb:selected',v)}catch(_){}} ,
      defaultKey:'{{ $currentGroupKey }}',
      clearActives(){
        try {
          document.querySelectorAll('.sidebar .menu .is-active').forEach(el => {
            el.classList.remove('is-active','bg-gradient-to-r','from-blue-600','via-indigo-500','to-purple-500','text-white','ring-1','ring-indigo-400/40','shadow-lg');
          });
        } catch (_) {}
      } ,
      sbSet(key){try{if(window.Alpine&&this.$store&&this.$store.sb){this.$store.sb.set(key)}}catch(_){}},
      sbToggle(key){try{if(window.Alpine&&this.$store&&this.$store.sb){this.$store.sb.toggle(key)}}catch(_){}},
      getStoreOpen(key){try{if(window.Alpine&&this.$store&&this.$store.sb)return this.$store.sb.openKey===key;return false}catch(_){return false}},
      ensureRouteOpenSync(){const key=this.defaultKey||null;if(key&&window.Alpine&&this.$store&&this.$store.sb){this.$store.sb.set(key);try{localStorage.setItem('sb:openKey',key)}catch(_){}}else if(window.Alpine&&this.$store&&this.$store.sb){this.$store.sb.set(null);try{localStorage.removeItem('sb:openKey')}catch(_){}}},
      
    }"
      x-init="
      setSel(location.href); 
      if(window.Alpine && this.$store && this.$store.sb) ensureRouteOpenSync();
      window.addEventListener('inertia:navigate',()=>{setSel(location.href); if(window.Alpine && this.$store && this.$store.sb) ensureRouteOpenSync();},{passive:true});
      window.addEventListener('pageshow',()=>{setSel(location.href); if(window.Alpine && this.$store && this.$store.sb) ensureRouteOpenSync();},{passive:true});
      window.addEventListener('popstate',()=>{setSel(location.href); if(window.Alpine && this.$store && this.$store.sb) ensureRouteOpenSync();},{passive:true});
    "
      class="menu flex flex-col p-4 pb-8 mt-2 space-y-2 text-slate-800 dark:text-slate-100">

      {{-- Dashboard --}}
      @php $dash = route('dashboard'); @endphp
      <li class="nav-li nav-li--dash">
        <a href="{{ $dash }}" id="sb-dash-link"
          @click.prevent="clearActives(); sbSet(null); try{localStorage.removeItem('sb:openKey')}catch(_){}; setSel('{{ $dash }}'); navTo('{{ $dash }}', $event, 'pulse')"
          class="{{ $linkBase }} {{ $linkHover }} {{ request()->routeIs('dashboard') ? $linkActive.' is-active' : '' }} flex items-center sb-ripple"
          :class="sel === '{{ $dash }}' ? '{{ $linkActive }} is-active' : ''"
          @if (request()->routeIs('dashboard')) aria-current="page" @endif>
          <span class="material-icons align-middle text-current">dashboard</span>
          <span class="nav-label align-middle" x-show="sidebarOpen" x-transition.opacity>Dashboard</span>
        </a>
      </li>

      {{-- Tickets --}}
      @php
      $uid='sb-tickets'; $ticketsBase=url('/dashboard/tickets');
      $ticketsCreate=route('tickets.create'); $ticketsProg=route('tickets.on-progress'); $ticketsReport=route('tickets.index');
      @endphp
      <li
        x-data="{ key:'menu.tickets', active: {{ request()->routeIs('tickets.*') ? 'true' : 'false' }}, defaultSel:'group:menu.tickets', forceOpen: {{ $ticketsReportActive ? 'true' : 'false' }} }"
        x-init="
          $nextTick(() => {
            if ((active || forceOpen) && window.Alpine && window.Alpine.store('sb')) {
              window.Alpine.store('sb').set(key);
            }
          });
        "
        class="nav-li"
        :class="(sel===defaultSel || sel.startsWith('{{ $ticketsBase }}')) ? 'allow-hover' : ''"
        x-bind:data-open="($store.sb.openKey===key || forceOpen)">
        <button type="button"
          data-sb-key="menu.tickets"
          @click="ripple($event); if(window.Alpine && window.Alpine.store('sb')) { if(forceOpen && window.Alpine.store('sb').openKey!==key){ window.Alpine.store('sb').set(key); } else { window.Alpine.store('sb').toggle(key); } } forceOpen=false; setSel(defaultSel); iconAnim($event,'ticket')"
          :aria-expanded="($store.sb.openKey===key || forceOpen).toString()"
          aria-controls="{{ $uid }}"
          class="{{ $btnBase }} {{ $btnHover }} sb-ripple"
          :class="(sel===defaultSel || sel.startsWith('{{ $ticketsBase }}')) ? '{{ $btnActive }} is-active' : ''">
          <span class="flex items-center">
            <span class="material-icons text-current">confirmation_number</span>
            <span class="nav-label" x-show="sidebarOpen" x-transition.opacity>Tickets</span>
            <span class="ml-2 inline-block w-2 h-2 rounded-full bg-red-500" x-show="sidebarOpen" x-transition.opacity aria-label="Tickets notification"></span>
          </span>
          <span class="material-icons transition-transform duration-200 text-current" :class="($store.sb.openKey===key || forceOpen) ? 'rotate-180' : ''" x-show="sidebarOpen">expand_more</span>
        </button>

        <ul id="{{ $uid }}"
          x-show="($store.sb.openKey===key || forceOpen) && sidebarOpen"
          x-transition:enter="transition ease-out duration-150"
          x-transition:leave="transition ease-in duration-100"
          class="mt-1 space-y-1 pl-10">
          <li>
            <a href="{{ $ticketsCreate }}"
              @click.prevent="clearActives(); badgePing('menu.tickets'); setSel('{{ $ticketsCreate }}'); navTo('{{ $ticketsCreate }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} {{ request()->routeIs('tickets.create') ? $linkActive.' is-active' : '' }}"
              :class="sel === '{{ $ticketsCreate }}' ? '{{ $linkActive }} is-active' : ''">Create Ticket</a>
          </li>
          <li>
            <a href="{{ $ticketsProg }}"
              @click.prevent="clearActives(); badgePing('menu.tickets'); setSel('{{ $ticketsProg }}'); navTo('{{ $ticketsProg }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} flex items-center {{ request()->routeIs('tickets.on-progress') ? $linkActive.' is-active' : '' }}"
              :class="sel === '{{ $ticketsProg }}' ? '{{ $linkActive }} is-active' : ''">
              <span>In Progress</span>
              <span class="ml-auto inline-flex items-center gap-1 text-xs font-semibold text-amber-700/90 dark:text-amber-300">
                <span class="inline-block w-2 h-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                <span>{{ $ticketsInProgressCount ?? 0 }}</span>
              </span>
            </a>
          </li>
          <li>
            <a href="{{ $ticketsReport }}"
              @click.prevent="clearActives(); badgePing('menu.tickets'); forceOpen=true; setSel('{{ $ticketsReport }}'); navTo('{{ $ticketsReport }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} {{ $ticketsReportActive ? $linkActive.' is-active' : '' }}"
              :class="(sel === '{{ $ticketsReport }}' || forceOpen) ? '{{ $linkActive }} is-active' : ''">
              <span>Report</span>
            </a>
          </li>
        </ul>
      </li>

      {{-- Tasks --}}
      @php
      $uid='sb-tasks'; $tasksBase=url('/dashboard/tasks');
      $tasksCreate=route('tasks.create'); $tasksProg=route('tasks.on-progress'); $tasksReport=route('tasks.report');
      @endphp
      <li
        x-data="{ key:'menu.tasks', active: {{ request()->routeIs('tasks.*') ? 'true' : 'false' }}, defaultSel:'group:menu.tasks', forceOpen: {{ $tasksReportActive ? 'true' : 'false' }} }"
        x-init="
          $nextTick(() => {
            if ((active || forceOpen) && window.Alpine && window.Alpine.store('sb')) {
              window.Alpine.store('sb').set(key);
            }
          });
        "
        class="nav-li nav-li--tasks"
        :class="(sel===defaultSel || sel.startsWith('{{ $tasksBase }}')) ? 'allow-hover' : ''"
        x-bind:data-open="($store.sb.openKey===key || forceOpen)">
        <button type="button"
          data-sb-key="menu.tasks"
          @click="ripple($event); if(window.Alpine && window.Alpine.store('sb')) { if(forceOpen && window.Alpine.store('sb').openKey!==key){ window.Alpine.store('sb').set(key); } else { window.Alpine.store('sb').toggle(key); } } forceOpen=false; setSel(defaultSel); iconAnim($event,'check')"
          :aria-expanded="($store.sb.openKey===key || forceOpen).toString()"
          aria-controls="{{ $uid }}"
          class="{{ $btnBase }} {{ $btnHover }} sb-ripple"
          :class="(sel===defaultSel || sel.startsWith('{{ $tasksBase }}')) ? '{{ $btnActive }} is-active' : ''">
          <span class="flex items-center">
            <span class="material-icons text-current">task</span>
            <span class="nav-label" x-show="sidebarOpen" x-transition.opacity>Tasks</span>
            <span class="ml-2 inline-block w-2 h-2 rounded-full bg-red-500" x-show="sidebarOpen" x-transition.opacity aria-label="Tasks notification"></span>
          </span>
          <span class="material-icons transition-transform duration-200 text-current" :class="($store.sb.openKey===key || forceOpen) ? 'rotate-180' : ''" x-show="sidebarOpen">expand_more</span>
        </button>

        <ul id="{{ $uid }}"
          x-show="($store.sb.openKey===key || forceOpen) && sidebarOpen"
          x-transition:enter="transition ease-out duration-150"
          x-transition:leave="transition ease-in duration-100"
          class="mt-1 space-y-1 pl-10">
          <li>
            <a href="{{ $tasksCreate }}"
              @click.prevent="clearActives(); badgePing('menu.tasks'); setSel('{{ $tasksCreate }}'); navTo('{{ $tasksCreate }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} {{ request()->routeIs('tasks.create') ? $linkActive.' is-active' : '' }}"
              :class="sel === '{{ $tasksCreate }}' ? '{{ $linkActive }} is-active' : ''">Create Task</a>
          </li>
          <li>
            <a href="{{ $tasksProg }}"
              @click.prevent="clearActives(); badgePing('menu.tasks'); setSel('{{ $tasksProg }}'); navTo('{{ $tasksProg }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} flex items-center {{ request()->routeIs('tasks.on-progress') ? $linkActive.' is-active' : '' }}"
              :class="sel === '{{ $tasksProg }}' ? '{{ $linkActive }} is-active' : ''">
              <span>In Progress</span>
              <span class="ml-auto inline-flex items-center gap-1 text-xs font-semibold text-amber-700/90 dark:text-amber-300">
                <span class="inline-block w-2 h-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                <span>{{ $tasksInProgressCount ?? 0 }}</span>
              </span>
            </a>
          </li>
          <li>
            <a href="{{ $tasksReport }}"
              @click.prevent="clearActives(); badgePing('menu.tasks'); forceOpen=true; setSel('{{ $tasksReport }}'); navTo('{{ $tasksReport }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} {{ $tasksReportActive ? $linkActive.' is-active' : '' }}"
              :class="(sel === '{{ $tasksReport }}' || forceOpen) ? '{{ $linkActive }} is-active' : ''">
              <span>Report</span>
            </a>
          </li>
        </ul>
      </li>

      {{-- Projects --}}
      @php
      $uid='sb-projects'; $projectsBase=url('/dashboard/projects');
      $projectsCreate=route('projects.create'); $projectsProg=route('projects.on-progress'); $projectsReport=route('projects.report');
      @endphp
      <li
        x-data="{ key:'menu.projects', active: {{ request()->routeIs('projects.*') ? 'true' : 'false' }}, defaultSel:'group:menu.projects', forceOpen: {{ $projectsReportActive ? 'true' : 'false' }} }"
        x-init="
          $nextTick(() => {
            if ((active || forceOpen) && window.Alpine && window.Alpine.store('sb')) {
              window.Alpine.store('sb').set(key);
            }
          });
        "
        class="nav-li"
        :class="(sel===defaultSel || sel.startsWith('{{ $projectsBase }}')) ? 'allow-hover' : ''"
        x-bind:data-open="($store.sb.openKey===key || forceOpen)">
        <button type="button"
          data-sb-key="menu.projects"
          @click="ripple($event); if(window.Alpine && window.Alpine.store('sb')) { if(forceOpen && window.Alpine.store('sb').openKey!==key){ window.Alpine.store('sb').set(key); } else { window.Alpine.store('sb').toggle(key); } } forceOpen=false; setSel(defaultSel); iconAnim($event,'folder')"
          :aria-expanded="($store.sb.openKey===key || forceOpen).toString()"
          aria-controls="{{ $uid }}"
          class="{{ $btnBase }} {{ $btnHover }} sb-ripple"
          :class="(sel===defaultSel || sel.startsWith('{{ $projectsBase }}')) ? '{{ $btnActive }} is-active' : ''">
          <span class="flex items-center">
            <span class="material-icons text-current">folder</span>
            <span class="nav-label" x-show="sidebarOpen" x-transition.opacity>Projects</span>
            <span class="ml-2 inline-block w-2 h-2 rounded-full bg-red-500" x-show="sidebarOpen" x-transition.opacity aria-label="Projects notification"></span>
          </span>
          <span class="material-icons transition-transform duration-200 text-current" :class="($store.sb.openKey===key || forceOpen) ? 'rotate-180' : ''" x-show="sidebarOpen">expand_more</span>
        </button>

        <ul id="{{ $uid }}"
          x-show="($store.sb.openKey===key || forceOpen) && sidebarOpen"
          x-transition:enter="transition ease-out duration-150"
          x-transition:leave="transition ease-in duration-100"
          class="mt-1 space-y-1 pl-10">
          <li>
            <a href="{{ $projectsCreate }}"
              @click.prevent="clearActives(); badgePing('menu.projects'); setSel('{{ $projectsCreate }}'); navTo('{{ $projectsCreate }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} {{ request()->routeIs('projects.create') ? $linkActive.' is-active' : '' }}"
              :class="sel === '{{ $projectsCreate }}' ? '{{ $linkActive }} is-active' : ''">Create Project</a>
          </li>
          <li>
            <a href="{{ $projectsProg }}"
              @click.prevent="clearActives(); badgePing('menu.projects'); setSel('{{ $projectsProg }}'); navTo('{{ $projectsProg }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} flex items-center {{ request()->routeIs('projects.on-progress') ? $linkActive.' is-active' : '' }}"
              :class="sel === '{{ $projectsProg }}' ? '{{ $linkActive }} is-active' : ''">
              <span>In Progress</span>
              <span class="ml-auto inline-flex items-center gap-1 text-xs font-semibold text-amber-700/90 dark:text-amber-300">
                <span class="inline-block w-2 h-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                <span>{{ $projectsInProgressCount ?? 0 }}</span>
              </span>
            </a>
          </li>
          <li>
            <a href="{{ $projectsReport }}"
              @click.prevent="clearActives(); badgePing('menu.projects'); forceOpen=true; setSel('{{ $projectsReport }}'); navTo('{{ $projectsReport }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} {{ $projectsReportActive ? $linkActive.' is-active' : '' }}"
              :class="(sel === '{{ $projectsReport }}' || forceOpen) ? '{{ $linkActive }} is-active' : ''">
              <span>Report</span>
            </a>
          </li>
        </ul>
      </li>

      @if ($isSuperAdmin)
        {{-- Unit Reports (legacy) --}}
        @php $unitReports = route('dashboard.unit-reports'); @endphp
        <li class="nav-li">
          <a href="{{ $unitReports }}"
            @click.prevent="clearActives(); setSel('{{ $unitReports }}'); navTo('{{ $unitReports }}', $event, 'pulse', 260)"
            class="{{ $linkBase }} {{ $linkHover }} flex items-center gap-2 {{ request()->routeIs('dashboard.unit-reports') ? $linkActive.' is-active' : '' }}"
            :class="sel === '{{ $unitReports }}' ? '{{ $linkActive }} is-active' : ''">
            <span class="material-icons text-current">stacked_line_chart</span>
            <span class="nav-label" x-show="sidebarOpen" x-transition.opacity>Unit Reports</span>
          </a>
        </li>

        {{-- SLA Reports --}}
        <li class="nav-li">
          <a href="{{ route('dashboard.sla') }}"
            @click.prevent="clearActives(); setSel('route:dashboard.sla'); navTo('{{ route('dashboard.sla') }}', $event, 'pulse', 280)"
            class="{{ $linkBase }} {{ $linkHover }} flex items-center gap-2 {{ $slaReportActive ? $linkActive.' is-active' : '' }}"
            :class="sel === 'route:dashboard.sla' ? '{{ $linkActive }} is-active' : ''">
            <span class="material-icons text-current">schedule</span>
            <span class="nav-label" x-show="sidebarOpen" x-transition.opacity>SLA Reports</span>
          </a>
        </li>

        {{-- Global Reports --}}
        @php $reportsIndex = route('reports.index'); @endphp
        <li class="nav-li">
          <a href="{{ $reportsIndex }}"
            @click.prevent="clearActives(); setSel('{{ $reportsIndex }}'); navTo('{{ $reportsIndex }}', $event, 'pulse', 280)"
            class="{{ $linkBase }} {{ $linkHover }} flex items-center gap-2 {{ request()->routeIs('reports.*') ? $linkActive.' is-active' : '' }}"
            :class="sel === '{{ $reportsIndex }}' ? '{{ $linkActive }} is-active' : ''">
            <span class="material-icons text-current">analytics</span>
            <span class="nav-label" x-show="sidebarOpen" x-transition.opacity>Reports</span>
          </a>
        </li>

        {{-- Settings --}}
        @php $settingsUrl = route('settings'); @endphp
        <li class="nav-li">
          <a href="{{ $settingsUrl }}"
            @click.prevent="clearActives(); setSel('{{ $settingsUrl }}'); navTo('{{ $settingsUrl }}', $event, 'pulse', 280)"
            class="{{ $linkBase }} {{ $linkHover }} flex items-center gap-2 {{ request()->routeIs('settings') ? $linkActive.' is-active' : '' }}"
            :class="sel === '{{ $settingsUrl }}' ? '{{ $linkActive }} is-active' : ''">
            <span class="material-icons text-current">settings_suggest</span>
            <span class="nav-label" x-show="sidebarOpen" x-transition.opacity>Settings</span>
          </a>
        </li>
      @endif

      {{-- Users --}}
      @can('viewAny', \App\Models\User::class)
      @php
      $uid='sb-users'; $usersBase=url('/dashboard/users');
      $usersCreate=route('users.create'); $usersReport=route('users.report');
      @endphp
      <li x-data="{ key:'menu.users', active: {{ request()->routeIs('users.*') ? 'true' : 'false' }} }"
        x-init="
          $nextTick(() => {
            if(active && window.Alpine && window.Alpine.store('sb')) {
              window.Alpine.store('sb').set(key);
            }
          });
        "
        class="nav-li"
        :class="(sel==='group:menu.users' || sel.startsWith('{{ $usersBase }}')) ? 'allow-hover' : ''"
        x-bind:data-open="$store.sb.openKey===key">
        <button type="button"
          data-sb-key="menu.users"
          @click="ripple($event); if(window.Alpine && window.Alpine.store('sb')) { window.Alpine.store('sb').toggle(key); } setSel('group:menu.users'); iconAnim($event,'pop')"
          :aria-expanded="($store.sb.openKey===key).toString()"
          aria-controls="{{ $uid }}"
          class="{{ $btnBase }} {{ $btnHover }} sb-ripple"
          :class="(sel==='group:menu.users' || sel.startsWith('{{ $usersBase }}')) ? '{{ $btnActive }} is-active' : ''">
          <span class="flex items-center">
            <span class="material-icons text-current">people</span>
            <span class="nav-label" x-show="sidebarOpen" x-transition.opacity>Users</span>
          </span>
          <span class="material-icons transition-transform duration-200 text-current" :class="$store.sb.openKey===key ? 'rotate-180' : ''" x-show="sidebarOpen">expand_more</span>
        </button>
        <ul id="{{ $uid }}"
          x-show="$store.sb.openKey===key && sidebarOpen"
          x-transition:enter="transition ease-out duration-150"
          x-transition:leave="transition ease-in duration-100"
          class="mt-1 space-y-1 pl-10">
          <li>
            <a href="{{ $usersCreate }}"
              @click.prevent="clearActives(); badgePing('menu.users'); setSel('{{ $usersCreate }}'); navTo('{{ $usersCreate }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} {{ request()->routeIs('users.create') ? $linkActive.' is-active' : '' }}"
              :class="sel === '{{ $usersCreate }}' ? '{{ $linkActive }} is-active' : ''">Create User</a>
          </li>
          <li>
            <a href="{{ $usersReport }}"
              @click.prevent="clearActives(); badgePing('menu.users'); setSel('{{ $usersReport }}'); navTo('{{ $usersReport }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} {{ request()->routeIs('users.report') ? $linkActive.' is-active' : '' }}"
              :class="sel === '{{ $usersReport }}' ? '{{ $linkActive }} is-active' : ''">Report</a>
          </li>
        </ul>
      </li>
      @endcan

      {{-- Account --}}
      @php
      $uid='sb-account'; $accountBase=url('/dashboard/account');
      $accountProfile=route('account.profile'); $accountPass=route('account.change-password');
      @endphp
      <li x-data="{ key:'menu.account', active: {{ request()->routeIs('account.*') ? 'true' : 'false' }} }"
        x-init="
          $nextTick(() => {
            if(active && window.Alpine && window.Alpine.store('sb')) {
              window.Alpine.store('sb').set(key);
            }
          });
        "
        class="nav-li nav-li--account"
        :class="(sel==='group:menu.account' || sel.startsWith('{{ $accountBase }}')) ? 'allow-hover' : ''"
        x-bind:data-open="$store.sb.openKey===key">
        <button type="button"
          data-sb-key="menu.account"
          @click="ripple($event); if(window.Alpine && window.Alpine.store('sb')) { window.Alpine.store('sb').toggle(key); } setSel('group:menu.account'); iconAnim($event,'gear')"
          :aria-expanded="($store.sb.openKey===key).toString()"
          aria-controls="{{ $uid }}"
          class="{{ $btnBase }} {{ $btnHover }} sb-ripple"
          :class="(sel==='group:menu.account' || sel.startsWith('{{ $accountBase }}')) ? '{{ $btnActive }} is-active' : ''">
          <span class="flex items-center">
            <span class="material-icons text-current">settings</span>
            <span class="nav-label" x-show="sidebarOpen" x-transition.opacity>Account</span>
          </span>
          <span class="material-icons transition-transform duration-200 text-current" :class="$store.sb.openKey===key ? 'rotate-180' : ''" x-show="sidebarOpen">expand_more</span>
        </button>
        <ul id="{{ $uid }}"
          x-show="$store.sb.openKey===key && sidebarOpen"
          x-transition:enter="transition ease-out duration-150"
          x-transition:leave="transition ease-in duration-100"
          class="mt-1 space-y-1 pl-10">
          <li>
            <a href="{{ $accountProfile }}"
              @click.prevent="clearActives(); badgePing('menu.account'); setSel('{{ $accountProfile }}'); navTo('{{ $accountProfile }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} {{ request()->routeIs('account.profile') ? $linkActive.' is-active' : '' }}"
              :class="sel === '{{ $accountProfile }}' ? '{{ $linkActive }} is-active' : ''">Profile</a>
          </li>
          <li>
            <a href="{{ $accountPass }}"
              @click.prevent="clearActives(); badgePing('menu.account'); setSel('{{ $accountPass }}'); navTo('{{ $accountPass }}', $event, 'pulse', 320)"
              class="{{ $linkBase }} {{ request()->routeIs('account.change-password') ? $linkActive.' is-active' : '' }}"
              :class="sel === '{{ $accountPass }}' ? '{{ $linkActive }} is-active' : ''">Change Password</a>
          </li>
        </ul>
      </li>

      {{-- Toggle Theme --}}
      <li class="nav-li allow-hover nav-li--dash nav-li--theme">
        <button type="button" @click="ripple($event); $dispatch('toggle-theme'); iconAnim($event,'theme')" class="w-full flex items-center px-4 py-2 rounded-xl transition-colors focus:outline-none sb-ripple">
          <span class="relative inline-flex items-center justify-center w-5 h-5 theme-toggle">
            <span class="material-icons sun absolute">light_mode</span>
            <span class="material-icons moon absolute">dark_mode</span>
          </span>
          <span class="nav-label">Toggle Theme</span>
        </button>
      </li>

      {{-- Logout --}}
      @auth
      <li class="nav-li nav-li--logout">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit"
            @click="ripple($event); iconAnim($event,'slide')"
            class="w-full flex items-center px-4 py-2 rounded-xl transition-colors focus:outline-none sb-ripple">
            <span class="material-icons text-current">logout</span>
            <span class="nav-label">Logout</span>
          </button>
        </form>
      </li>
      @endauth
    </ul>

    <style>
      .sidebar .menu>li.nav-li--tasks,
      .sidebar .menu>li.nav-li--account,
      .sidebar .menu>li.nav-li--logout {
        position: relative;
        border-radius: .75rem;
        overflow: visible
      }

      .sidebar .menu>li.nav-li--tasks>button,
      .sidebar .menu>li.nav-li--account>button,
      .sidebar .menu>li.nav-li--logout>form>button {
        position: relative;
        z-index: 2
      }

      .sidebar .menu>li.nav-li--tasks::after,
      .sidebar .menu>li.nav-li--account::after,
      .sidebar .menu>li.nav-li--logout::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: .75rem;
        z-index: 1;
        opacity: 0;
        transition: opacity .18s ease
      }

      .sidebar .menu>li.nav-li--tasks:hover::after {
        background: rgba(37, 99, 235, .12) !important;
        opacity: 1
      }

      .dark .sidebar .menu>li.nav-li--tasks:hover::after {
        background: rgba(59, 130, 246, .26) !important;
        opacity: 1
      }

      .sidebar .menu>li.nav-li--account:hover::after {
        background: rgba(124, 58, 237, .12) !important;
        opacity: 1
      }

      .dark .sidebar .menu>li.nav-li--account:hover::after {
        background: rgba(139, 92, 246, .26) !important;
        opacity: 1
      }

      .sidebar .menu>li.nav-li--logout:hover::after {
        background: rgba(239, 68, 68, .14) !important;
        opacity: 1
      }

      .dark .sidebar .menu>li.nav-li--logout:hover::after {
        background: rgba(239, 68, 68, .28) !important;
        opacity: 1
      }

      .sidebar .menu>li.nav-li--tasks>button:hover,
      .sidebar .menu>li.nav-li--account>button:hover,
      .sidebar .menu>li.nav-li--logout>form>button:hover {
        background-color: transparent !important;
        background-image: none !important;
        color: inherit !important
      }

      .sidebar .menu>li.nav-li--theme>button {
        transition: background-color .18s ease, color .18s ease, box-shadow .18s ease;
      }

      body:not(.dark) .sidebar:not(.no-hover) .menu>li.nav-li--theme:hover>button,
      body:not(.dark) .sidebar:not(.no-hover) .menu>li.nav-li--theme>button:focus-visible {
        background: linear-gradient(135deg, #fde68a, #fbbf24) !important;
        color: #92400e !important;
        box-shadow: 0 12px 28px -18px rgba(251, 191, 36, .68);
      }

      .dark .sidebar .menu>li.nav-li--theme:hover>button,
      .dark .sidebar .menu>li.nav-li--theme>button:focus-visible {
        background: linear-gradient(135deg, rgba(250, 204, 21, .32), rgba(249, 115, 22, .34)) !important;
        color: #fef3c7 !important;
        box-shadow: 0 14px 30px -18px rgba(253, 224, 71, .45);
      }

      .sidebar .menu>li.nav-li--tasks>button.sb-ripple .ripple-ink {
        background: rgba(37, 99, 235, .28) !important;
        opacity: .18 !important
      }

      .dark .sidebar .menu>li.nav-li--tasks>button.sb-ripple .ripple-ink {
        opacity: .22 !important
      }

      .sidebar .menu>li.nav-li--account>button.sb-ripple .ripple-ink {
        background: rgba(124, 58, 237, .28) !important;
        opacity: .18 !important
      }

      .dark .sidebar .menu>li.nav-li--account>button.sb-ripple .ripple-ink {
        opacity: .22 !important
      }

      .sidebar .menu>li.nav-li--theme>button.sb-ripple .ripple-ink {
        background: rgba(251, 191, 36, .32) !important;
        opacity: .18 !important;
      }

      .dark .sidebar .menu>li.nav-li--theme>button.sb-ripple .ripple-ink {
        background: rgba(250, 204, 21, .4) !important;
        opacity: .24 !important;
      }

      .sidebar .menu>li.nav-li--logout>form>button.sb-ripple .ripple-ink {
        background: rgba(239, 68, 68, .32) !important;
        opacity: .18 !important
      }

      .dark .sidebar .menu>li.nav-li--logout>form>button.sb-ripple .ripple-ink {
        opacity: .24 !important
      }

      .menu[data-nav-lock="1"] .nav-li {
        pointer-events: none !important
      }

      .menu[data-nav-lock="1"] .nav-li>a:hover,
      .menu[data-nav-lock="1"] .nav-li>button:hover,
      .menu[data-nav-lock="1"] .nav-li>form>button:hover {
        background-color: transparent !important;
        color: inherit !important
      }
    </style>

    <style>
      body:not(.dark) .sidebar:not(.no-hover) .menu>li.nav-li:not(.nav-li--theme):hover>a,
      body:not(.dark) .sidebar:not(.no-hover) .menu>li.nav-li:not(.nav-li--theme):hover>button,
      body:not(.dark) .sidebar:not(.no-hover) .menu>li.nav-li:not(.nav-li--theme):hover>form>button {
        color: #ffffff !important
      }

      body:not(.dark) .sidebar .menu>li.nav-li:not(.nav-li--theme)>a.is-active:hover,
      body:not(.dark) .sidebar .menu>li.nav-li:not(.nav-li--theme)>button.is-active:hover,
      body:not(.dark) .sidebar .menu>li.nav-li:not(.nav-li--theme)>form>button.is-active:hover {
        color: #ffffff !important
      }
    </style>
</nav>
