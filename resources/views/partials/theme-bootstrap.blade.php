<script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce') }}">
  (function () {
    function restoreTheme() {
      var root = document.documentElement;
      var dark = false;
      var appearance = 'default';
      try {
        dark = localStorage.getItem('theme') === 'dark';
        appearance = localStorage.getItem('tickora:appearance') === 'glassy' ? 'glassy' : 'default';
      } catch (_) {}
      root.classList.toggle('dark', dark);
      root.classList.toggle('light', !dark);
      root.style.colorScheme = dark ? 'dark' : 'light';
      root.dataset.appearance = appearance;
    }
    restoreTheme();
    window.addEventListener('pageshow', restoreTheme);
    window.addEventListener('storage', function (event) {
      if (event.key === 'theme' || event.key === 'tickora:appearance' || event.key === null) restoreTheme();
    });
  })();
</script>
