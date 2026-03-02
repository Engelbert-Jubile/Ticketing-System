/* global grecaptcha */
(function () {
  var form = document.getElementById("login-form");
  var el = document.getElementById("g-recaptcha-response");

  if (!form || !el) return;

  var siteKey = form.getAttribute("data-recaptcha-sitekey") || "";
  if (!siteKey) return;

  // Fallback: avoid JS crash if showBgLoading is missing.
  if (typeof window.showBgLoading !== "function") {
    window.showBgLoading = function () {
      var ov = document.getElementById("loginOverlay");
      if (ov) ov.classList.add("show");
    };
  }

  var busy = false;
  form.addEventListener(
    "submit",
    function (e) {
      window.showBgLoading();

      // Submit kedua setelah token didapat.
      if (busy) return;

      // Biarkan server handle error jika API belum siap.
      if (typeof grecaptcha === "undefined") return;

      e.preventDefault();
      busy = true;

      try {
        grecaptcha.ready(function () {
          grecaptcha
            .execute(siteKey, { action: "login" })
            .then(function (token) {
              el.value = token || "";
              form.submit();
            })
            .catch(function () {
              busy = false;
              alert("reCAPTCHA gagal, silakan refresh dan coba lagi.");
            });
        });
      } catch (err) {
        busy = false;
        alert("reCAPTCHA gagal, silakan refresh dan coba lagi.");
      }
    },
    true
  );
})();
