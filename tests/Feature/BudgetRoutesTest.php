<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_index_requires_authentication()
    {
        $response = $this->get(route('budgets.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_sees_welcome_page_at_root()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Hello to Eagle');
    }

    public function test_authenticated_user_sees_dashboard_at_root()
    {
        $user = User::create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_budget_show_requires_authentication()
    {
        // Create a budget for a user
        $user = User::create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);
        $budget = $user->budgets()->create([
            'name' => 'Test Budget',
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        // Attempt to access without auth
        $response = $this->get(route('budgets.show', $budget->id));
        $response->assertRedirect(route('login'));
    }
}
