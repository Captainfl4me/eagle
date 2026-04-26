<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_is_required(): void
    {
        $response = $this->post('/register', [
            'username' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_username_must_be_unique(): void
    {
        User::create([
            'username' => 'taken',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/register', [
            'username' => 'taken',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_password_must_be_confirmed(): void
    {
        $response = $this->post('/register', [
            'username' => 'newuser',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_is_created_in_database(): void
    {
        $this->post('/register', [
            'username' => 'newuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
        ]);
    }

    public function test_password_is_hashed(): void
    {
        $this->post('/register', [
            'username' => 'newuser',
            'password' => 'mypassword123',
            'password_confirmation' => 'mypassword123',
        ]);

        $user = User::where('username', 'newuser')->first();
        $this->assertTrue(Hash::check('mypassword123', $user->password));
    }

    public function test_registration_redirects_to_login(): void
    {
        $response = $this->post('/register', [
            'username' => 'newuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
    }
}