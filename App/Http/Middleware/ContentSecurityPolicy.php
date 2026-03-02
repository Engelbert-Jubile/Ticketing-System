<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        view()->share('cspNonce', $nonce);
        $request->attributes->set('csp_nonce', $nonce);

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "frame-src 'self' https://www.google.com https://www.gstatic.com",
            "img-src 'self' data: https://img.icons8.com",
            // NOTE: hashes below whitelist known inline bootstrap snippets.
            "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://www.google.com https://www.gstatic.com 'sha256-LMmqXgfkkVs7Lpa0RDspAs2xyQJI/+Yv58I12SywUnA=' 'sha256-FyCbrsYE1Dwm6hKyfZKXqBbAkusY0XCL9xowJYRWmWo=' 'sha256-uyWu7xRwdCUgJJE4CkMn64ZUqHkwyOrS2xAOZ9B77+0=' 'sha256-b1rtbWIN0jhBPpRIL713T0oW9gTGg+D3HAbnKoidRm4=' 'sha256-Ed+eLaW9fQnKqC1wOd2Fc52IdsYurUnHxlqH9idgnnk=' 'sha256-Y0wPbVp63Ys5vshT3/LI0dkbqsik0yl6mZ66JmR5IvE=' 'sha256-07d3SD3u41fNM/vCBhGjQxghXue+I310wn3w9cCV/Bs=' 'sha256-AKf3tXPksV2ofkZmQrnF4A1VDgGC1yJp4ZWH2yMsYcE=' 'sha256-CYVhwhzP/O+34ZD6clHrW1MtHM5hQgNG5VjCXQiaS1c='",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdn.jsdelivr.net",
            "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net https://cdn.jsdelivr.net",
            "connect-src 'self' https://www.google.com https://www.gstatic.com",
        ]);

        /** @var Response $response */
        $response = $next($request);
        $this->injectNonceIntoHtml($response, $nonce);
        $response->headers->set('Content-Security-Policy', preg_replace("/[\r\n]+/", ' ', $csp).';');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    private function injectNonceIntoHtml(Response $response, string $nonce): void
    {
        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($contentType !== '' && strpos($contentType, 'text/html') === false) {
            return;
        }

        $content = (string) $response->getContent();
        if ($content === '') {
            return;
        }

        // Add nonce to script/style tags that do not already have one.
        $content = preg_replace('/<script(?![^>]*\bnonce=)([^>]*)>/i', '<script nonce="'.$nonce.'"$1>', $content);
        $content = preg_replace('/<style(?![^>]*\bnonce=)([^>]*)>/i', '<style nonce="'.$nonce.'"$1>', $content);

        $response->setContent($content);
    }
}
