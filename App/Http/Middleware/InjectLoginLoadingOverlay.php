<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectLoginLoadingOverlay
{
  public function handle(Request $request, Closure $next): Response
  {
    /** @var Response $response */
    $response = $next($request);

    try {
      // Intercept successful login redirect to show a brief loading screen
      if ($this->shouldInterceptLoginRedirect($request, $response)) {
        $target = (string) $response->headers->get('Location');

        return $this->loadingResponse($target);
      }

      if ($this->shouldInject($request, $response)) {
        $content = (string) $response->getContent();
        if (stripos($content, 'id="loginOverlay"') !== false) {
          return $response; // already present in the view
        }
        $injection = $this->overlayMarkup();

        if (stripos($content, '</body>') !== false) {
          $content = preg_replace('~</body>~i', $injection . '</body>', $content, 1);
        } else {
          $content .= $injection;
        }

        $response->setContent($content);
      }
    } catch (\Throwable $e) {
      // Fail silently to avoid breaking the login page
    }

    return $response;
  }

  protected function shouldInterceptLoginRedirect(Request $request, Response $response): bool
  {
    if (! $request->isMethod('POST')) {
      return false;
    }
    $path = strtolower($request->path());
    if (strpos($path, 'login') === false) {
      return false;
    }
    if (! ($response instanceof RedirectResponse)) {
      return false;
    }
    $location = (string) $response->headers->get('Location');
    if ($location === '') {
      return false;
    }

    // Do not intercept when redirecting back to login (failed auth)
    return stripos($location, 'login') === false;
  }

  protected function loadingResponse(string $target): Response
  {
    $html = <<<'HTML'
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Tickora — Memuat</title>
  <style>
    * {
      box-sizing: border-box;
    }
    html, body {
      height: 100%;
      margin: 0;
    }
    body {
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, Ubuntu, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(15, 23, 42, 0.82), rgba(15, 23, 42, 0.92));
      color: #e2e8f0;
    }
    .scene {
      position: relative;
      width: min(320px, 90vw);
      padding: 0 1rem;
      text-align: center;
    }
    .card {
      position: relative;
      padding: 28px;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.9);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.32);
      min-width: 260px;
      min-height: 170px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 12px;
      text-align: center;
    }
    .loader {
      width: 64px;
      height: 64px;
      margin: 0;
      position: relative;
      display: block;
    }
    .loader .ring {
      position: absolute;
      inset: 0;
      border-radius: 50%;
      border: 4px solid rgba(79, 70, 229, 0.18);
      border-top-color: #1d4ed8;
      border-right-color: #38bdf8;
      animation: spin 0.9s linear infinite;
      transform-origin: 50% 50%;
    }
    .hero {
      margin: 0;
      font-size: 16px;
      font-weight: 700;
      color: #0f172a;
    }
    .subtext {
      margin: 4px 0 0;
      font-size: 13px;
      color: #475569;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="scene">
    <div class="card">
      <div class="loader" aria-hidden="true"><span class="ring"></span></div>
      <h1 class="hero">Sedang masuk…</h1>
      <p class="subtext">Membuka Dashboard Anda.</p>
    </div>
  </div>
  <script>setTimeout(function(){ window.location.href = %s; }, 700);</script>
</body>
</html>
HTML;
    // Safely JSON-encode target URL for JS literal
    $encodedTarget = json_encode($target, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $html = sprintf($html, $encodedTarget);

    return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
  }

  protected function shouldInject(Request $request, Response $response): bool
  {
    if (! $request->isMethod('GET')) {
      return false;
    }

    $contentType = (string) $response->headers->get('Content-Type');
    $isHtml = $contentType === '' || stripos($contentType, 'text/html') !== false;
    if (! $isHtml) {
      return false;
    }

    $content = (string) $response->getContent();
    if ($content === '') {
      return false;
    }

    // Heuristic: inject only if the page looks like a login form
    $looksLikeLogin = stripos($content, 'type="password"') !== false || stripos($content, 'name="password"') !== false;
    $hasForm = stripos($content, '<form') !== false;
    $mentionsLogin = stripos($content, 'login') !== false || stripos($content, 'masuk') !== false;

    return $hasForm && $looksLikeLogin && $mentionsLogin;
  }

  protected function overlayMarkup(): string
  {
    // Minimal inline CSS + JS. No external dependencies required.
    return <<<'HTML'
<style id="login-overlay-style">
#loginOverlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.95));
  backdrop-filter: blur(8px);
  visibility: hidden;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.28s ease, visibility 0.28s ease;
  z-index: 1050;
}
#loginOverlay.visible {
  visibility: visible;
  opacity: 1;
  pointer-events: auto;
}
#loginOverlay::before {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.35), transparent 45%),
              radial-gradient(circle at 80% 0%, rgba(14, 165, 233, 0.35), transparent 35%);
  pointer-events: none;
  opacity: 0.9;
}
#loginOverlay .panel {
  position: relative;
  z-index: 1;
  backdrop-filter: blur(6px);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 28px 36px 30px;
  border-radius: 32px;
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.45);
  min-width: 280px;
}
#loginOverlay .panel .emblem {
  width: 72px;
  height: 72px;
  border-radius: 24px;
  background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
#loginOverlay .panel .emblem::after {
  content: "";
  position: absolute;
  inset: 6px;
  border-radius: 20px;
  border: 2px solid rgba(79, 70, 229, 0.45);
  animation: orbit 1.4s linear infinite;
}
#loginOverlay .panel .orbit {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #4338ca;
  position: absolute;
  animation: drift 1.2s ease-in-out infinite;
}
#loginOverlay .panel .title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  text-align: center;
}
#loginOverlay .panel .subtitle {
  margin: 0;
  font-size: 13px;
  color: #475569;
  text-align: center;
}
#loginOverlay .close-btn {
  position: absolute;
  top: 18px;
  right: 18px;
  border: none;
  background: transparent;
  color: rgba(15, 23, 42, 0.7);
  font-size: 22px;
  cursor: pointer;
  padding: 0;
}
@keyframes orbit {
  to {
    transform: rotate(360deg);
  }
}
@keyframes drift {
  0% { transform: translate(-18px, -8px); }
  50% { transform: translate(20px, 10px); }
  100% { transform: translate(-18px, -8px); }
}
</style>
<div id="loginOverlay" aria-hidden="true">
  <div class="panel">
    <div class="emblem">
      <span class="orbit" aria-hidden="true"></span>
    </div>
    <p class="title">Sedang masuk, mohon tunggu…</p>
    <p class="subtitle">Kami sedang menyiapkan dasbor Tickora Anda.</p>
  </div>
  <button type="button" class="close-btn" aria-label="Tutup overlay" onclick="this.closest('#loginOverlay').classList.remove('visible')">×</button>
  <script>
    (function(){
      function ready(fn){ if(document.readyState !== 'loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
      ready(function(){
        try {
          var path = location.pathname.toLowerCase();
          if (path !== '/login' && !path.endsWith('/login')) return;
          var overlay = document.getElementById('loginOverlay');
          if (!overlay) return;
          var form = document.querySelector('form');
          if (!form) return;
            form.addEventListener('submit', function(){
              overlay.classList.add('visible');
              var btn = form.querySelector('[type="submit"]');
            if (btn){
              btn.disabled = true;
              try {
                if (!btn.dataset.originalText) btn.dataset.originalText = btn.innerHTML;
                btn.innerHTML = 'Memuat…';
              } catch(e) {}
            }
          }, { once: true });
        } catch(e) { /* no-op */ }
      });
    })();
  </script>
</div>
HTML;
  }
}
