import { once, setupLeaveGuard, setupStepper } from '../lib/pageTools';

function toISODate(dmy) {
  if (!dmy) return '';
  const m = String(dmy).trim().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
  if (!m) return '';
  return `${m[3]}-${m[2]}-${m[1]}`;
}

function composeDateTime(dateStr, timeStr) {
  const d = toISODate(dateStr);
  if (!d) return '';
  const t = /^\d{2}:\d{2}$/.test(timeStr || '') ? timeStr : '00:00';
  return `${d} ${t}:00`;
}

function initTimeChips(form) {
  form.querySelectorAll('.tf-btn').forEach(btn => btn.addEventListener('click', () => {
    const sel = btn.getAttribute('data-target');
    const el = sel && form.querySelector(sel);
    // try open flatpickr time widget; fallback focus
    if (el?._flatpickr) el._flatpickr.open(); else el?.focus();
  }));

  form.querySelectorAll('.time-chip').forEach(chip => chip.addEventListener('click', () => {
    const trg = chip.getAttribute('data-target');
    const val = chip.getAttribute('data-time');
    if (!trg || !val) return;
    const el = form.querySelector(trg);
    if (!el) return;
    if (val === 'now') {
      const now = new Date();
      el.value = `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
    } else if (val === 'copy-due') {
      const due = form.querySelector('#due_time');
      if (due) el.value = due.value || '';
    } else {
      el.value = val;
    }
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }));
}

function initDraft(form) {
  const KEY = 'ticket:create:draft';
  const hasOld = form.dataset.hasOld === '1';
  const wasSuccess = form.dataset.success === '1';

  const fields = [
    '#title', '#description', '#due_date', '#due_time', '#finish_date', '#finish_time'
  ];
  const names = [
    'priority','type','sla','requester_id','agent_id','reason','letter_no'
  ];

  const save = () => {
    try {
      const data = {};
      for (const id of fields) {
        const el = form.querySelector(id);
        if (el) data[id.replace('#','')] = el.value || '';
      }
      for (const n of names) {
        const el = form.querySelector(`[name="${n}"]`);
        if (el) data[n] = el.value || '';
      }
      localStorage.setItem(KEY, JSON.stringify(data));
    } catch {}
  };

  const load = () => {
    try {
      const raw = localStorage.getItem(KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch { return null; }
  };

  const clear = () => { try { localStorage.removeItem(KEY); } catch {} };
  if (wasSuccess) clear();

  // Restore only if no server old values
  if (!hasOld) {
    const data = load();
    if (data) {
      const set = (sel, val) => { const el = form.querySelector(sel); if (el && (val ?? '') !== '') el.value = val; };
      for (const id of fields) set(id, data[id.replace('#','')]);
      for (const n of names) set(`[name="${n}"]`, data[n]);
    }
  }

  const onDirty = () => save();
  form.addEventListener('input', onDirty, true);
  form.addEventListener('change', onDirty, true);

  return { save, clear };
}

function initQuill(form) {
  const wrapper = document.getElementById('descWrap');
  const editor = document.getElementById('description_editor');
  const hidden = document.getElementById('description');
  const counter = document.getElementById('descCounter');
  if (!editor || !hidden || !window.Quill) return;
  if (editor.__quill) return;
  const quill = new window.Quill(editor, {
    theme: 'snow',
    placeholder: 'Tulis detail tiket di sini...',
    modules: {
      toolbar: [[{ header: [1,2,3,false] }], ['bold','italic','underline','strike','blockquote'], [{ align: [] }], [{ list:'ordered' },{ list:'bullet' },{ indent:'-1' },{ indent:'+1' }], ['link','code-block','clean']]
    }
  });
  editor.__quill = quill;
  if ((hidden.value || '').trim() && !editor.dataset.initialPasted) {
    try { quill.clipboard.dangerouslyPasteHTML(hidden.value); } catch {}
    editor.dataset.initialPasted = '1';
  }
  const sync = () => {
    hidden.value = quill.root.innerHTML;
    if (counter) {
      const words = (quill.getText().trim().split(/\s+/).filter(Boolean).length) || 0;
      counter.textContent = `${words} kata`;
    }
  };
  quill.on('text-change', sync);
  quill.on('selection-change', r => wrapper?.classList.toggle('is-focused', !!r));
}

function initAssignees(form) {
  const grid = form.querySelector('#assignee_options');
  const select = form.querySelector('#assignee_picker');
  const list = form.querySelector('#assignee_list');
  if (!grid || !select || !list) return;

  const getLabelById = (id) => {
    const btn = grid.querySelector(`.assignee-option[data-user-id="${id}"]`);
    const txt = btn?.querySelector('.assignee-option-label')?.textContent || '';
    return txt.trim();
  };

  const markGrid = (id, on) => {
    const btn = grid.querySelector(`.assignee-option[data-user-id="${id}"]`);
    if (!btn) return;
    btn.classList.toggle('is-selected', !!on);
    btn.setAttribute('aria-selected', on ? 'true' : 'false');
  };

  const markSelect = (id, on) => {
    Array.from(select.options).forEach(opt => {
      if (String(opt.value) === String(id)) opt.selected = !!on;
    });
  };

  const selected = new Set();

  const addChip = (id, label) => {
    const key = String(id);
    if (selected.has(key)) return;
    selected.add(key);
    const span = document.createElement('span');
    span.className = 'assignee-chip';
    span.dataset.userId = key;
    span.innerHTML = `
      <span class="assignee-chip-label">${label}</span>
      <button type="button" class="remove-assignee" aria-label="Hapus ${label}">&times;</button>
      <input type="hidden" name="assigned_user_ids[]" value="${key}">
    `;
    list.appendChild(span);
    markGrid(id, true);
    markSelect(id, true);
  };

  const removeChip = (id) => {
    const key = String(id);
    if (!selected.has(key)) return;
    selected.delete(key);
    markGrid(id, false);
    markSelect(id, false);
    Array.from(list.querySelectorAll('.assignee-chip')).forEach(ch => {
      if (String(ch.dataset.userId) === key) ch.remove();
    });
  };

  // Initialize from existing chips or select
  Array.from(list.querySelectorAll('input[name="assigned_user_ids[]"]')).forEach(inp => {
    const id = inp.value;
    const label = getLabelById(id) || 'User';
    selected.add(String(id));
    markGrid(id, true);
    markSelect(id, true);
  });
  Array.from(select.selectedOptions || []).forEach(opt => {
    const id = opt.value;
    if (!selected.has(String(id))) addChip(id, opt.textContent?.trim() || 'User');
  });

  // Grid button click -> toggle selection
  // no separate add button; clicks on grid or select change handle adding

  grid.addEventListener('click', (e) => {
    const btn = e.target.closest('.assignee-option');
    if (!btn) return;
    const id = btn.getAttribute('data-user-id');
    const label = btn.querySelector('.assignee-option-label')?.textContent?.trim() || 'User';
    if (!id) return;
    if (selected.has(String(id))) {
      removeChip(id);
    } else {
      addChip(id, label);
    }
  });

  // Select change -> sync chips and grid
  select.addEventListener('change', () => {
    const ids = new Set(Array.from(select.selectedOptions).map(opt => String(opt.value)));
    // remove chips not in select
    Array.from(selected).forEach(id => { if (!ids.has(id)) removeChip(id); });
    // add chips for new selections
    ids.forEach(id => { if (!selected.has(id)) addChip(id, getLabelById(id) || 'User'); });
  });

  // Remove chip via button
  list.addEventListener('click', (e) => {
    const rm = e.target.closest('.remove-assignee');
    if (!rm) return;
    const chip = rm.closest('.assignee-chip');
    const id = chip?.dataset?.userId;
    if (id) removeChip(id);
  });
}

export function initTicketCreate() {
  const form = document.getElementById('ticketCreateForm');
  if (!form) return;
  if (!once(form, 'mod-init')) return;

  setupStepper(form, { storageKey: 'ticket:create:step' });
  setupLeaveGuard(form, {
    modalSel: '#leaveGuardModal', btnStaySel: '#leaveGuardCancel', btnLeaveSel: '#leaveGuardConfirm',
    clearDraftKeys: ['ticket:create:draft','ticket:create:step','ts:attachments:ticket-attachments'],
    activateOnSel: '[data-step="1"]',
    enableBeforeUnload: false,
  });
  initQuill(form);
  initTimeChips(form);
  initAssignees(form);
  const draft = initDraft(form);

  // Compose hidden datetime before submit
  form.addEventListener('submit', () => {
    const dueAt = composeDateTime(form.querySelector('#due_date')?.value, form.querySelector('#due_time')?.value);
    const finishAt = composeDateTime(form.querySelector('#finish_date')?.value, form.querySelector('#finish_time')?.value);
    const dueAtEl = form.querySelector('#due_at'); if (dueAtEl) dueAtEl.value = dueAt;
    const finAtEl = form.querySelector('#finish_at'); if (finAtEl) finAtEl.value = finishAt;
  }, { capture: true });

  // Shortcut save
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === 's') {
      e.preventDefault();
      form.requestSubmit?.();
    }
  });

  // After successful save (server sets data-success="1"), clear drafts and hard-reset wizard to step 1
  try {
    if (form && form.dataset && form.dataset.success === '1') {
      const ATTACH_KEY = 'ts:attachments:ticket-attachments';
      localStorage.removeItem('ticket:create:draft');
      localStorage.removeItem('ticket:create:step');
      localStorage.removeItem(ATTACH_KEY);
      if (window.attachmentDrafts && window.attachmentDrafts[ATTACH_KEY]) {
        try { window.attachmentDrafts[ATTACH_KEY].clear(); } catch(_){}
      }
      // Force show step 1 immediately
      try {
        const sections = Array.from(form.querySelectorAll('[data-step]'));
        sections.forEach(sec => sec.classList.toggle('hidden', String(sec.getAttribute('data-step')) !== '1'));
        const labels = Array.from(document.querySelectorAll('[data-step-label]'));
        labels.forEach(lb => lb.classList.toggle('is-active', String(lb.getAttribute('data-step-label')) === '1'));
      } catch(_){}
      // Optional: smooth scroll to top of form
      try { form.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch(_){}
    }
  } catch(_){}
}
