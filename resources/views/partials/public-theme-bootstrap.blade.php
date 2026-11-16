<script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce') }}">
  (function () {
    // Public appearance is temporary and never reads account or browser preferences.
    var context = window.tickoraTheme = { user_id: null, preferences: { appearance: 'default', color_mode: 'light' } };
    window.setPublicTheme = function (preferences) {
      context.preferences = {
        appearance: preferences.appearance === 'glassy' ? 'glassy' : 'default',
        color_mode: preferences.color_mode === 'dark' ? 'dark' : 'light'
      };
      var root = document.documentElement;
      var dark = context.preferences.color_mode === 'dark';
      root.dataset.appearance = context.preferences.appearance;
      root.classList.toggle('dark', dark);
      root.classList.toggle('light', !dark);
      root.style.colorScheme = dark ? 'dark' : 'light';
      window.dispatchEvent(new CustomEvent('tickora:theme-updated', { detail: context }));
    };
    window.setPublicTheme(context.preferences);
    window.addEventListener('pageshow', function () { window.setPublicTheme(context.preferences); });
  })();
</script>
