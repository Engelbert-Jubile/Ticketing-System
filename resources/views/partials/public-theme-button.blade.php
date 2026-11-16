<div class="public-theme-picker" id="public-theme-picker">
  <button type="button" class="public-theme-trigger" id="public-theme-trigger" aria-expanded="false" aria-controls="public-theme-panel">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3a9 9 0 1 0 0 18h1.5a2 2 0 0 0 1.4-3.4 1.3 1.3 0 0 1 .9-2.2H17a4 4 0 0 0 4-4A8.5 8.5 0 0 0 12 3Z"/><circle cx="7.5" cy="10" r=".8"/><circle cx="11" cy="7" r=".8"/><circle cx="15.5" cy="8.5" r=".8"/></svg>
    <span>{{ __('Theme') }}</span>
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
  </button>
  <div class="public-theme-panel" id="public-theme-panel" hidden>
    <fieldset>
      <legend>{{ __('Theme') }}</legend>
      <div class="public-theme-options">
        <label><input type="radio" name="public-appearance" value="default" checked><span>Default</span></label>
        <label><input type="radio" name="public-appearance" value="glassy"><span>Glassy</span></label>
      </div>
    </fieldset>
    <label class="public-theme-dark"><span>{{ __('Dark mode') }}</span><input type="checkbox" id="public-theme-dark" role="switch"></label>
  </div>
</div>
<script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce') }}">
  (function () {
    var picker = document.getElementById('public-theme-picker');
    var trigger = document.getElementById('public-theme-trigger');
    var panel = document.getElementById('public-theme-panel');
    var dark = document.getElementById('public-theme-dark');
    function close(restoreFocus) {
      panel.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      if (restoreFocus) trigger.focus();
    }
    trigger.addEventListener('click', function () {
      panel.hidden = !panel.hidden;
      trigger.setAttribute('aria-expanded', String(!panel.hidden));
    });
    picker.addEventListener('change', function () {
      window.setPublicTheme({ appearance: picker.querySelector('input[name="public-appearance"]:checked').value, color_mode: dark.checked ? 'dark' : 'light' });
    });
    picker.addEventListener('keydown', function (event) { if (event.key === 'Escape') close(true); });
    picker.addEventListener('focusout', function (event) { if (!picker.contains(event.relatedTarget)) close(false); });
    document.addEventListener('click', function (event) { if (!picker.contains(event.target)) close(false); });
    function sync() {
      var preferences = window.tickoraTheme.preferences;
      picker.querySelector('input[value="' + preferences.appearance + '"]').checked = true;
      dark.checked = preferences.color_mode === 'dark';
    }
    window.addEventListener('tickora:theme-updated', sync);
    sync();
  })();
</script>
