// resources/js/lib/pageTools.js
export function once(el, key = 'init') {
  if (!el) return false;
  const k = `data-${key}`;
  if (el.hasAttribute(k)) return false;
  el.setAttribute(k, '1');
  return true;
}

export function setupStepper(root, { sectionSel = '[data-step]', labelSel = '[data-step-label]', storageKey } = {}) {
  if (!root) return;
  const sections = Array.from(root.querySelectorAll(sectionSel));
  const labels = Array.from(document.querySelectorAll(labelSel));
  const max = sections.length;
  if (max === 0) return;

  const getSaved = () => {
    if (!storageKey) return 1;
    try { return Math.max(1, Math.min(max, parseInt(localStorage.getItem(storageKey) || '1', 10))); } catch { return 1; }
  };
  const save = (n) => {
    if (!storageKey) return;
    try { localStorage.setItem(storageKey, String(n)); } catch {}
  };

  let current = getSaved();
  const show = (n) => {
    const s = Math.max(1, Math.min(max, Number(n) || 1));
    sections.forEach(sec => sec.classList.toggle('hidden', String(sec.getAttribute('data-step')) !== String(s)));
    labels.forEach(lb => lb.classList.toggle('is-active', String(lb.getAttribute('data-step-label')) === String(s)));
    current = s;
    save(current);
  };

  const validateCurrent = () => {
    const currentSection = sections.find(sec => String(sec.getAttribute('data-step')) === String(current));
    if (!currentSection) return true;
    const requiredEls = Array.from(currentSection.querySelectorAll('input[required], select[required], textarea[required]'));
    // Grouped validation for radios/checkboxes
    const isGroupValid = (el) => {
      const type = (el.getAttribute('type') || '').toLowerCase();
      const name = el.getAttribute('name') || '';
      if (!name) return !!(el.value || '').trim();
      if (type === 'radio' || type === 'checkbox') {
        const group = currentSection.querySelectorAll(`input[type="${type}"][name="${name.replace(/"/g, '\\"')}"]`);
        return Array.from(group).some(e => e.checked);
      }
      if (el.tagName === 'SELECT') {
        return (el.value || '').trim() !== '';
      }
      return (el.value || '').trim() !== '';
    };

    for (const el of requiredEls) {
      if (!isGroupValid(el)) {
        try { el.reportValidity?.(); } catch {}
        el.focus?.();
        return false;
      }
    }
    return true;
  };

  root.querySelectorAll('[data-next-step]').forEach(b => b.addEventListener('click', (e) => {
    const target = (Number(b.getAttribute('data-next-step')) || current + 1);
    if (target > current) {
      if (!validateCurrent()) {
        e.preventDefault();
        e.stopPropagation();
        return;
      }
    }
    show(target);
  }));
  root.querySelectorAll('[data-prev-step]').forEach(b => b.addEventListener('click', () => show((Number(b.getAttribute('data-prev-step')) || current - 1))));
  show(current);
}

export function setupLeaveGuard(root, {
  modalSel = '#leaveGuardModal',
  btnStaySel = '#leaveGuardCancel',
  btnLeaveSel = '#leaveGuardConfirm',
  cancelAttr = 'data-cancel',
  clearDraftKeys = [],
  clearAttachments = true,
} = {}) {
  if (!root) return;
  const modal = document.querySelector(modalSel);
  const btnStay = document.querySelector(btnStaySel);
  const btnLeave = document.querySelector(btnLeaveSel);

  let isDirty = false, userInteracted = false, pendingHref = null, pendingForm = null;
  const markDirty = () => { if (userInteracted) isDirty = true; };

  let overrideTitle = null;
  const openModal = (target) => {
    // target can be href string or a form element
    pendingHref = (typeof target === 'string' ? target : null) || null;
    pendingForm = (target && target.tagName === 'FORM') ? target : null;
    if (!modal) { if (pendingHref) location.href = pendingHref; return; }
    try {
      if (overrideTitle) {
        const h = modal.querySelector('h3');
        if (h) {
          if (!modal.dataset.origTitle) modal.dataset.origTitle = h.textContent || '';
          h.textContent = overrideTitle;
        }
      }
    } catch(_){}
    modal.classList.remove('hidden');
    requestAnimationFrame(() => modal.classList.add('modal-show'));
  };
  const closeModal = () => {
    pendingHref = null;
    pendingForm = null;
    if (!modal) return;
    modal.classList.remove('modal-show');
    try {
      if (modal.dataset && modal.dataset.origTitle) {
        const h = modal.querySelector('h3');
        if (h) h.textContent = modal.dataset.origTitle;
        delete modal.dataset.origTitle;
      }
      overrideTitle = null;
    } catch(_){}
    setTimeout(() => { if (!modal.classList.contains('modal-show')) modal.classList.add('hidden'); }, 180);
  };

  btnStay?.addEventListener('click', (e) => { e.preventDefault(); closeModal(); });
  btnLeave?.addEventListener('click', (e) => {
    e.preventDefault();
    const go = pendingHref || root.querySelector('[data-cancel]')?.getAttribute('data-cancel');
    // Clear local drafts before leaving
    try {
      clearDraftKeys.forEach(k => { try { localStorage.removeItem(k); } catch {} });
      if (clearAttachments && window.attachmentDrafts) {
        Object.values(window.attachmentDrafts).forEach(api => { try { api?.clear?.(); } catch {} });
      }
    } catch {}
    closeModal();
    if (pendingForm) { try { pendingForm.submit(); } catch {} return; }
    if (go) location.href = go;
  });

  root.addEventListener('input', () => { userInteracted = true; markDirty(); }, true);
  root.addEventListener('change', () => { userInteracted = true; markDirty(); }, true);

  // Allow other modules to flag dirty programmatically (e.g., attachments upload)
  try {
    document.addEventListener('create:dirty', () => { userInteracted = true; isDirty = true; }, true);
  } catch (_) {}

  root.addEventListener('click', (e) => {
    // Handle explicit cancel buttons
    const cancelBtn = e.target.closest(`[${cancelAttr}]`);
    if (cancelBtn) {
      const url = cancelBtn.getAttribute(cancelAttr) || '';
      e.preventDefault();
      e.stopPropagation();
      // Always show confirmation modal on cancel to reset drafts/step
      try { overrideTitle = cancelBtn.getAttribute('data-confirm-title') || overrideTitle; } catch(_){}
      openModal(url);
      return;
    }
    // Handle anchor navigations
    const a = e.target.closest('a');
    if (!a) return;
    if (a.closest(modalSel)) return;
    const href = a.getAttribute('href') || '';
    if (!href || href.startsWith('#') || a.target === '_blank') return;
    if (!isDirty) return;
    e.preventDefault();
    e.stopPropagation();
    openModal(a.href);
  }, true);

  // Also capture clicks anywhere in the document (sidebar/topbar links)
  document.addEventListener('click', (e) => {
    try {
      const a = e.target.closest('a');
      if (a) {
        if (a.closest(modalSel)) return;
        const href = a.getAttribute('href') || '';
        if (!href || href.startsWith('#') || a.target === '_blank') return;
        if (!isDirty) return;
        e.preventDefault();
        e.stopPropagation();
        openModal(a.href);
        return;
      }
      // Intercept logout form button clicks
      const btn = e.target.closest('button');
      const form = btn?.closest('form');
      const action = String(form?.getAttribute('action') || '').toLowerCase();
      if (form && action && (action.includes('/logout') || action.includes('account.logout'))) {
        if (!isDirty) return; // allow immediate logout if not dirty
        e.preventDefault();
        e.stopPropagation();
        openModal(form);
        return;
      }
    } catch (_) {}
  }, true);

  // Intercept Livewire SPA navigation if present
  try {
    if (window.Livewire && typeof window.Livewire.navigate === 'function' && !window.Livewire.__navGuardPatched) {
      const _origNav = window.Livewire.navigate.bind(window.Livewire);
      window.Livewire.navigate = (url, ...rest) => {
        if (isDirty) { openModal(url); return; }
        return _origNav(url, ...rest);
      };
      window.Livewire.__navGuardPatched = true;
    }
  } catch (_) {}

  // Intercept programmatic navigation: location.assign/replace
  try {
    if (!window.__locGuardPatched) {
      const _assign = window.location.assign.bind(window.location);
      const _replace = window.location.replace.bind(window.location);
      window.location.assign = (url) => { if (isDirty) { openModal(url); return; } _assign(url); };
      window.location.replace = (url) => { if (isDirty) { openModal(url); return; } _replace(url); };
      window.__locGuardPatched = true;
    }
  } catch (_) {}

  // Intercept history API pushes (SPA frameworks)
  try {
    if (!window.__histGuardPatched) {
      const _push = history.pushState.bind(history);
      const _rep = history.replaceState.bind(history);
      history.pushState = (state, title, url) => { if (isDirty && url) { openModal(String(url)); return; } _push(state, title, url); };
      history.replaceState = (state, title, url) => { if (isDirty && url) { openModal(String(url)); return; } _rep(state, title, url); };
      window.__histGuardPatched = true;
    }
  } catch (_) {}

  // Note: native beforeunload prompt is disabled to avoid double popups

  return { markDirty, setDirty: (v) => { isDirty = !!v; }, interacted: () => { userInteracted = true; } };
}
