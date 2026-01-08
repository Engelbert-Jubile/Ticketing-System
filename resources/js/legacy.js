// Legacy application bootstrap (Blade + Alpine.js)
// Copied from previous resources/js/app.js prior to Inertia setup.

// Page modules
import { initTicketCreate } from './pages/ticketCreate';
import { initTaskCreate } from './pages/taskCreate';
import { initProjectCreate } from './pages/projectCreate';

// ---------------------------------------------------------
// Alpine coordination helper (gunakan Alpine bawaan Livewire)
// ---------------------------------------------------------
const alpineReadyQueue = [];
let alpineBootstrapped = false;

function flushAlpineQueue(Alpine) {
  if (!Alpine || alpineBootstrapped) {
    if (Alpine && alpineReadyQueue.length) {
      let cb;
      while ((cb = alpineReadyQueue.shift())) {
        try { cb(Alpine); } catch (_) {}
      }
    }
    return;
  }

  alpineBootstrapped = true;
  let cb;
  while ((cb = alpineReadyQueue.shift())) {
    try { cb(Alpine); } catch (_) {}
  }
}

export function withAlpine(callback) {
  if (typeof callback !== 'function') return;
  const Alpine = window.Alpine;
  if (Alpine && typeof Alpine.version !== 'undefined') {
    callback(Alpine);
    return;
  }
  alpineReadyQueue.push(callback);
}

document.addEventListener('alpine:init', () => {
  const Alpine = window.Alpine;
  if (!Alpine) return;
  flushAlpineQueue(Alpine);
});

if (window.Alpine && typeof window.Alpine.version !== 'undefined') {
  flushAlpineQueue(window.Alpine);
}

function getSidebarStore() {
  try {
    const Alpine = window.Alpine;
    if (Alpine && typeof Alpine.store === 'function') {
      return Alpine.store('sb') || null;
    }
  } catch (_) {}
  return null;
}

// ---------------------------------------------------------
// Sidebar store initialization (dieksekusi ketika Alpine siap)
// ---------------------------------------------------------
function initSidebarStore(Alpine) {
  try {
    if (!Alpine || typeof Alpine.store !== 'function' || typeof Alpine.reactive !== 'function') return;
    if (Alpine.store('sb')) {
      return;
    }

    // Create reactive store using Alpine's magic
    const store = Alpine.reactive({
      openKey: null,

      init() {
        try {
          this.openKey = null;
          localStorage.removeItem('sb:openKey');
          localStorage.removeItem('sb:selected');
          document.documentElement.classList.add('optimize-animations');
        } catch (_) {}
      },

      set(key) {
        this.openKey = key;
        try {
          if (key === null) localStorage.removeItem('sb:openKey');
        } catch (_) {}
      },

      toggle(key) {
        this.set(this.openKey === key ? null : key);
      },

      closeAll() {
        this.set(null);
        try {
          localStorage.removeItem('sb:openKey');
          localStorage.removeItem('sb:selected');
        } catch (_) {}
      }
    });

    Alpine.store('sb', store);
  } catch (e) {
    console.warn('Failed to init sidebar store:', e);
  }
}

// ---------------------------------------------------------
// Global page lifecycle (initial load + Livewire navigations)
// ---------------------------------------------------------
(() => {
  const mounts = [];
  function runMounts(ctx = {}) {
    try {
      document.dispatchEvent(new CustomEvent('app:mount', { detail: ctx }));
    } catch (_) {}
    for (const fn of mounts) {
      try {
        fn(ctx);
      } catch (_) {}
    }
  }
  window.onAppMount = function onAppMount(fn) {
    if (typeof fn === 'function') mounts.push(fn);
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => runMounts({ source: 'dom' }), { once: true });
  } else {
    queueMicrotask(() => runMounts({ source: 'dom-immediate' }));
  }
  window.addEventListener('pageshow', () => runMounts({ source: 'pageshow' }));
  const handleSpaMount = source => () => runMounts({ source });
  document.addEventListener('livewire:navigated', handleSpaMount('livewire'));
  document.addEventListener('inertia:navigate', handleSpaMount('inertia'));
})();

// ---------------------------------------------------------
// SPA-safe initializers (Flatpickr, Quill, dll.)
// ---------------------------------------------------------
onAppMount(async () => {
  // Flatpickr
  try {
    if (window.flatpickr) {
      const ensurePicker = (sel, opts = {}) => {
        const el = document.querySelector(sel);
        if (!el || el._flatpickr) return;
        window.flatpickr(el, opts);
      };
      ensurePicker('#due_date', { allowInput: true, dateFormat: 'd/m/Y' });
      ensurePicker('#finish_date', { allowInput: true, dateFormat: 'd/m/Y' });

      const timeOpts = {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true,
        minuteIncrement: 5,
        allowInput: true
      };
      ensurePicker('#due_time', timeOpts);
      ensurePicker('#finish_time', timeOpts);

      document.querySelectorAll('.flatpickr-field').forEach(el => {
        if (!el._flatpickr) window.flatpickr(el, { allowInput: true, dateFormat: 'd/m/Y' });
      });
    }
  } catch (_) {}

  // Quill
  try {
    if (window.Quill) {
      const pairs = [
        ['#description_editor', '#description'],
        ['#project_description_editor', '#description']
      ];
      for (const [edSel, hidSel] of pairs) {
        const ed = document.querySelector(edSel);
        const hid = document.querySelector(hidSel);
        if (!ed || !hid || ed.__quill) continue;

        const quill = new window.Quill(ed, {
          theme: 'snow',
          placeholder: 'Tulis detail di sini...',
          modules: {
            toolbar: [
              [{ header: [1, 2, 3, false] }],
              ['bold', 'italic', 'underline', 'strike', 'blockquote'],
              [{ align: [] }],
              [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
              ['link', 'image', 'code-block', 'clean']
            ]
          }
        });
        ed.__quill = quill;

        if (hid.value?.trim()) {
          try {
            quill.clipboard.dangerouslyPasteHTML(hid.value);
          } catch {}
        }
        const counter = document.getElementById('descCounter') || null;
        const sync = () => {
          hid.value = quill.root.innerHTML;
          try { document.dispatchEvent(new CustomEvent('create:dirty')); } catch (_) {}
          if (counter) {
            const words = quill.getText().trim().split(/\s+/).filter(Boolean).length || 0;
            counter.textContent = `${words} kata`;
          }
        };
        quill.on('text-change', sync);
      }
    }
  } catch (_) {}
});

// ---------------------------------------------------------
// layout() — dipakai oleh <html x-data="layout()">
// ---------------------------------------------------------
window.layout = function layout() {
  return {
    sidebarOpen: false,
    sidebarLocked: false,
    isDesktop: false,
    hoverGate: false,
    hoverCapable: window.matchMedia('(hover: hover)').matches,

    setAppSpace() {
      try {
        const root = document.documentElement;
        const styles = getComputedStyle(root);
        const max = styles.getPropertyValue('--sb-max').trim() || '18rem';
        const mini = styles.getPropertyValue('--sb-mini').trim() || '4.75rem';
        const width = this.isDesktop ? (this.sidebarOpen ? max : mini) : '0px';
        root.style.setProperty('--app-sidebar-space', width);
      } catch (_) {}
    },

    init() {
      const mqDesktop = window.matchMedia('(min-width: 1024px)');
      this.isDesktop = mqDesktop.matches;

      // Ambil sinkron awal dari komponen <nav.sidebar> kalau sudah ter-mount
      const sidebarEl = document.querySelector('nav.sidebar');
      if (sidebarEl && sidebarEl.__x) {
        this.sidebarOpen = sidebarEl.__x.get.sidebarOpen;
        this.sidebarLocked = sidebarEl.__x.get.sidebarLocked;
      } else {
        const so = (() => {
          try {
            return localStorage.getItem('sidebarOpen');
          } catch {
            return null;
          }
        })();
        const sl = (() => {
          try {
            return localStorage.getItem('sidebarLocked');
          } catch {
            return null;
          }
        })();
        this.sidebarOpen = so !== null ? JSON.parse(so) : this.isDesktop ? false : false;
        this.sidebarLocked = sl !== null ? JSON.parse(sl) : false;
      }

      this.setAppSpace();

      mqDesktop.addEventListener
        ? mqDesktop.addEventListener('change', e => {
            this.isDesktop = e.matches;
            this.setAppSpace();
          })
        : mqDesktop.addListener(e => {
            this.isDesktop = e.matches;
            this.setAppSpace();
          });

      window.addEventListener('sb:sync', e => {
        if (!e.detail) return;
        if (typeof e.detail.locked !== 'undefined') this.sidebarLocked = e.detail.locked;
        if (typeof e.detail.open !== 'undefined') this.sidebarOpen = e.detail.open;
        this.setAppSpace();
      });

      this.$watch('sidebarOpen', () => this.setAppSpace());
      this.$watch('sidebarLocked', () => this.setAppSpace());

        // Theme
        this.applyStoredTheme();
        // Ensure theme persists on BFCache restore and SPA navigations
        window.addEventListener('pageshow', () => this.applyStoredTheme());
        const prefers = window.matchMedia('(prefers-color-scheme: dark)');
        prefers.addEventListener?.('change', e => {
          if (!localStorage.getItem('theme')) document.documentElement.classList.toggle('dark', e.matches);
        });
        this.$root.addEventListener('toggle-theme', () => this.toggleTheme());
        const themeSync = () => this.applyStoredTheme();
        document.addEventListener('livewire:navigated', themeSync);
        document.addEventListener('inertia:navigate', themeSync);

      const store = getSidebarStore();
      store?.init?.();

      requestAnimationFrame(() => document.documentElement.classList.remove('sb-pref-open'));
      this.setAppSpace();
    },

    gateHover(msFallback = 380) {
      try {
        if (this.hoverGate) return;
        this.hoverGate = true;
        const el = document.querySelector('nav.sidebar') || document.querySelector('.sidebar');
        let done = false;
        const clear = () => {
          if (done) return;
          done = true;
          this.hoverGate = false;
          el?.removeEventListener('transitionend', onEnd, true);
        };
        const onEnd = e => {
          if (e.target === el && (e.propertyName === 'width' || e.propertyName === 'transform')) clear();
        };
        el?.addEventListener('transitionend', onEnd, true);
        clearTimeout(this._hoverT);
        this._hoverT = setTimeout(clear, msFallback);
      } catch (_) {}
    },
    fireSidebarCommand(cmd) {
      try {
        window.dispatchEvent(new CustomEvent('sb:command', { detail: cmd }));
      } catch (_) {}
    },
    toggleLock() {
      this.fireSidebarCommand('toggle-lock');
    },
    onSidebarEnter() {
      if (!this.sidebarLocked && this.hoverCapable && this.isDesktop && !this.sidebarOpen) {
        this.gateHover();
        requestAnimationFrame(() => {
          this.sidebarOpen = true;
        });
      }
    },
    onSidebarLeave() {
      if (!this.sidebarLocked && this.hoverCapable && this.isDesktop && this.sidebarOpen) {
        this.gateHover();
        requestAnimationFrame(() => {
          this.sidebarOpen = false;
        });
      }
    },
    onEdgeEnter() {
      if (!this.sidebarLocked && this.hoverCapable && this.isDesktop && !this.sidebarOpen) {
        this.gateHover();
        requestAnimationFrame(() => {
          this.sidebarOpen = true;
        });
      }
    },
    onEdgeLeave() {
      if (!this.sidebarLocked && this.hoverCapable && this.isDesktop && this.sidebarOpen) {
        this.gateHover();
        this.sidebarOpen = false;
      }
    },

    // Desktop: treat topbar button as lock; Mobile: toggle
    onTopbarButton() {
      if (this.isDesktop) this.toggleLock();
      else this.toggleSidebar();
    },

    playIcon(el, kind = 'pulse') {
      if (!el) return;
      const cls = `ai-${kind}`;
      el.classList.remove(cls);
      void el.offsetWidth;
      el.classList.add(cls);
      el.addEventListener('animationend', () => el.classList.remove(cls), { once: true });
    },
    iconAnim(evt, kind = 'pulse') {
      try {
        const btn = evt?.currentTarget || evt?.target;
        let target = kind === 'theme' ? btn?.querySelector?.('.theme-toggle') : null;
        if (!target) target = btn?.querySelector?.('.material-icons');
        if (target) this.playIcon(target, kind);
      } catch (_) {}
    },
    navTo(url, evt, kind = 'pulse', delay = 180) {
      this.iconAnim(evt, kind);
      this.ripple(evt);
      try {
        const go = () => {
          if (window.Inertia?.visit) {
            window.Inertia.visit(url);
            return;
          }
          if (window.Livewire?.navigate) {
            window.Livewire.navigate(url);
            return;
          }
          window.location.href = url;
        };
        setTimeout(go, delay);
      } catch (_) {
        window.location.href = url;
      }
    },
    ripple(evt) {
      try {
        const el = evt?.currentTarget;
        if (!el) return;
        const host = el.closest('.sb-ripple') || el;
        const rect = host.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height) * 1.6;
        const ink = document.createElement('span');
        ink.className = 'ripple-ink';
        ink.style.width = `${size}px`;
        ink.style.height = `${size}px`;
        const x = (evt.clientX ?? rect.left + rect.width / 2) - rect.left - size / 2;
        const y = (evt.clientY ?? rect.top + rect.height / 2) - rect.top - size / 2;
        ink.style.left = `${x}px`;
        ink.style.top = `${y}px`;
        host.appendChild(ink);
        ink.addEventListener('animationend', () => ink.remove(), { once: true });
      } catch (_) {}
    },
    rippleUnlessActive(evt) {
      try {
        const host = (evt?.currentTarget || evt?.target)?.closest('button, a, .sb-ripple');
        if (host?.classList?.contains('is-active')) return;
        this.ripple(evt);
      } catch (_) {
        this.ripple(evt);
      }
    },

    // ---------- Theme ----------
    applyStoredTheme() {
      try {
        if (localStorage.getItem('darkMode') !== null && localStorage.getItem('theme') === null) {
          const legacyDark = localStorage.getItem('darkMode') === 'true';
          localStorage.setItem('theme', legacyDark ? 'dark' : 'light');
          localStorage.removeItem('darkMode');
        }
      } catch (_) {}

      let stored = null;
      try {
        stored = localStorage.getItem('theme');
      } catch {
        stored = null;
      }

      if (stored === null) {
        try {
          localStorage.setItem('theme', 'light');
        } catch (_) {}
        document.documentElement.classList.remove('dark');
      } else if (stored === 'dark') {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
    },
    toggleTheme() {
      const isDark = document.documentElement.classList.toggle('dark');
      try {
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
      } catch (_) {}
    },

    // ---------- Mobile toggle ----------
    toggleSidebar() {
      if (this.isDesktop) return;
      this.fireSidebarCommand('toggle-sidebar');
      const store = getSidebarStore();
      if (this.sidebarOpen && store) {
        store.closeAll();
      }
    }
  };
};

// Initialize sidebar store ketika Alpine siap
withAlpine(initSidebarStore);

// Create safe store proxy to avoid "undefined" errors in sidebar x-data
// if store isn't ready yet
const safeStoreFallback = {
  openKey: null,
  animating: false,
  set: () => {},
  toggle: () => {},
  closeAll: () => {}
};

if (!window.safeStoreProxy) {
  window.safeStoreProxy = {
    get sb() {
      return getSidebarStore() || safeStoreFallback;
    }
  };
}

// Topbar menu Alpine helper (used by app layout)
window.topbarMenu = function topbarMenu() {
  return {
    notificationsOpen: false,
    theme: 'light',
    loading: false,
    init() {
      this.syncTheme();
    },
    syncTheme() {
      try {
        const stored = localStorage.getItem('theme');
        this.theme = stored === 'dark' ? 'dark' : 'light';
      } catch (_) {
        this.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
      }
    },
    toggleTheme() {
      try {
        this.$root?.dispatchEvent?.(new CustomEvent('toggle-theme'));
      } catch (_) {}
      this.syncTheme();
    },
    openDropdown(name) {
      if (name === 'notifications') {
        this.notificationsOpen = true;
      }
    },
    closeDropdowns() {
      this.notificationsOpen = false;
    }
  };
};

// Reset submenu state hanya saat navigasi selesai agar tidak memblokir klik awal
const closeSidebarAfterNavigate = () => {
  const store = getSidebarStore();
  if (store) store.closeAll();
};

document.addEventListener('livewire:navigated', closeSidebarAfterNavigate);
document.addEventListener('inertia:navigate', closeSidebarAfterNavigate);

// ---------------------------------------------------------
// Global: clear create drafts when logging out
// ---------------------------------------------------------
(() => {
  const clearCreateDrafts = () => {
    try {
      // Core drafts + wizard steps
      [
        'ticket:create:draft',
        'task:create:draft',
        'project:create:draft',
        'ticket:create:step',
        'task:create:step',
        'project:create:step'
      ].forEach(k => { try { localStorage.removeItem(k); } catch {} });

      // Attachment draft buckets
      [
        'ts:attachments:ticket-attachments',
        'ts:attachments:task-attachments',
        'ts:attachments:project-attachments'
      ].forEach(k => { try { localStorage.removeItem(k); } catch {} });

      // If attachment modules expose clear APIs, call them too
      if (window.attachmentDrafts) {
        try { Object.values(window.attachmentDrafts).forEach(api => api?.clear?.()); } catch {}
      }
    } catch (_) {}
  };

  // Hook: any form submit to a logout endpoint
  document.addEventListener('submit', (e) => {
    const form = e.target?.closest?.('form');
    if (!form) return;
    const action = String(form.getAttribute('action') || '').toLowerCase();
    if (!action) return;
    if (action.includes('/logout') || action.includes('account.logout')) {
      clearCreateDrafts();
    }
  }, true);

  // Hook: clicks on logout UI (sidebar button or Livewire wire:click="logout")
  document.addEventListener('click', (e) => {
    try {
      const btn = e.target?.closest?.('button, a');
      if (!btn) return;
      // Sidebar logout menu
      if (btn.closest('.nav-li--logout')) { clearCreateDrafts(); return; }
      // Livewire buttons like <button wire:click="logout">
      const wireClick = btn.getAttribute('wire:click') || btn.getAttribute('wire:click.prevent') || btn.getAttribute('wire:click.stop');
      if (wireClick && String(wireClick).toLowerCase().includes('logout')) { clearCreateDrafts(); return; }
    } catch (_) {}
  }, true);
})();

// Page modules
onAppMount(() => {
  try {
    initTicketCreate();
    initTaskCreate();
    initProjectCreate();
    const store = getSidebarStore();
    if (store) store.closeAll();
  } catch (_) {}
});
