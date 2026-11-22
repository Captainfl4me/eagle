<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Untrusted proxies: X-Forwarded-For/Proto/Host must be ignored so
     * clients cannot spoof their IP, scheme or Host header.
     */
    public function test_untrusted_proxy_cannot_spoof_forwarded_headers(): void
    {
        TrustProxies::flushState();

        $request = $this->spoofedRequest();

        (new TrustProxies)->handle($request, fn (Request $r) => $r);

        $this->assertSame('172.16.0.10', $request->ip());
        $this->assertFalse($request->isSecure());
        $this->assertSame('127.0.0.1', $request->host());
        $this->assertSame('http', $request->getScheme());
    }

    /**
     * The trusted-proxy feature still works when configured explicitly:
     * headers from a trusted proxy are honored (IP, scheme and host).
     */
    public function test_forwarded_headers_are_honored_when_proxy_is_trusted(): void
    {
        TrustProxies::flushState();
        TrustProxies::at('172.16.0.10');

        try {
            $request = $this->spoofedRequest();

            (new TrustProxies)->handle($request, fn (Request $r) => $r);

            $this->assertSame('203.0.113.99', $request->ip());
            $this->assertTrue($request->isSecure());
            $this->assertSame('evil.example.com', $request->host());
        } finally {
            TrustProxies::flushState();
        }
    }

    /**
     * The device that changes the password must stay logged in afterwards.
     */
    public function test_current_session_survives_password_change(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->actingAs($user)
            ->post('/profile/password', [
                'current_password' => 'old-password',
                'new_password' => 'new-password-1',
                'new_password_confirmation' => 'new-password-1',
            ])
            ->assertRedirect(route('profile'));

        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));

        // The current session must remain authenticated after the change.
        $this->get('/profile')->assertOk();
    }

    /**
     * A session opened before the password change (e.g. on another device,
     * or by an attacker with a stolen session) must be signed out once the
     * password changes: its recorded password hash no longer matches.
     */
    public function test_other_device_session_is_invalidated_by_password_change(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);
        $rawHashBeforeChange = $user->password;

        $this->actingAs($user)
            ->post('/profile/password', [
                'current_password' => 'old-password',
                'new_password' => 'new-password-1',
                'new_password_confirmation' => 'new-password-1',
            ]);

        // Replay the session state AuthenticateSession records at login time:
        // the HMAC-wrapped password hash that was valid before the change.
        $this->withSession([
            'password_hash_web' => auth()->guard()->hashPasswordForCookie($rawHashBeforeChange),
        ]);

        $this->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * A request pretending to come through a reverse proxy.
     */
    private function spoofedRequest(): Request
    {
        $request = Request::create('http://127.0.0.1/', 'GET');
        $request->server->set('REMOTE_ADDR', '172.16.0.10');
        $request->headers->set('X-Forwarded-For', '203.0.113.99');
        $request->headers->set('X-Forwarded-Proto', 'https');
        $request->headers->set('X-Forwarded-Host', 'evil.example.com');

        return $request;
    }
}
