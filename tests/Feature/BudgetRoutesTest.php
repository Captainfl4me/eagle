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

    public function test_dashboard_renders_pie_and_cashflow_charts()
    {
        $user = User::create([
            'username' => 'chartuser',
            'password' => bcrypt('password123'),
        ]);

        // Two budgets with known envelope totals.
        $housing = $user->budgets()->create([
            'name' => 'Housing',
            'start_month' => '2026-01-01',
            'start_amount' => 2000,
        ]);
        $user->budgets()->create([
            'name' => 'Food',
            'start_month' => '2026-01-01',
            'start_amount' => 600,
        ]);

        // Latest (Feb 2026) budgeted vs realized for the cash-flow chart.
        $housing->months()->create([
            'month' => '2026-02-01',
            'budgeted_amount' => 2000,
            'realized_amount' => 1750,
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        // The two chart canvases are rendered inside a fixed-height wrapper
        // (Chart.js fills its parent; without a fixed height the responsive
        // re-size loop makes the charts grow unboundedly).
        $response->assertSee('id="pie-chart"', false);
        $response->assertSee('id="cashflow-chart"', false);
        $response->assertSee('relative h-64', false);
        $response->assertSee('min-w-0', false);

        // Pie chart carries each budget's name and envelope total. The Housing
        // total is 2250: 2000 start + (2000 budgeted − 1750 realized in Feb).
        $response->assertSee('Housing', false);
        $response->assertSee('Food', false);
        $response->assertSee('2000', false);
        $response->assertSee('600', false);
        $response->assertSee('2250', false);

        // Cash-flow chart carries budgeted and realized for the latest month
        // (only Housing has a February record).
        $response->assertSee('1750', false);
        $response->assertSee('data-budgeted', false);
        $response->assertSee('data-realized', false);
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
