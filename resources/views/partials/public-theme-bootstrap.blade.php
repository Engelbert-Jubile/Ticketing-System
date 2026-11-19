<script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce') }}">
  (function () {
    // Public pages always use the default light appearance.
    var context = window.tickoraTheme = { user_id: null, preferences: { appearance: 'default', color_mode: 'light' } };
    function applyPublicTheme() {
      context.preferences = { appearance: 'default', color_mode: 'light' };
      var root = document.documentElement;
      var dark = context.preferences.color_mode === 'dark';
      root.dataset.appearance = context.preferences.appearance;
      root.classList.toggle('dark', dark);
      root.classList.toggle('light', !dark);
      root.style.colorScheme = dark ? 'dark' : 'light';
      window.dispatchEvent(new CustomEvent('tickora:theme-updated', { detail: context }));
    };
    applyPublicTheme();
    window.addEventListener('pageshow', applyPublicTheme);
  })();
</script>
