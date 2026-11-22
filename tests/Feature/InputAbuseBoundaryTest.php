<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetMonth;
use App\Models\Reallocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputAbuseBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_range_over_the_cap_is_rejected()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->post(route('budgets.updateMonthsRange', $budget->id), [
            'from_month' => '2024-01',
            'to_month' => '9999-12',
            'budgeted_amount' => 500,
        ]);

        $response->assertSessionHasErrors('to_month');
        $this->assertEquals(0, BudgetMonth::where('budget_id', $budget->id)->count());
    }

    public function test_month_range_at_exactly_the_cap_is_accepted()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->post(route('budgets.updateMonthsRange', $budget->id), [
            'from_month' => '2024-01',
            'to_month' => '2033-12',
            'budgeted_amount' => 500,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertEquals(120, BudgetMonth::where('budget_id', $budget->id)->count());
        $this->assertDatabaseHas('budget_months', [
            'budget_id' => $budget->id,
            'month' => '2033-12-01 00:00:00',
            'budgeted_amount' => 500,
        ]);
    }

    public function test_reallocation_duplicate_returns_friendly_error_not_500()
    {
        $user = User::factory()->create();
        $recipient = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01']);
        $source = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01']);

        $first = $this->actingAs($user)->post(route('reallocations.store', $recipient->id), [
            'source_budget_id' => $source->id,
            'month' => '2024-02',
            'amount' => 100,
        ]);
        $first->assertRedirect();

        $second = $this->actingAs($user)->post(route('reallocations.store', $recipient->id), [
            'source_budget_id' => $source->id,
            'month' => '2024-02',
            'amount' => 100,
        ]);
        $second->assertSessionHasErrors('amount');
        $this->assertEquals(1, Reallocation::count());
    }

    public function test_garbage_to_month_returns_validation_error_not_500()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);

        $response = $this->actingAs($user)->post(route('budgets.updateMonthsRange', $budget->id), [
            'from_month' => '2024-01',
            'to_month' => 'abcd',
            'budgeted_amount' => 500,
        ]);

        $response->assertSessionHasErrors('to_month');
        $this->assertEquals(0, BudgetMonth::where('budget_id', $budget->id)->count());
    }
}
