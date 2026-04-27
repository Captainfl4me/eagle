<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_budget_creation_page()
    {
        $response = $this->get(route('budgets.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_budget_creation_page()
    {
        $user = User::create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)->get(route('budgets.create'));
        $response->assertStatus(200);
        $response->assertSee('Create New Budget');
    }

    public function test_budget_creation_validation_errors()
    {
        $user = User::create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)->post(route('budgets.store'), []);
        $response->assertSessionHasErrors(['name', 'start_month', 'start_amount']);
    }

    public function test_budget_is_created_successfully()
    {
        $user = User::create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $data = [
            'name' => 'Test Budget',
            'start_month' => '2024-05',
            'start_amount' => '1000.50',
        ];

        $response = $this->actingAs($user)->post(route('budgets.store'), $data);
        $response->assertRedirect('/');

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'name' => 'Test Budget',
            'start_month' => '2024-05-01 00:00:00',
            'start_amount' => 1000.5,
        ]);
    }
}
