<?php

namespace App\Services;

use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Money-domain computations for a budget's cumulative envelope.
 *
 * Envelope at month M = start_amount
 *                       + Σ(months ≤ M: budgeted − realized)
 *                       + Σ(recipient reallocations ≤ M: +amount)
 *                       − Σ(source  reallocations ≤ M: +amount)
 *
 * net_borrowed = Σ(out) − Σ(in) (positive ⇒ net lender).
 */
class BudgetCalculator
{
    /**
     * Cumulative envelope for the budget up to and including the given month.
     */
    public function envelopeAtMonth(Budget $budget, Carbon $month): float
    {
        return $this->tailTotal($budget, $month);
    }

    /**
     * The tail total for a specific month, including all reallocation effects.
     */
    public function tailTotal(Budget $budget, Carbon $month): float
    {
        $total = $budget->start_amount;

        foreach ($budget->months()->get() as $m) {
            if ($m->month->lte($month)) {
                $total += $m->budgeted_amount - $m->realized_amount;
            }
        }

        // Money moved INTO this budget counts as income.
        foreach ($budget->reallocationsIn()->get() as $r) {
            if ($r->month->lte($month)) {
                $total += $r->amount;
            }
        }

        // Money moved OUT of this budget counts as an outflow.
        foreach ($budget->reallocationsOut()->get() as $r) {
            if ($r->month->lte($month)) {
                $total -= $r->amount;
            }
        }

        return $total;
    }

    /**
     * Months at which the cumulative envelope is negative.
     */
    public function negativeMonths(Budget $budget): Collection
    {
        $result = [];
        foreach ($budget->months()->get() as $m) {
            if ($this->tailTotal($budget, $m->month) < 0) {
                $result[] = $m->month;
            }
        }

        return collect($result);
    }

    /**
     * Net reallocation position: Σ(out) − Σ(in).
     * Positive means net lender; negative means net borrower.
     */
    public function netBorrowed(Budget $budget): float
    {
        $in = $budget->reallocationsIn()->sum('amount');
        $out = $budget->reallocationsOut()->sum('amount');

        return (float) ($out - $in);
    }
}
