<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate limiters for the authentication endpoints (brute-force protection).
        // Defined here (not in routes/web.php) so they are registered even when
        // routes are cached (`php artisan route:cache` in production skips
        // loading the routes file).
        RateLimiter::for('login', function (Request $request) {
            // Key on the submitted username + client IP so an attacker cannot lock out
            // other accounts by spraying passwords, and so brute force against one
            // account does not open the door from another identity.
            $username = $request->input('username');
            $username = is_string($username) ? strtolower(trim($username)) : '';

            return [
                Limit::perMinute(5)->by('login:'.$username.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return [
                Limit::perMinute(10)->by('register:'.$request->ip()),
            ];
        });
    }
}
