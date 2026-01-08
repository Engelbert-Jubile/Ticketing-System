# Update Create Task - Assignees dengan Chips Display

## Perubahan HTML ✅ SELESAI
File: `resources/views/pages/tasks/create.blade.php`

HTML sudah diubah dari checkbox list menjadi:
- Multi-select dropdown (`<select id="taskAssigneesSelect" multiple>`)
- Quick list buttons (`.task-quick-btn`)
- Pills display area (`#taskAssigneePills`)

## CSS yang Perlu Ditambahkan

**Lokasi:** `resources/views/pages/tasks/create.blade.php` dalam `@push('styles')`

**Sebelum tag `</style>`, TAMBAHKAN:**

```css
  .task-assignee-picker{border:1px solid #d1d5db;border-radius:.5rem;padding:.5rem;background:#fff;font-size:.875rem;max-height:12rem;overflow-y:auto}
  .task-assignee-picker:focus{outline:none;border-color:#06b6d4;box-shadow:0 0 0 3px rgba(6,182,212,.1)}
  .task-assignee-picker option:checked{background:#0ea5e9 linear-gradient(#0ea5e9, #0ea5e9)}
  .dark .task-assignee-picker{background:#111827;border-color:#374151;color:#e5e7eb}
  .dark .task-assignee-picker:focus{border-color:#06b6d4}
  .dark .task-assignee-picker option{background:#1f2937}
  .dark .task-assignee-picker option:checked{background:#0ea5e9}

  .task-quick-list{display:flex;flex-wrap:wrap;gap:.5rem}
  .task-quick-btn{display:inline-block;padding:.5rem .75rem;border:1px dashed #d1d5db;border-radius:9999px;background:#fff;font-size:.75rem;font-weight:500;color:#374151;transition:all .2s ease;cursor:pointer}
  .task-quick-btn:hover{border-color:#0ea5e9;background:#ecf9ff;color:#0369a1}
  .task-quick-btn[aria-selected="true"]{border-style:solid;background:#0ea5e9;color:#fff;border-color:#0ea5e9}
  .dark .task-quick-btn{background:#1f2937;border-color:#4b5563;color:#d1d5db}
  .dark .task-quick-btn:hover{border-color:#06b6d4;background:#083344;color:#06b6d4}
  .dark .task-quick-btn[aria-selected="true"]{background:#0ea5e9;color:#fff;border-color:#0ea5e9}

  #taskAssigneePills{display:flex;flex-wrap:wrap;gap:.5rem}
  .task-pill{display:inline-flex;align-items:center;gap:.5rem;padding:.375rem .75rem;background:#e0e7ff;border-radius:9999px;font-size:.75rem;font-weight:500;color:#3730a3}
  .task-pill .btn-remove-pill{margin-left:.25rem;padding:0;border:none;background:none;cursor:pointer;font-size:.75rem;opacity:.7;transition:opacity .2s ease}
  .task-pill .btn-remove-pill:hover{opacity:1}
  .dark .task-pill{background:#3730a3;color:#e0e7ff}
```

## JavaScript yang Perlu Ditambahkan

**Lokasi:** `resources/views/pages/tasks/create.blade.php` sebelum `</script>` di akhir file

**TAMBAHKAN sebelum IIFE function `(function(){`:**

```javascript
// Task Assignees Multi-Select Handler
(function () {
  const selectEl = document.getElementById('taskAssigneesSelect');
  const quickListEl = document.getElementById('taskAssigneeQuickList');
  const pillsContainerEl = document.getElementById('taskAssigneePills');
  const assigneesInputEl = document.getElementById('assigneesInput');
  const quickBtns = document.querySelectorAll('.task-quick-btn');

  function updatePills() {
    const selectedOptions = Array.from(selectEl?.querySelectorAll('option:checked') || []);
    const selectedIds = selectedOptions.map(opt => opt.value);

    pillsContainerEl.innerHTML = selectedIds.map(id => {
      const option = Array.from(selectEl?.options || []).find(o => o.value === id);
      const label = option?.textContent || 'User';
      return `
        <div class="task-pill">
          <span>${label}</span>
          <button type="button" class="btn-remove-pill" data-user-id="${id}" aria-label="Hapus ${label}">✕</button>
        </div>
      `;
    }).join('');

    assigneesInputEl.value = JSON.stringify(selectedIds);

    // Update quick buttons
    quickBtns.forEach(btn => {
      const userId = btn.dataset.userId;
      const isSelected = selectedIds.includes(userId);
      btn.setAttribute('aria-selected', isSelected);
    });

    // Add remove listeners
    document.querySelectorAll('.btn-remove-pill').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        const userId = btn.dataset.userId;
        const option = Array.from(selectEl?.options || []).find(o => o.value === userId);
        if (option) {
          option.selected = false;
          updatePills();
        }
      });
    });
  }

  if (selectEl) {
    selectEl.addEventListener('change', updatePills);
  }

  quickBtns.forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const userId = btn.dataset.userId;
      const option = Array.from(selectEl?.options || []).find(o => o.value === userId);
      if (option) {
        option.selected = !option.selected;
        updatePills();
      }
    });
  });

  // Initial render
  updatePills();
})();
```

## Yang Sudah Diubah di HTML ✅

1. ✅ Multi-select dropdown dengan native `<select multiple>`
2. ✅ Quick buttons list untuk fast selection
3. ✅ Pills container untuk display selected users
4. ✅ Hidden input untuk store JSON array of assignee IDs
5. ✅ Removed old checkbox approach

## Flow Kerja

1. User membuka Create Task
2. Lihat dropdown "Daftar User" atau klik quick buttons
3. User memilih user (bisa multi-select via Ctrl/Shift atau klik tombol)
4. Chips/pills muncul di bawah dengan "✕" untuk remove
5. Hidden input `assignees` ter-update dengan JSON array
6. Form di-submit, controller terima `assignees` array

## Controller Sudah Siap ✅

Controller sudah update untuk handle `assignees[]` array:
- Validasi: `'assignees' => ['nullable', 'array'], 'assignees.*' => ['integer', 'exists:users,id']`
- Simpan assignee pertama sebagai `assignee_id`
- Notify semua assignees

## Dark Mode ✅

Semua CSS sudah support dark mode dengan `.dark` prefix.

## Testing Checklist

- [ ] Buka Create Task
- [ ] Lihat dropdown multi-select user
- [ ] Klik quick buttons - harusnya add/remove user
- [ ] Pilih via Ctrl/Shift di dropdown
- [ ] Chips muncul dan bisa dihapus dengan ✕
- [ ] Test dark mode - styling konsisten
- [ ] Submit form - user tersimpan dengan baik
