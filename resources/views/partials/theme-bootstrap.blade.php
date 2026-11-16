<script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce') }}">
  (function () {
    var context = window.tickoraTheme = @json(\App\Support\UserTheme::context(request()));
    function cacheKey() { return context.user_id ? 'tickora:theme:user:' + context.user_id : null; }
    function restoreTheme(event) {
      var root = document.documentElement;
      var key = cacheKey();
      if (event && event.persisted && key) {
        try {
          var cached = JSON.parse(localStorage.getItem(key));
          if (cached) context.preferences = cached;
        } catch (_) {}
      }
      var preferences = context.preferences || {};
      var dark = preferences.color_mode === 'dark';
      var appearance = preferences.appearance === 'glassy' ? 'glassy' : 'default';
      root.classList.toggle('dark', dark);
      root.classList.toggle('light', !dark);
      root.style.colorScheme = dark ? 'dark' : 'light';
      root.dataset.appearance = appearance;
      window.dispatchEvent(new CustomEvent('tickora:theme-updated', { detail: context }));
    }
    restoreTheme();
    window.addEventListener('pageshow', restoreTheme);
    window.addEventListener('storage', function (event) {
      var key = cacheKey();
      if (!key || event.key !== key || !event.newValue) return;
      try { context.preferences = JSON.parse(event.newValue); restoreTheme(); } catch (_) {}
    });
  })();
</script>
