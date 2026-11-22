<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The middleware is registered globally in bootstrap/app.php, so real
        // application routes carry the headers; test-only routes remain for
        // redirect/edge coverage.
        Route::get('/security-headers-test', fn () => 'ok');
        Route::get('/security-headers-redirect', fn () => redirect('/login'));
    }

    public function test_response_includes_all_security_headers(): void
    {
        $response = $this->get('/security-headers-test');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        // No inline scripts exist in the app, so the script policy never
        // allows 'unsafe-inline' (the Vite dev origin is added outside prod).
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
        // Fonts are loaded from Bunny Fonts via a <link> tag in the layout.
        $this->assertStringContainsString('https://fonts.bunny.net', $csp);
    }

    public function test_headers_are_applied_to_redirect_responses(): void
    {
        $response = $this->get('/security-headers-redirect');

        $response->assertStatus(302);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_headers_are_set_exactly_once(): void
    {
        $response = $this->get('/security-headers-test');

        $response->assertStatus(200);
        $this->assertSame(['nosniff'], $response->headers->all('X-Content-Type-Options'));
        $this->assertSame(['SAMEORIGIN'], $response->headers->all('X-Frame-Options'));
        $this->assertCount(1, $response->headers->all('Content-Security-Policy'));
    }

    public function test_headers_are_present_on_real_app_pages(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
