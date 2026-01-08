<?php

$file = 'resources/views/pages/tasks/create.blade.php';
$content = file_get_contents($file);

// JavaScript handler
$js = '
  // Task Assignees - Sync between select, quick buttons, and display
  (function() {
    const selectEl = document.getElementById("taskAssigneesSelect");
    const quickBtnsContainer = document.getElementById("taskAssigneeQuickList");
    const pillsEl = document.getElementById("taskAssigneePills");
    const wrapEl = document.getElementById("taskAssigneesWrap");
    const inputEl = document.getElementById("assigneesInput");
    const quickBtns = document.querySelectorAll(".task-quick-btn");

    let selectedIds = [];

    // Parse initial value
    try {
      const jsonVal = inputEl.value;
      if (jsonVal && jsonVal !== "[]") {
        selectedIds = JSON.parse(jsonVal);
      }
    } catch (e) {
      selectedIds = [];
    }

    function updateUI() {
      // Update select element
      if (selectEl) {
        Array.from(selectEl.options).forEach(opt => {
          opt.selected = selectedIds.includes(parseInt(opt.value));
        });
      }

      // Update quick buttons
      quickBtns.forEach(btn => {
        const userId = parseInt(btn.dataset.userId);
        btn.dataset.selected = selectedIds.includes(userId) ? "true" : "false";
      });

      renderPills();
      renderCards();
    }

    function renderPills() {
      pillsEl.innerHTML = selectedIds.map(userId => {
        const btn = Array.from(quickBtns).find(b => parseInt(b.dataset.userId) === userId);
        const label = btn ? btn.dataset.userLabel : "User #" + userId;
        return `<div class="task-pill"><span>${label}</span><button type="button" class="btn-remove-pill" data-user-id="${userId}">✕</button></div>`;
      }).join("");

      pillsEl.querySelectorAll(".btn-remove-pill").forEach(btn => {
        btn.addEventListener("click", e => {
          e.preventDefault();
          const userId = parseInt(btn.dataset.userId);
          selectedIds = selectedIds.filter(id => id !== userId);
          updateUI();
        });
      });

      updateInput();
    }

    function renderCards() {
      wrapEl.innerHTML = selectedIds.map(userId => {
        const btn = Array.from(quickBtns).find(b => parseInt(b.dataset.userId) === userId);
        const label = btn ? btn.dataset.userLabel : "User #" + userId;
        return `
          <div class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/30">
            <div class="text-sm font-medium text-blue-900 dark:text-blue-100">${label}</div>
            <button type="button" class="btn-remove-card inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-100 dark:text-red-300 dark:hover:bg-red-900/30" data-user-id="${userId}">
              <span>Hapus</span>
            </button>
          </div>
        `;
      }).join("");

      wrapEl.querySelectorAll(".btn-remove-card").forEach(btn => {
        btn.addEventListener("click", e => {
          e.preventDefault();
          const userId = parseInt(btn.dataset.userId);
          selectedIds = selectedIds.filter(id => id !== userId);
          updateUI();
        });
      });
    }

    function updateInput() {
      inputEl.value = JSON.stringify(selectedIds);
    }

    function toggleUser(userId) {
      userId = parseInt(userId);
      const idx = selectedIds.indexOf(userId);
      if (idx >= 0) {
        selectedIds.splice(idx, 1);
      } else {
        selectedIds.push(userId);
      }
      updateUI();
    }

    // Select element change handler
    if (selectEl) {
      selectEl.addEventListener("change", () => {
        selectedIds = Array.from(selectEl.selectedOptions).map(opt => parseInt(opt.value));
        updateUI();
      });
    }

    // Quick buttons click handlers
    quickBtns.forEach(btn => {
      btn.addEventListener("click", e => {
        e.preventDefault();
        toggleUser(btn.dataset.userId);
      });
    });

    // Initial render
    updateUI();
  })();
';

// Find and replace @push("scripts") ... @endpush correctly
$old = '@push(\'scripts\')'."\n".'@endpush';
$new = '@push(\'scripts\')'."\n".$js."\n".'@endpush';

if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    echo "✅ JavaScript handler berhasil diinsert ke @push('scripts')\n";
} else {
    echo "❌ Tidak bisa menemukan @push('scripts') ... @endpush\n";
    exit(1);
}

file_put_contents($file, $content);
echo "✅ File sudah diupdate!\n";
