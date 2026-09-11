<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetMonthTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_month_can_be_created()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        $response = $this->post(route('budgets.updateMonth', $budget), [
            'month' => '2024-01-01',
            'budgeted_amount' => 1100,
            'realized_amount' => 0,
        ]);

        $response->assertRedirect(route('budgets.show', $budget));
        $this->assertDatabaseHas('budget_months', [
            'budget_id' => $budget->id,
            'month' => '2024-01-01 00:00:00',
        ]);
    }

    public function test_cannot_create_non_contiguous_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        // Attempt to create March without February existing
        $response = $this->post(route('budgets.updateMonth', $budget), [
            'month' => '2024-03-01',
            'budgeted_amount' => 1200,
            'realized_amount' => 0,
        ]);

        $response->assertSessionHasErrors('month');
        $this->assertDatabaseMissing('budget_months', [
            'budget_id' => $budget->id,
            'month' => '2024-03-01',
        ]);
    }

    public function test_cannot_create_month_without_amounts()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        $response = $this->post(route('budgets.updateMonth', $budget), [
            'month' => '2024-01-01',
            // missing amounts
        ]);

        $response->assertSessionHasErrors(['budgeted_amount', 'realized_amount']);
    }

    public function test_budgeted_amount_prefills_from_previous_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 0,
        ]);

        $this->post(route('budgets.updateMonth', $budget), [
            'month' => '2024-01-01',
            'budgeted_amount' => 200,
            'realized_amount' => 100,
        ]);

        // February has no record yet: budgeted amount pre-fills with January's value.
        $response = $this->get(route('budgets.show', ['id' => $budget->id, 'month' => '2024-02']));
        $response->assertSee('name="budgeted_amount" value="200.00"', false);
    }

    public function test_budgeted_amount_prefills_zero_without_previous_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 0,
        ]);

        // Start month with no records at all: pre-fill with 0.
        $response = $this->get(route('budgets.show', $budget->id));
        $response->assertSee('name="budgeted_amount" value="0.00"', false);
    }

    public function test_budgeted_amount_prefill_uses_stored_value_when_month_exists()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 0,
        ]);

        $this->post(route('budgets.updateMonth', $budget), [
            'month' => '2024-01-01',
            'budgeted_amount' => 200,
            'realized_amount' => 100,
        ]);
        $this->post(route('budgets.updateMonth', $budget), [
            'month' => '2024-02-01',
            'budgeted_amount' => 300,
            'realized_amount' => 250,
        ]);

        // February exists with its own stored value: show it, not January's.
        $response = $this->get(route('budgets.show', ['id' => $budget->id, 'month' => '2024-02']));
        $response->assertSee('name="budgeted_amount" value="300.00"', false);
    }
}
