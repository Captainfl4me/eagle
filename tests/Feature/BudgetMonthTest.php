<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\User;
use Carbon\Carbon;
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
}
