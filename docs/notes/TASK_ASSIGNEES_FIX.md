# Fix Task Assignees - Perbaikan Styling & JavaScript

## Masalah yang Diperbaiki
1. ✅ Tampilan dropdown yang jelek - dihapus, ganti dengan button pills
2. ✅ Tombol user tidak bisa diklik - sekarang fully functional
3. ✅ Chips display lebih bagus dengan styling yang proper

## HTML ✅ Sudah Diupdate
File: `resources/views/pages/tasks/create.blade.php` (line ~205-225)

Sekarang menggunakan:
- Quick buttons yang besar (persis seperti Create Project)
- Pills/chips display di bawah
- Hidden input untuk store JSON array

---

## LANGKAH 1: Update CSS

**File:** `resources/views/pages/tasks/create.blade.php`

**Cari:** `@push('styles')` (sekitar line 326)

**Sebelum tag `</style>`, REPLACE CSS yang lama dengan:**

```css
  /* Task Assignees - Quick Buttons */
  .task-quick-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .task-quick-btn {
    padding: 0.5rem 1rem;
    border: 1px dashed #d1d5db;
    border-radius: 9999px;
    background-color: white;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: all 200ms ease;
  }

  .task-quick-btn:hover {
    border-color: #0ea5e9;
    background-color: #f0f9ff;
    color: #0369a1;
  }

  .task-quick-btn[data-selected="true"] {
    border: 1px solid #0ea5e9;
    background-color: #0ea5e9;
    color: white;
  }

  /* Dark Mode */
  .dark .task-quick-btn {
    background-color: #1f2937;
    border-color: #4b5563;
    color: #d1d5db;
  }

  .dark .task-quick-btn:hover {
    border-color: #06b6d4;
    background-color: #083344;
    color: #06b6d4;
  }

  .dark .task-quick-btn[data-selected="true"] {
    background-color: #0ea5e9;
    border-color: #0ea5e9;
    color: white;
  }

  /* Pills Display */
  #taskAssigneePills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  .task-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    background-color: #e0e7ff;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #3730a3;
  }

  .task-pill .btn-remove-pill {
    margin-left: 0.25rem;
    padding: 0;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 0.875rem;
    opacity: 0.7;
    transition: opacity 200ms ease;
    color: inherit;
  }

  .task-pill .btn-remove-pill:hover {
    opacity: 1;
  }

  /* Dark Mode Pills */
  .dark .task-pill {
    background-color: #3730a3;
    color: #e0e7ff;
  }
```

---

## LANGKAH 2: Update JavaScript

**File:** `resources/views/pages/tasks/create.blade.php`

**Cari:** di akhir file sebelum `</script>` (sekitar line 380+)

**TAMBAHKAN SEBELUM** yang sudah ada:

```javascript
// Task Assignees Handler
(function() {
  const quickListEl = document.getElementById('taskAssigneeQuickList');
  const pillsEl = document.getElementById('taskAssigneePills');
  const inputEl = document.getElementById('assigneesInput');
  const quickBtns = document.querySelectorAll('.task-quick-btn');

  // Parse initial values
  let selectedIds = [];
  try {
    const jsonVal = inputEl.value;
    if (jsonVal) {
      selectedIds = JSON.parse(jsonVal);
    }
  } catch (e) {
    selectedIds = [];
  }

  function renderPills() {
    pillsEl.innerHTML = selectedIds.map(userId => {
      const btn = Array.from(quickBtns).find(b => b.dataset.userId === String(userId));
      const label = btn ? btn.dataset.userLabel : 'User #' + userId;
      return `
        <div class="task-pill">
          <span>${label}</span>
          <button type="button" class="btn-remove-pill" data-user-id="${userId}">✕</button>
        </div>
      `;
    }).join('');

    // Attach remove listeners
    pillsEl.querySelectorAll('.btn-remove-pill').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        const userId = btn.dataset.userId;
        selectedIds = selectedIds.filter(id => String(id) !== String(userId));
        updateUI();
      });
    });

    updateInput();
  }

  function updateUI() {
    quickBtns.forEach(btn => {
      const userId = btn.dataset.userId;
      const isSelected = selectedIds.includes(userId) || selectedIds.includes(parseInt(userId));
      btn.dataset.selected = isSelected ? 'true' : 'false';
    });
    renderPills();
  }

  function updateInput() {
    inputEl.value = JSON.stringify(selectedIds);
  }

  function toggleUser(userId) {
    const userIdStr = String(userId);
    const idx = selectedIds.findIndex(id => String(id) === userIdStr);
    if (idx >= 0) {
      selectedIds.splice(idx, 1);
    } else {
      selectedIds.push(userId);
    }
    updateUI();
  }

  // Attach click handlers
  quickBtns.forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      toggleUser(btn.dataset.userId);
    });
  });

  // Initial render
  updateUI();
})();
```

---

## LANGKAH 3: Hard Refresh Browser

```
Ctrl+Shift+R (Windows)
Cmd+Shift+R (Mac)
```

---

## Testing

1. ✅ Klik tombol user - harus berubah warna biru
2. ✅ Chips muncul di bawah dengan nama user
3. ✅ Klik ✕ pada chip - hapus user
4. ✅ Klik tombol lagi - remove dari selection
5. ✅ Dark mode - styling consistent
6. ✅ Submit form - assignees ter-save

---

## Catatan Penting

- `data-selected="true"` di button = user sudah dipilih
- JSON array di hidden input `assignees` di-parse dan disimpan
- Semua ID di-convert ke string untuk konsistensi
- Dark mode support lengkap
