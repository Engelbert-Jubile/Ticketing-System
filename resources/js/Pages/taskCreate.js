import { once, setupLeaveGuard, setupStepper } from '../lib/pageTools';

function initTaskDraft(form) {
  const KEY = 'task:create:draft';
  const hasOld = form.dataset.hasOld === '1';
  const wasSuccess = form.dataset.success === '1';
  const fields = ['#title','#description','#due_at'];
  const names = ['priority','assignee_id','assigned_to','ticket_id','output_type'];
  const save = () => { try {
    const data={}; for (const id of fields){ const el=form.querySelector(id); if(el) data[id.replace('#','')]=el.value||''; }
    for (const n of names){ const el=form.querySelector(`[name="${n}"]`); if(el) data[n]=el.value||''; }
    localStorage.setItem(KEY, JSON.stringify(data)); } catch{} };
  const load = () => { try { const raw=localStorage.getItem(KEY); return raw?JSON.parse(raw):null; } catch { return null; } };
  const clear = () => { try { localStorage.removeItem(KEY); } catch {} };
  if (wasSuccess) clear();
  if (!hasOld) {
    const data = load();
    if (data) {
      const set=(sel,val)=>{ const el=form.querySelector(sel); if(el && (val??'')!=='') el.value=val; };
      for (const id of fields) set(id, data[id.replace('#','')]);
      for (const n of names) set(`[name="${n}"]`, data[n]);
    }
  }
  form.addEventListener('input', save, true);
  form.addEventListener('change', save, true);
}

export function initTaskCreate() {
  const form = document.getElementById('taskCreateForm');
  if (!form) return; if (!once(form, 'mod-init')) return;
  setupStepper(form, { storageKey: 'task:create:step' });
  setupLeaveGuard(form, {
    modalSel: '#leaveGuardModal', btnStaySel: '#leaveGuardCancel', btnLeaveSel: '#leaveGuardConfirm',
    clearDraftKeys: ['task:create:draft','task:create:step','ts:attachments:task-attachments'],
    activateOnSel: '[data-step="1"]',
    enableBeforeUnload: false,
  });
  initTaskDraft(form);
}
