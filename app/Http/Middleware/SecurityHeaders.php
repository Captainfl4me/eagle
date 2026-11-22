<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds pragmatic defensive HTTP security headers to every response.
 * Registered globally via $middleware->append(...) in bootstrap/app.php.
 *
 * Notes on the trade-offs:
 * - `style-src` has to allow 'unsafe-inline' because one Blade template
 *   (welcome.blade.php) sets an inline style attribute (conic-gradient pie).
 *   Moving that rule into resources/css/app.css would let us drop
 *   'unsafe-inline' and tighten the policy. There are no inline <script>
 *   tags in the codebase, so `script-src 'self'` stays strict.
 * - `https://fonts.bunny.net` is whitelisted for styles and fonts because
 *   the layout loads Instrument Sans from it.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // In local/dev the Vite dev server (http://localhost:5173) serves the
        // JS/CSS, so it must be allowed; in production assets are same-origin
        // bundles and the policy stays strict.
        $viteDev = app()->isProduction() ? [] : ['http://localhost:5173'];

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self'".($viteDev ? ' '.implode(' ', $viteDev) : ''),
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net".($viteDev ? ' '.implode(' ', $viteDev) : ''),
            "font-src 'self' https://fonts.bunny.net",
            "img-src 'self' data:",
            "connect-src 'self'".($viteDev ? ' '.implode(' ', $viteDev) : ''),
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
        ]));

        return $response;
    }
}
