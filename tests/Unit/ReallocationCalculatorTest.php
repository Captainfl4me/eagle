<?php

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\BudgetMonth;
use App\Models\Reallocation;
use App\Models\User;
use App\Services\BudgetCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReallocationCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function calculator(): BudgetCalculator
    {
        return app(BudgetCalculator::class);
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_tail_total_incorporates_reallocations()
    {
        $calculator = $this->calculator();

        $recipient = Budget::create(['user_id' => $this->user()->id, 'name' => 'Recipient', 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $source = Budget::create(['user_id' => $this->user()->id, 'name' => 'Source', 'start_month' => '2024-01-01', 'start_amount' => 500]);

        BudgetMonth::create(['budget_id' => $recipient->id, 'month' => '2024-01-01', 'budgeted_amount' => 300, 'realized_amount' => 200]);
        BudgetMonth::create(['budget_id' => $recipient->id, 'month' => '2024-02-01', 'budgeted_amount' => 400, 'realized_amount' => 500]);

        // Baseline (no reallocations): Jan = 1000 + 100 = 1100; Feb = 1100 - 100 = 1000
        $this->assertSame(1100.00, $calculator->tailTotal($recipient, Carbon::parse('2024-01-01')));
        $this->assertSame(1000.00, $calculator->tailTotal($recipient, Carbon::parse('2024-02-01')));

        // A single reallocation of 200 moves money from source into recipient at Feb.
        Reallocation::create([
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => '2024-02-01',
            'amount' => 200,
        ]);

        // Jan is unaffected (reallocation is at Feb); Feb recipient is up by 200.
        $this->assertSame(1100.00, $calculator->tailTotal($recipient, Carbon::parse('2024-01-01')));
        $this->assertSame(1200.00, $calculator->tailTotal($recipient, Carbon::parse('2024-02-01')));

        // Source is down by 200 from Feb onward: 500 - 200 = 300.
        $this->assertSame(300.00, $calculator->tailTotal($source, Carbon::parse('2024-02-01')));
    }

    public function test_negative_months_are_detected()
    {
        $calculator = $this->calculator();

        $budget = Budget::create(['user_id' => $this->user()->id, 'name' => 'B', 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        BudgetMonth::create(['budget_id' => $budget->id, 'month' => '2024-01-01', 'budgeted_amount' => 100, 'realized_amount' => 200]); // total 900 (positive)
        BudgetMonth::create(['budget_id' => $budget->id, 'month' => '2024-02-01', 'budgeted_amount' => 0, 'realized_amount' => 1000]); // total -100 (negative)
        BudgetMonth::create(['budget_id' => $budget->id, 'month' => '2024-03-01', 'budgeted_amount' => 0, 'realized_amount' => 500]); // total -1500 (negative)

        $negatives = $calculator->negativeMonths($budget);

        $this->assertCount(2, $negatives);
        $this->assertSame('2024-02-01', $negatives[0]->format('Y-m-d'));
        $this->assertSame('2024-03-01', $negatives[1]->format('Y-m-d'));
    }

    public function test_net_borrowed_positive_means_net_lender()
    {
        $calculator = $this->calculator();

        $recipient = Budget::create(['user_id' => $this->user()->id, 'name' => 'Recipient', 'start_month' => '2024-01-01', 'start_amount' => 1000]);
        $source1 = Budget::create(['user_id' => $this->user()->id, 'name' => 'Source1', 'start_month' => '2024-01-01', 'start_amount' => 500]);
        $source2 = Budget::create(['user_id' => $this->user()->id, 'name' => 'Source2', 'start_month' => '2024-01-01', 'start_amount' => 500]);

        Reallocation::create(['recipient_budget_id' => $recipient->id, 'source_budget_id' => $source1->id, 'month' => '2024-01-01', 'amount' => 300]);
        Reallocation::create(['recipient_budget_id' => $recipient->id, 'source_budget_id' => $source2->id, 'month' => '2024-01-01', 'amount' => 100]);

        // Recipient: in 400, out 0 → net_borrowed = -400 (net borrower)
        $this->assertSame(-400.00, $calculator->netBorrowed($recipient));
        // Source1: out 300 → net_borrowed = 300 (net lender)
        $this->assertSame(300.00, $calculator->netBorrowed($source1));
        // Source2: out 100 → net_borrowed = 100
        $this->assertSame(100.00, $calculator->netBorrowed($source2));
    }

    public function test_net_borrowed_is_zero_without_reallocations()
    {
        $calculator = $this->calculator();
        $budget = Budget::create(['user_id' => $this->user()->id, 'name' => 'B', 'start_month' => '2024-01-01', 'start_amount' => 1000]);

        $this->assertSame(0.00, $calculator->netBorrowed($budget));
    }
}
