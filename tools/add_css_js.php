<?php

$file = 'resources/views/pages/tasks/create.blade.php';
$content = file_get_contents($file);

// Add CSS before </style>
$css = '
  .task-quick-list{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem}
  .task-quick-btn{padding:.5rem 1rem;border:1px dashed #d1d5db;border-radius:9999px;background:#fff;font-size:.875rem;font-weight:500;color:#374151;cursor:pointer;transition:all 200ms ease}
  .task-quick-btn:hover{border-color:#0ea5e9;background:#f0f9ff;color:#0369a1}
  .task-quick-btn[data-selected="true"]{border:1px solid #0ea5e9;background:#0ea5e9;color:#fff}
  .dark .task-quick-btn{background:#1f2937;border-color:#4b5563;color:#d1d5db}
  .dark .task-quick-btn:hover{border-color:#06b6d4;background:#083344;color:#06b6d4}
  .dark .task-quick-btn[data-selected="true"]{background:#0ea5e9;border-color:#0ea5e9;color:#fff}
  #taskAssigneePills{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem}
  .task-pill{display:inline-flex;align-items:center;gap:.5rem;padding:.375rem .75rem;background:#e0e7ff;border-radius:9999px;font-size:.875rem;font-weight:500;color:#3730a3}
  .task-pill .btn-remove-pill{margin-left:.25rem;padding:0;border:none;background:none;cursor:pointer;opacity:.7;transition:opacity 200ms ease;color:inherit}
  .task-pill .btn-remove-pill:hover{opacity:1}
  .dark .task-pill{background:#3730a3;color:#e0e7ff}
';

$content = str_replace('</style>', $css.PHP_EOL.'</style>', $content);

// Add JavaScript after @push('scripts')
$js = '<script>
  // Task Assignees Multi-Select Handler
  (function() {
    const quickListEl = document.getElementById(\'taskAssigneeQuickList\');
    const pillsEl = document.getElementById(\'taskAssigneePills\');
    const inputEl = document.getElementById(\'assigneesInput\');
    const quickBtns = document.querySelectorAll(\'.task-quick-btn\');

    let selectedIds = [];
    try {
      const jsonVal = inputEl.value;
      if (jsonVal && jsonVal !== \'[]\') {
        selectedIds = JSON.parse(jsonVal);
      }
    } catch (e) {
      selectedIds = [];
    }

    function renderPills() {
      pillsEl.innerHTML = selectedIds.map(userId => {
        const btn = Array.from(quickBtns).find(b => b.dataset.userId === String(userId));
        const label = btn ? btn.dataset.userLabel : \'User #\' + userId;
        return `<div class="task-pill"><span>${label}</span><button type="button" class="btn-remove-pill" data-user-id="${userId}">✕</button></div>`;
      }).join(\'\');

      pillsEl.querySelectorAll(\'.btn-remove-pill\').forEach(btn => {
        btn.addEventListener(\'click\', e => {
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
        const isSelected = selectedIds.some(id => String(id) === String(userId));
        btn.dataset.selected = isSelected ? \'true\' : \'false\';
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

    quickBtns.forEach(btn => {
      btn.addEventListener(\'click\', e => {
        e.preventDefault();
        toggleUser(btn.dataset.userId);
      });
    });

    updateUI();
  })();
</script>';

$content = str_replace('@push(\'scripts\')\n@endpush', '@push(\'scripts\')\n'.$js.'\n@endpush', $content);

file_put_contents($file, $content);

echo "✅ CSS dan JavaScript sudah ditambahkan ke file!\n";
echo "Sekarang hard refresh browser dengan Ctrl+Shift+R\n";
