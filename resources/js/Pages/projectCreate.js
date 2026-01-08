import { once, setupLeaveGuard, setupStepper } from '../lib/pageTools';

function initProjectDraft(form) {
  const KEY = 'project:create:draft';
  const hasOld = form.dataset.hasOld === '1';
  const wasSuccess = form.dataset.success === '1';
  const fields = ['#title','#description','#timeline_start','#timeline_end'];
  const names = ['ticket_id','project_no'];
  const save = () => { try {
    const data={}; for(const id of fields){ const el=form.querySelector(id); if(el) data[id.replace('#','')]=el.value||''; }
    for(const n of names){ const el=form.querySelector(`[name="${n}"]`); if(el) data[n]=el.value||''; }
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

export function initProjectCreate() {
  const form = document.getElementById('projectCreateForm');
  if (!form) return; if (!once(form, 'mod-init')) return;
  setupStepper(form, { storageKey: 'project:create:step' });
  setupLeaveGuard(form, {
    modalSel: '#leaveGuardModal', btnStaySel: '#leaveGuardCancel', btnLeaveSel: '#leaveGuardConfirm',
    clearDraftKeys: ['project:create:draft','project:create:step','ts:attachments:project-attachments'],
    activateOnSel: '[data-step="1"]',
    enableBeforeUnload: false,
  });
  initProjectDraft(form);
}
