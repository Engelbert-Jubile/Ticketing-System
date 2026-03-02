<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectLoginLoadingOverlay
{
    public function handle(Request $request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        if (! ($response instanceof Response)) {
            return $response;
        }

        if (! $this->shouldInject($request, $response)) {
            return $response;
        }

        $content = (string) $response->getContent();

        // jangan double-inject
        if (stripos($content, 'id="loginOverlay"') !== false) {
            return $response;
        }

        $overlay = $this->overlayMarkup((string) $request->attributes->get('csp_nonce', ''));

        if (stripos($content, '</body>') !== false) {
            $content = preg_replace('~</body>~i', $overlay . "\n</body>", $content, 1);
        } else {
            $content .= "\n" . $overlay;
        }

        $response->setContent($content);

        return $response;
    }

    protected function shouldInject(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        // hanya di /login (termasuk /en/login, dst)
        $path = '/' . ltrim(strtolower($request->path()), '/');
        if ($path !== '/login' && ! str_ends_with($path, '/login')) {
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

        // pastikan benar-benar form login
        $hasForm = stripos($content, '<form') !== false;
        $hasPassword = stripos($content, 'type="password"') !== false || stripos($content, 'name="password"') !== false;

        return $hasForm && $hasPassword;
    }

    protected function overlayMarkup(string $nonce = ''): string
    {
        $nonceAttr = $nonce !== '' ? ' nonce="' . e($nonce) . '"' : '';
        $html = <<<'HTML'
<style id="login-overlay-style">
#loginOverlay{
  position:fixed;
  inset:0;
  display:grid;
  place-items:center;
  background:rgba(255,255,255,.08);
  -webkit-backdrop-filter:blur(8px);
  backdrop-filter:blur(8px);
  visibility:hidden;
  opacity:0;
  pointer-events:none;
  transition:opacity .28s ease, visibility .28s ease;
  z-index:1050;
}
#loginOverlay.visible{
  visibility:visible;
  opacity:1;
  pointer-events:auto;
}
#loginOverlay::before{
  content:"";
  position:absolute;
  inset:0;
  background:
    radial-gradient(circle at 20% 20%, rgba(99,102,241,.35), transparent 45%),
    radial-gradient(circle at 80% 0%, rgba(14,165,233,.35), transparent 35%);
  pointer-events:none;
  opacity:.9;
}
#loginOverlay .panel{
  position:relative;
  z-index:1;
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:10px;
  padding:28px 36px 30px;
  border-radius:32px;
  background:rgba(255,255,255,.95);
  box-shadow:0 30px 80px rgba(15,23,42,.45);
  min-width:280px;
  text-align:center;
}
#loginOverlay .emblem{
  width:72px;height:72px;
  border-radius:24px;
  background:linear-gradient(135deg,#e0e7ff,#c7d2fe);
  position:relative;
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
}
#loginOverlay .emblem::after{
  content:"";
  position:absolute;
  inset:6px;
  border-radius:20px;
  border:2px solid rgba(79,70,229,.45);
  animation:orbit 1.4s linear infinite;
}
#loginOverlay .orbit{
  width:16px;height:16px;
  border-radius:50%;
  background:#4338ca;
  position:absolute;
  animation:drift 1.2s ease-in-out infinite;
}
#loginOverlay .title{
  margin:0;
  font-size:18px;
  font-weight:700;
  color:#0f172a;
}
#loginOverlay .subtitle{
  margin:0;
  font-size:13px;
  color:#475569;
}
#loginOverlay .close-btn{
  position:absolute;
  top:18px; right:18px;
  border:none;
  background:transparent;
  color:rgba(15,23,42,.7);
  font-size:22px;
  cursor:pointer;
  padding:0;
  z-index:2;
}
@keyframes orbit{to{transform:rotate(360deg)}}
@keyframes drift{
  0%{transform:translate(-18px,-8px)}
  50%{transform:translate(20px,10px)}
  100%{transform:translate(-18px,-8px)}
}
</style>

<div id="loginOverlay" aria-hidden="true">
  <button type="button" class="close-btn" aria-label="Tutup overlay">&times;</button>
  <div class="panel">
    <div class="emblem">
      <span class="orbit" aria-hidden="true"></span>
    </div>
    <p class="title">Sedang masuk, mohon tunggu...</p>
    <p class="subtitle">Kami sedang menyiapkan dasbor Tickora Anda.</p>
  </div>
</div>

<script__NONCE__ src="/js/login-overlay.js" defer></script>
HTML;

        return str_replace('__NONCE__', $nonceAttr, $html);
    }
}
