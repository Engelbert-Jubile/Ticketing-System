// resources/js/script.js

document.addEventListener('DOMContentLoaded', () => {
  console.log('Custom script loaded!');

  // 1) WELCOME TEXT INTERACTION
  const welcomeText = document.querySelector('.welcome-text');
  if (welcomeText) {
    welcomeText.addEventListener('click', () => {
      alert('Selamat datang di Ticketing System!');
    });
  }

  // 2) SIDEBAR OFF-CANVAS + OVERLAY
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const btnOpen = document.getElementById('btn-open-sidebar');

  // Focus-trap variables
  let focusableElements = [];
  let firstFocusable, lastFocusable;

  function saveFocusableElements() {
    focusableElements = sidebar.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
    if (focusableElements.length > 0) {
      firstFocusable = focusableElements[0];
      lastFocusable = focusableElements[focusableElements.length - 1];
    }
  }

  function handleKeyDown(e) {
    if (e.key === 'Tab') {
      // SHIFT + TAB
      if (e.shiftKey && document.activeElement === firstFocusable) {
        e.preventDefault();
        lastFocusable.focus();
      }
      // TAB
      else if (!e.shiftKey && document.activeElement === lastFocusable) {
        e.preventDefault();
        firstFocusable.focus();
      }
    }
    // ESC to close
    if (e.key === 'Escape') {
      closeSidebar();
    }
  }

  function openSidebar() {
    sidebar.classList.add('translate-x-0');
    overlay.classList.add('opacity-100', 'pointer-events-auto');
    sidebar.setAttribute('aria-hidden', 'false');

    saveFocusableElements();
    if (firstFocusable) firstFocusable.focus();
    document.addEventListener('keydown', handleKeyDown);
  }

  function closeSidebar() {
    sidebar.classList.remove('translate-x-0');
    overlay.classList.remove('opacity-100', 'pointer-events-auto');
    sidebar.setAttribute('aria-hidden', 'true');

    document.removeEventListener('keydown', handleKeyDown);
    btnOpen.focus();
  }

  if (btnOpen) btnOpen.addEventListener('click', openSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);

  // 3) DARK MODE TOGGLE (persistent)
  const btnTheme = document.getElementById('btn-toggle-theme');

  function loadTheme() {
    const dark = localStorage.getItem('darkMode') === 'true';
    document.documentElement.classList.toggle('dark', dark);
    btnTheme.textContent = dark ? '☀️ Light Mode' : '🌙 Dark Mode';
  }

  function toggleTheme() {
    const nowDark = !document.documentElement.classList.contains('dark');
    document.documentElement.classList.toggle('dark', nowDark);
    localStorage.setItem('darkMode', nowDark);
    btnTheme.textContent = nowDark ? '☀️ Light Mode' : '🌙 Dark Mode';
  }

  if (btnTheme) btnTheme.addEventListener('click', toggleTheme);
  loadTheme();
});
