<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetMonth;
use App\Models\Reallocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that every budget/reallocation endpoint is scoped to the
 * authenticated user: accessing or mutating another user's data must
 * result in 403/404 (or a validation error) and no data change.
 */
class CrossUserAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $attacker;

    private Budget $victimBudget;

    private BudgetMonth $victimMonth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->attacker = User::factory()->create();

        $this->victimBudget = Budget::factory()->create([
            'user_id' => $this->owner->id,
            'start_month' => '2024-01-01',
            'start_amount' => 1000,
        ]);
        $this->victimMonth = BudgetMonth::create([
            'budget_id' => $this->victimBudget->id,
            'month' => '2024-01-01',
            'budgeted_amount' => 300,
            'realized_amount' => 200,
        ]);
    }

    public function test_cannot_show_another_users_budget(): void
    {
        $response = $this->actingAs($this->attacker)->get(route('budgets.show', $this->victimBudget->id));

        $response->assertStatus(404);
    }

    public function test_cannot_update_month_of_another_users_budget(): void
    {
        $response = $this->actingAs($this->attacker)->post(route('budgets.updateMonth', $this->victimBudget->id), [
            'month' => '2024-01-01',
            'budgeted_amount' => 999,
            'realized_amount' => 999,
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('budget_months', [
            'id' => $this->victimMonth->id,
            'budgeted_amount' => 300,
            'realized_amount' => 200,
        ]);
        $this->assertEquals(1, BudgetMonth::where('budget_id', $this->victimBudget->id)->count());
    }

    public function test_cannot_update_month_range_of_another_users_budget(): void
    {
        $response = $this->actingAs($this->attacker)->post(route('budgets.updateMonthsRange', $this->victimBudget->id), [
            'from_month' => '2024-01',
            'to_month' => '2024-03',
            'budgeted_amount' => 999,
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('budget_months', [
            'id' => $this->victimMonth->id,
            'budgeted_amount' => 300,
            'realized_amount' => 200,
        ]);
        $this->assertEquals(1, BudgetMonth::where('budget_id', $this->victimBudget->id)->count());
    }

    public function test_cannot_destroy_another_users_budget(): void
    {
        $response = $this->actingAs($this->attacker)->delete(route('budgets.destroy', $this->victimBudget->id));

        $response->assertStatus(404);
        $this->assertDatabaseHas('budgets', ['id' => $this->victimBudget->id]);
        $this->assertDatabaseHas('budget_months', ['id' => $this->victimMonth->id]);
    }

    public function test_cannot_list_reallocations_of_another_users_budget(): void
    {
        $response = $this->actingAs($this->attacker)->get(route('reallocations.index', $this->victimBudget->id));

        $response->assertStatus(404);
    }

    public function test_cannot_store_reallocation_into_another_users_budget(): void
    {
        $attackerBudget = Budget::factory()->create([
            'user_id' => $this->attacker->id,
            'start_month' => '2024-01-01',
            'start_amount' => 500,
        ]);

        $response = $this->actingAs($this->attacker)->post(route('reallocations.store', $this->victimBudget->id), [
            'source_budget_id' => $attackerBudget->id,
            'month' => '2024-02',
            'amount' => 100,
        ]);

        $response->assertStatus(404);
        $this->assertEquals(0, Reallocation::count());
    }

    public function test_cannot_use_another_users_budget_as_reallocation_source(): void
    {
        $attackerBudget = Budget::factory()->create([
            'user_id' => $this->attacker->id,
            'start_month' => '2024-01-01',
            'start_amount' => 500,
        ]);

        $response = $this->actingAs($this->attacker)->post(route('reallocations.store', $attackerBudget->id), [
            'source_budget_id' => $this->victimBudget->id,
            'month' => '2024-02',
            'amount' => 100,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('source_budget_id');
        $this->assertEquals(0, Reallocation::count());
    }

    public function test_cannot_update_a_reallocation_that_does_not_involve_ones_budget(): void
    {
        $victimSource = Budget::factory()->create([
            'user_id' => $this->owner->id,
            'start_month' => '2024-01-01',
            'start_amount' => 800,
        ]);
        $reallocation = Reallocation::create([
            'recipient_budget_id' => $this->victimBudget->id,
            'source_budget_id' => $victimSource->id,
            'month' => '2024-02-01',
            'amount' => 100,
        ]);
        $attackerBudget = Budget::factory()->create([
            'user_id' => $this->attacker->id,
            'start_month' => '2024-01-01',
            'start_amount' => 500,
        ]);

        $response = $this->actingAs($this->attacker)->patch(
            route('reallocations.update', ['budget' => $attackerBudget->id, 'reallocation' => $reallocation->id]),
            ['amount' => 999],
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('reallocations', [
            'id' => $reallocation->id,
            'amount' => 100,
        ]);
    }

    public function test_cannot_update_reallocation_using_victims_budget_in_url(): void
    {
        $victimSource = Budget::factory()->create([
            'user_id' => $this->owner->id,
            'start_month' => '2024-01-01',
            'start_amount' => 800,
        ]);
        $reallocation = Reallocation::create([
            'recipient_budget_id' => $this->victimBudget->id,
            'source_budget_id' => $victimSource->id,
            'month' => '2024-02-01',
            'amount' => 100,
        ]);

        $response = $this->actingAs($this->attacker)->patch(
            route('reallocations.update', ['budget' => $this->victimBudget->id, 'reallocation' => $reallocation->id]),
            ['amount' => 999],
        );

        $response->assertStatus(404);
        $this->assertDatabaseHas('reallocations', [
            'id' => $reallocation->id,
            'amount' => 100,
        ]);
    }

    public function test_cannot_destroy_a_reallocation_that_does_not_involve_ones_budget(): void
    {
        $victimSource = Budget::factory()->create([
            'user_id' => $this->owner->id,
            'start_month' => '2024-01-01',
            'start_amount' => 800,
        ]);
        $reallocation = Reallocation::create([
            'recipient_budget_id' => $this->victimBudget->id,
            'source_budget_id' => $victimSource->id,
            'month' => '2024-02-01',
            'amount' => 100,
        ]);
        $attackerBudget = Budget::factory()->create([
            'user_id' => $this->attacker->id,
            'start_month' => '2024-01-01',
            'start_amount' => 500,
        ]);

        $response = $this->actingAs($this->attacker)->delete(
            route('reallocations.destroy', ['budget' => $attackerBudget->id, 'reallocation' => $reallocation->id]),
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('reallocations', ['id' => $reallocation->id]);
    }

    public function test_cannot_destroy_reallocation_using_victims_budget_in_url(): void
    {
        $victimSource = Budget::factory()->create([
            'user_id' => $this->owner->id,
            'start_month' => '2024-01-01',
            'start_amount' => 800,
        ]);
        $reallocation = Reallocation::create([
            'recipient_budget_id' => $this->victimBudget->id,
            'source_budget_id' => $victimSource->id,
            'month' => '2024-02-01',
            'amount' => 100,
        ]);

        $response = $this->actingAs($this->attacker)->delete(
            route('reallocations.destroy', ['budget' => $this->victimBudget->id, 'reallocation' => $reallocation->id]),
        );

        $response->assertStatus(404);
        $this->assertDatabaseHas('reallocations', ['id' => $reallocation->id]);
    }

    public function test_cannot_destroy_another_users_budget_via_reallocation_route_parameters(): void
    {
        // The reallocation routes resolve the budget first, so a foreign budget
        // id in the URL must 404 before the reallocation is ever touched.
        $response = $this->actingAs($this->attacker)->delete(
            route('reallocations.destroy', ['budget' => $this->victimBudget->id, 'reallocation' => 999999]),
        );

        $response->assertStatus(404);
        $this->assertDatabaseHas('budgets', ['id' => $this->victimBudget->id]);
    }
}
