<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_throttled_after_five_attempts_per_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'testuser',
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_login_throttle_is_keyed_per_username_so_other_accounts_are_not_blocked(): void
    {
        User::create([
            'username' => 'otheruser',
            'password' => Hash::make('password123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'bruteforced-user',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'username' => 'bruteforced-user',
            'password' => 'wrong-password',
        ]);
        $response->assertStatus(429);

        // A different account from the same IP must not be locked out.
        $response = $this->post('/login', [
            'username' => 'otheruser',
            'password' => 'password123',
        ]);
        $response->assertStatus(302);
        $this->assertTrue(auth()->check());
    }

    public function test_register_is_throttled_after_ten_attempts_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', [
                'username' => 'user'.$i,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertRedirect(route('login'));
        }

        $response = $this->post('/register', [
            'username' => 'user11',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(429);
    }

    public function test_logout_route_requires_authentication(): void
    {
        // The unauthenticated POST /logout route was removed; guests now hit
        // the auth middleware and are redirected to the login page.
        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertFalse(auth()->check());
    }

    public function test_register_rejects_short_passwords(): void
    {
        $response = $this->post('/register', [
            'username' => 'shortpw',
            'password' => 'short1!',
            'password_confirmation' => 'short1!',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['username' => 'shortpw']);
    }
}
