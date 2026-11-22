<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Only honor X-Forwarded-* headers (Proto/Host/For/...) coming from
        // reverse proxies explicitly listed in the TRUSTED_PROXIES environment
        // variable (comma-separated IPs or CIDRs, e.g. "10.0.0.0/8").
        // "*" trusts every caller and should only be used when the app is
        // exclusively reachable through the TLS-terminating proxy — otherwise
        // clients can spoof their IP, the scheme and the Host header.
        // When unset, no proxy headers are trusted.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies);
        }

        // Re-validate the session's recorded password hash on every request.
        // This makes "logout other browser sessions" work: after a password
        // change, sessions opened before the change no longer match the
        // stored hash and are immediately signed out.
        $middleware->authenticateSessions();
    })
    ->booted(function (Application $app): void {
        // Behind a TLS-terminating proxy, generated URLs (links, assets,
        // redirects) must use https even though the app itself receives
        // plain http. Only forced when running in production AND APP_URL is
        // configured with an https:// scheme, so local development on plain
        // http (and the test suite) is unaffected.
        if (
            $app->isProduction()
            && str_starts_with((string) $app['config']->get('app.url'), 'https://')
        ) {
            $app['url']->forceScheme('https');
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
