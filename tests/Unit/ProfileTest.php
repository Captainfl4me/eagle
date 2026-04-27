<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_requires_authentication()
    {
        $response = $this->get('/profile');
        $response->assertRedirect('/login');
    }

    public function test_profile_page_displays_username()
    {
        // Create and login user
        $user = User::create([
            'username' => 'testuser',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user);
        $response = $this->get('/profile');
        
        $response->assertStatus(200);
        $response->assertSee('testuser');
    }

    public function test_password_update_with_valid_current_password()
    {
        // Create and login user
        $user = User::create([
            'username' => 'testuser',
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($user);
        
        // Update password
        $response = $this->post('/profile/password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('status', 'Password updated successfully!');
        
        // Verify password was updated in database
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_password_update_fails_with_wrong_current_password()
    {
        // Create and login user
        $user = User::create([
            'username' => 'testuser',
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($user);
        
        // Try to update with wrong current password
        $response = $this->post('/profile/password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_password_update_requires_confirmation_match()
    {
        // Create and login user
        $user = User::create([
            'username' => 'testuser',
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($user);
        
        // Try to update with mismatched passwords
        $response = $this->post('/profile/password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('new_password');
    }

    public function test_password_update_requires_minimum_length()
    {
        // Create and login user
        $user = User::create([
            'username' => 'testuser',
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($user);
        
        // Try to update with too short password
        $response = $this->post('/profile/password', [
            'current_password' => 'oldpassword',
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('new_password');
    }

    public function test_profile_logout_clears_session()
    {
        // Create and login user
        $user = User::create([
            'username' => 'testuser',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user);
        $this->assertTrue(Auth::check());

        // Perform logout via profile controller
        $this->post('/logout');
        
        $this->assertFalse(Auth::check());
    }
}