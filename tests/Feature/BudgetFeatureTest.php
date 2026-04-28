<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetMonth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_amount_is_correctly_calculated_on_detail_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        // Create two months
        BudgetMonth::create([
            'budget_id' => $budget->id,
            'month' => '2024-01-01',
            'budgeted_amount' => 500,
            'realized_amount' => 200,
        ]);
        BudgetMonth::create([
            'budget_id' => $budget->id,
            'month' => '2024-02-01',
            'budgeted_amount' => 400,
            'realized_amount' => 600,
        ]);

        $response = $this->get(route('budgets.show', $budget->id));
        $response->assertStatus(200);
        // Expected total: start 1000 + (500-200) + (400-600) = 1100
        $response->assertSee('$1,100.00');
    }

    public function test_total_amount_is_correctly_calculated_on_list_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 2000,
        ]);
        BudgetMonth::create([
            'budget_id' => $budget->id,
            'month' => '2024-01-01',
            'budgeted_amount' => 300,
            'realized_amount' => 100,
        ]);
        // total = 2000 + (300-100) = 2200
        $response = $this->get(route('budgets.index'));
        $response->assertStatus(200);
        $response->assertSee('$2,200.00');
    }

    public function test_total_amount_shows_correct_color_based_on_sign()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 500,
        ]);
        BudgetMonth::create([
            'budget_id' => $budget->id,
            'month' => '2024-01-01',
            'budgeted_amount' => 100,
            'realized_amount' => 200,
        ]); // total = 500 + (100-200) = 400 (positive)

        $response = $this->get(route('budgets.show', $budget->id));
        $response->assertSee('text-green-600');

        // Make total negative
        $budget->months()->create([
            'month' => '2024-02-01',
            'budgeted_amount' => 100,
            'realized_amount' => 1000,
        ]); // now total = 500 + (100-200) + (100-1000) = -500
        $response = $this->get(route('budgets.show', $budget->id));
        $response->assertSee('text-red-600');
    }

    public function test_deleting_a_budget_cascades_to_month_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);
        BudgetMonth::create([
            'budget_id' => $budget->id,
            'month' => '2024-01-01',
            'budgeted_amount' => 100,
            'realized_amount' => 0,
        ]);

        $response = $this->delete(route('budgets.destroy', $budget->id));
        $response->assertRedirect(route('budgets.index'));
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
        $this->assertDatabaseMissing('budget_months', ['budget_id' => $budget->id]);
    }

    public function test_default_month_selection_behaviour()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);
        // No month records – should default to start month
        $response = $this->get(route('budgets.show', $budget->id));
        $response->assertSee('value="2024-01-01"', false);

        // Add a month record for February
        BudgetMonth::create([
            'budget_id' => $budget->id,
            'month' => '2024-02-01',
            'budgeted_amount' => 200,
            'realized_amount' => 0,
        ]);
        // Should now default to the latest existing month (Feb)
        $response = $this->get(route('budgets.show', $budget->id));
        $response->assertSee('value="2024-02-01"', false);
    }
}
