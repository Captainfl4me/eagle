<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_works()
    {
        // Create a test user
        User::create([
            'username' => 'testuser',
            'password' => Hash::make('password123'),
        ]);

        // Attempt login
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        // Should redirect to intended page (home)
        $response->assertRedirect('/');
        
        // User should be authenticated
        $this->assertTrue(Auth::check());
        $this->assertEquals('testuser', Auth::user()->username);
    }

    public function test_login_with_invalid_credentials_fails()
    {
        // Create a test user
        User::create([
            'username' => 'testuser',
            'password' => Hash::make('password123'),
        ]);

        // Attempt login with wrong password
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        // Should redirect back with errors
        $response->assertRedirect(); // Assert it's a redirect
        $response->assertSessionHasErrors('username');
    }

    public function test_username_is_required()
    {
        $response = $this->post('/login', [
            'username' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_password_is_required()
    {
        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_logout_clears_session()
    {
        // Create and login user
        User::create([
            'username' => 'testuser',
            'password' => Hash::make('password123'),
        ]);

        $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $this->assertTrue(Auth::check());

        // Perform logout
        $this->post('/logout');

        // User should be logged out
        $this->assertFalse(Auth::check());
    }
}