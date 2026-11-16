(function () {
  function ready(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }

  ready(function () {
    try {
      var overlay = document.getElementById("loginOverlay");
      if (!overlay) return;

      // hanya aktif di /login
      var path = (location.pathname || "").toLowerCase();
      if (path !== "/login" && !path.endsWith("/login")) return;

      var form = document.querySelector("form");
      if (!form) return;

      window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        overlay.classList.remove('visible', 'show');
        overlay.setAttribute('aria-hidden', 'true');
        var button = form.querySelector('[type="submit"]');
        if (button && button.dataset.originalText) {
          button.innerHTML = button.dataset.originalText;
          button.disabled = false;
          button.removeAttribute('aria-busy');
        }
      });

      form.addEventListener(
        "submit",
        function () {
          overlay.classList.add("visible");
          overlay.setAttribute("aria-hidden", "false");
          var btn = form.querySelector('[type="submit"]');
          if (btn) {
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            try {
              if (!btn.dataset.originalText)
                btn.dataset.originalText = btn.innerHTML;
              btn.innerHTML = "Memuat…";
            } catch (e) {}
          }
        }
      );
    } catch (e) {}
  });
})();
