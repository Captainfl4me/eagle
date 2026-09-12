<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetMonth;
use App\Models\Reallocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReallocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reallocation_changes_detail_page_total()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        BudgetMonth::create(['budget_id' => $recipient->id, 'month' => '2024-01-01', 'budgeted_amount' => 300, 'realized_amount' => 200]);

        $response = $this->get(route('budgets.show', $recipient->id));
        $response->assertStatus(200);
        $response->assertSee('$1,100.00'); // start 1000 + 100

        $source = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);

        $response = $this->post(route('reallocations.store', $recipient->id), [
            'source_budget_id' => $source->id,
            'month' => '2024-02',
            'amount' => 200,
        ]);
        $response->assertRedirect(route('budgets.show', $recipient->id));

        // Feb total = 1000 + 100 (Jan) + 200 (reallocation in) = 1300
        $response = $this->get(route('budgets.show', ['id' => $recipient->id, 'month' => '2024-02']));
        $response->assertSee('$1,300.00');
    }

    public function test_source_budget_total_decreases_after_reallocation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $source = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);

        $response = $this->get(route('budgets.show', $source->id));
        $response->assertSee('$500.00');

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);

        $this->post(route('reallocations.store', $recipient->id), [
            'source_budget_id' => $source->id,
            'recipient_budget_id' => $recipient->id,
            'month' => '2024-02',
            'amount' => 200,
        ]);

        // Source Feb total = 500 - 200 = 300
        $response = $this->get(route('budgets.show', ['id' => $source->id, 'month' => '2024-02']));
        $response->assertSee('$300.00');
    }

    public function test_negative_months_show_alert()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        BudgetMonth::create(['budget_id' => $budget->id, 'month' => '2024-01-01', 'budgeted_amount' => 100, 'realized_amount' => 200]); // 900
        BudgetMonth::create(['budget_id' => $budget->id, 'month' => '2024-02-01', 'budgeted_amount' => 0, 'realized_amount' => 1000]); // -100

        $response = $this->get(route('budgets.show', $budget->id));
        $response->assertSee('Alert');
        $response->assertSee('text-red-600');
    }

    public function test_list_shows_net_borrowed_per_budget()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $source = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);
        Reallocation::create([
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => '2024-01-01',
            'amount' => 250,
        ]);

        // source: net_borrowed = 250 (positive ⇒ net lender)
        $this->get(route('budgets.index'))->assertSee('Lent $250.00');
        // recipient: net_borrowed = -250 (negative ⇒ net borrower)
        $this->get(route('budgets.index'))->assertSee('Borrowed $250.00');
    }

    public function test_store_reallocation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $source = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);

        $response = $this->post(route('reallocations.store', $recipient->id), [
            'source_budget_id' => $source->id,
            'month' => '2024-02',
            'amount' => 250,
        ]);
        $response->assertRedirect(route('budgets.show', $recipient->id));

        $this->assertDatabaseHas('reallocations', [
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => '2024-02-01',
            'amount' => 250,
        ]);
    }

    public function test_self_reallocation_is_rejected()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);

        $response = $this->post(route('reallocations.store', $budget->id), [
            'recipient_budget_id' => $budget->id,
            'source_budget_id' => $budget->id,
            'month' => '2024-02',
            'amount' => 250,
        ]);
        $response->assertRedirectBack()->assertSessionHasErrors();
    }

    public function test_duplicate_reallocation_is_rejected()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $source = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);

        // First reallocation for (recipient, source, month) succeeds.
        $this->post(route('reallocations.store', $recipient->id), [
            'source_budget_id' => $source->id,
            'month' => '2024-02',
            'amount' => 250,
        ])->assertRedirect(route('budgets.show', $recipient->id));

        // A second stack for the same (recipient, source, month) is rejected.
        $response = $this->post(route('reallocations.store', $recipient->id), [
            'source_budget_id' => $source->id,
            'month' => '2024-02',
            'amount' => 100,
        ]);
        $response->assertRedirectBack()->assertSessionHasErrors(['amount']);

        // Only the original row remains (rows are not summed or stacked).
        $this->assertDatabaseMissing('reallocations', [
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => '2024-02-01',
            'amount' => 100,
        ]);
    }

    public function test_duplicate_allowed_different_same_unit_month()
    {
        // A different (recipient, source, month) is not a duplicate, even for the same source/destination pair.
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $source = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);

        $this->post(route('reallocations.store', $recipient->id), [
            'source_budget_id' => $source->id,
            'month' => '2024-02',
            'amount' => 250,
        ])->assertRedirect(route('budgets.show', $recipient->id));

        // Same pair, different month, is allowed.
        $this->post(route('reallocations.store', $recipient->id), [
            'source_budget_id' => $source->id,
            'month' => '2024-03',
            'amount' => 100,
        ])->assertRedirect(route('budgets.show', $recipient->id));

        $this->assertDatabaseHas('reallocations', [
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => '2024-02-01',
        ]);
        $this->assertDatabaseHas('reallocations', [
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => '2024-03-01',
        ]);
    }

    public function test_reallocations_list_page_shows_all_months()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'name' => 'Recipient', 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $source = Budget::factory()->create(['user_id' => $user->id, 'name' => 'Source', 'start_month' => '2024-01-01', 'start_amount' => 500]);
        Reallocation::create([
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => '2024-02-01',
            'amount' => 250,
        ]);
        Reallocation::create([
            'recipient_budget_id' => $source->id,
            'source_budget_id' => $recipient->id,
            'month' => '2024-04-01',
            'amount' => 50,
        ]);

        // The dedicated list page shows reallocations across ALL months.
        $response = $this->get(route('reallocations.index', $recipient->id));
        $response->assertStatus(200);
        $response->assertSee('Source → Recipient');
        $response->assertSee('Recipient → Source');
        $response->assertSee('February');
        $response->assertSee('April');
    }

    public function test_update_rejects_reallocation_without_access_to_it()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Three budgets owned by the same user; the reallocation links only A and B.
        $budgetA = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $budgetB = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);
        $budgetC = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 300]);
        $reallocation = Reallocation::create([
            'recipient_budget_id' => $budgetA->id,
            'source_budget_id' => $budgetB->id,
            'month' => '2024-02-01',
            'amount' => 100,
        ]);

        // Editing via a budget that the reallocation does NOT involve is forbidden.
        $response = $this->patch(route('reallocations.update', [$budgetC->id, $reallocation->id]), [
            'amount' => 200,
        ]);
        $response->assertStatus(403);

        // The record was left untouched.
        $this->assertDatabaseHas('reallocations', ['id' => $reallocation->id, 'amount' => 100]);
    }

    public function test_destroy_rejects_reallocation_without_access_to_it()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budgetA = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $budgetB = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);
        $budgetC = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 300]);
        $reallocation = Reallocation::create([
            'recipient_budget_id' => $budgetA->id,
            'source_budget_id' => $budgetB->id,
            'month' => '2024-02-01',
            'amount' => 100,
        ]);

        $response = $this->delete(route('reallocations.destroy', [$budgetC->id, $reallocation->id]));
        $response->assertStatus(403);

        // The record still exists.
        $this->assertDatabaseHas('reallocations', ['id' => $reallocation->id]);
    }

    public function test_selector_excludes_the_current_budget()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'name' => 'Recipient Budget', 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        Budget::factory()->create(['user_id' => $user->id, 'name' => 'Another Budget', 'start_month' => '2024-01-01', 'start_amount' => 500]);

        $response = $this->get(route('budgets.show', $recipient->id));
        $response->assertStatus(200);
        // The current budget must NOT appear as an option in the source selector
        // (but its name still appears as the page heading and recipient field).
        $response->assertDontSee('Recipient Budget — net', false);
        $response->assertSee('Another Budget — net', false);
    }

    public function test_cannot_reallocate_from_another_users_budget()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $other = User::factory()->create();
        $otherBudget = Budget::factory()->create(['user_id' => $other->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);
        $myBudget = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);

        $response = $this->post(route('reallocations.store', $myBudget->id), [
            'recipient_budget_id' => $myBudget->id,
            'source_budget_id' => $otherBudget->id,
            'month' => '2024-02',
            'amount' => 250,
        ]);
        $response->assertSessionHasErrors(['source_budget_id']);
    }

    public function test_update_reallocation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $source = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);
        $reallocation = Reallocation::create([
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => '2024-02-01',
            'amount' => 250,
        ]);

        $response = $this->patch(route('reallocations.update', [$recipient->id, $reallocation->id]), [
            'amount' => 500,
        ]);
        $response->assertRedirect(route('budgets.show', $recipient->id));

        // Only the amount changes; the month is immutable.
        $this->assertDatabaseHas('reallocations', [
            'id' => $reallocation->id,
            'amount' => 500,
            'month' => '2024-02-01',
        ]);
    }

    public function test_destroy_reallocation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $source = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 500]);
        $reallocation = Reallocation::create([
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => '2024-02-01',
            'amount' => 250,
        ]);

        $response = $this->delete(route('reallocations.destroy', [$recipient->id, $reallocation->id]));
        $response->assertRedirect(route('budgets.show', $recipient->id));
        $this->assertDatabaseMissing('reallocations', ['id' => $reallocation->id]);
    }

    public function test_show_page_lists_reallocations_for_budget()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'name' => 'Recipient', 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $source = Budget::factory()->create(['user_id' => $user->id, 'name' => 'Source', 'start_month' => '2024-01-01', 'start_amount' => 500]);
        Reallocation::create([
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => '2024-02-01',
            'amount' => 250,
        ]);

        $response = $this->get(route('budgets.show', ['id' => $recipient->id, 'month' => '2024-02']));
        $response->assertStatus(200);
        $response->assertSee('Source → Recipient');
        $response->assertSee('250.00');

        // Other months show no reallocations (list is filtered to the displayed month).
        $response = $this->get(route('budgets.show', ['id' => $recipient->id, 'month' => '2024-01']));
        $response->assertStatus(200);
        $response->assertDontSee('Source → Recipient');
    }

    public function test_unauthenticated_user_cannot_access()
    {
        $response = $this->get(route('reallocations.index', [1]));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_alert_appears_and_clears_after_resolving_reallocation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create(['user_id' => $user->id, 'name' => 'Negative Budget', 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        BudgetMonth::create(['budget_id' => $budget->id, 'month' => '2024-01-01', 'budgeted_amount' => 0, 'realized_amount' => 1500]); // envelope = -500
        $source = Budget::factory()->create(['user_id' => $user->id, 'name' => 'Source Budget', 'start_month' => '2024-01-01', 'start_amount' => 500]);

        // Alert shows on the dashboard while the envelope is negative.
        $this->get('/')->assertSee('Alert:')->assertSee('Negative Budget');

        // Borrow 600 in January: envelope = 1000 - 1500 + 600 = 100 (positive).
        $this->post(route('reallocations.store', $budget->id), [
            'source_budget_id' => $source->id,
            'month' => '2024-01',
            'amount' => 600,
        ])->assertRedirect(route('budgets.show', $budget->id));

        // The alert cleared.
        $response = $this->get('/');
        $response->assertDontSee('Alert:');
    }

    public function test_create_form_uses_the_displayed_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create(['user_id' => $user->id, 'start_month' => '2024-01-01', 'start_amount' => 1000]);

        $response = $this->get(route('budgets.show', ['id' => $budget->id, 'month' => '2024-02']));
        $response->assertStatus(200);
        // No month input for the user; the displayed month is submitted via a hidden field.
        $response->assertSee('name="month" value="2024-02"', false);
    }

    public function test_selector_lists_all_budgets_sorted_by_current_net()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $recipient = Budget::factory()->create(['user_id' => $user->id, 'name' => 'Recipient Budget', 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        Budget::factory()->create(['user_id' => $user->id, 'name' => 'Rich Budget', 'start_month' => '2024-01-01', 'start_amount' => 2000]);
        Budget::factory()->create(['user_id' => $user->id, 'name' => 'Mid Budget', 'start_month' => '2024-01-01', 'start_amount' => 500]);
        $poor = Budget::factory()->create(['user_id' => $user->id, 'name' => 'Poor Budget', 'start_month' => '2024-01-01', 'start_amount' => 100]);
        BudgetMonth::create(['budget_id' => $poor->id, 'month' => '2024-01-01', 'budgeted_amount' => 0, 'realized_amount' => 200]); // net = -100

        $response = $this->get(route('budgets.show', $recipient->id));
        $response->assertStatus(200);
        // All budgets are listed (negative nets not hidden), sorted by net descending.
        $response->assertSeeInOrder(['Rich Budget', 'Mid Budget', 'Poor Budget']);
        $response->assertSee('net $-100.00');
    }
}
