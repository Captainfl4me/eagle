<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_budgeted_amount_can_be_set_for_a_range_of_months()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01 00:00:00',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->post(route('budgets.updateMonthsRange', $budget->id), [
            'from_month' => '2024-01',
            'to_month' => '2024-03',
            'budgeted_amount' => 500,
        ]);

        $response->assertRedirect(route('budgets.show', ['id' => $budget->id, 'month' => '2024-03']));
        $this->assertEquals(3, BudgetMonth::where('budget_id', $budget->id)->count());
        foreach (['2024-01-01 00:00:00', '2024-02-01 00:00:00', '2024-03-01 00:00:00'] as $month) {
            $this->assertDatabaseHas('budget_months', [
                'budget_id' => $budget->id,
                'month' => $month,
                'budgeted_amount' => 500,
            ]);
        }
    }

    public function test_range_update_keeps_existing_realized_amounts()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01 00:00:00',
            'start_amount' => 1000,
        ]);

        $this->actingAs($user)->post(route('budgets.updateMonth', $budget->id), [
            'month' => '2024-01-01',
            'budgeted_amount' => 400,
            'realized_amount' => 350,
        ]);

        $this->actingAs($user)->post(route('budgets.updateMonthsRange', $budget->id), [
            'from_month' => '2024-01',
            'to_month' => '2024-02',
            'budgeted_amount' => 500,
        ]);

        $this->assertDatabaseHas('budget_months', [
            'budget_id' => $budget->id,
            'month' => '2024-01-01 00:00:00',
            'budgeted_amount' => 500,
            'realized_amount' => 350,
        ]);
        $this->assertDatabaseHas('budget_months', [
            'budget_id' => $budget->id,
            'month' => '2024-02-01 00:00:00',
            'budgeted_amount' => 500,
            'realized_amount' => 0,
        ]);
    }

    public function test_range_must_be_contiguous_with_existing_months()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01 00:00:00',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->post(route('budgets.updateMonthsRange', $budget->id), [
            'from_month' => '2024-03',
            'to_month' => '2024-04',
            'budgeted_amount' => 500,
        ]);

        $response->assertSessionHasErrors('from_month');
        $this->assertEquals(0, BudgetMonth::where('budget_id', $budget->id)->count());
    }

    public function test_range_cannot_start_before_the_budget_start_month()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01 00:00:00',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->post(route('budgets.updateMonthsRange', $budget->id), [
            'from_month' => '2023-12',
            'to_month' => '2024-01',
            'budgeted_amount' => 500,
        ]);

        $response->assertSessionHasErrors('from_month');
        $this->assertEquals(0, BudgetMonth::where('budget_id', $budget->id)->count());
    }

    public function test_to_month_must_not_be_before_from_month()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01 00:00:00',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->post(route('budgets.updateMonthsRange', $budget->id), [
            'from_month' => '2024-03',
            'to_month' => '2024-01',
            'budgeted_amount' => 500,
        ]);

        $response->assertSessionHasErrors('to_month');
        $this->assertEquals(0, BudgetMonth::where('budget_id', $budget->id)->count());
    }

    public function test_cannot_update_range_of_another_users_budget()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $owner->id,
            'start_month' => '2024-01-01 00:00:00',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($other)->post(route('budgets.updateMonthsRange', $budget->id), [
            'from_month' => '2024-01',
            'to_month' => '2024-02',
            'budgeted_amount' => 500,
        ]);

        $response->assertStatus(404);
        $this->assertEquals(0, BudgetMonth::where('budget_id', $budget->id)->count());
    }

    public function test_budget_page_shows_the_advanced_settings_menu()
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'start_month' => '2024-01-01 00:00:00',
            'start_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('budgets.show', $budget->id));

        $response->assertStatus(200);
        $response->assertSee('Advanced settings');
        $response->assertSee('name="from_month"', false);
        $response->assertSee('name="to_month"', false);
        $response->assertSee('Apply to month range');
    }
}
