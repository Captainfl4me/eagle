<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\BudgetCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard: per-budget summary for the latest month, net-borrowed metric,
     * and the envelope-positivity alert banner (reallocations included).
     */
    public function index()
    {
        $calculator = app(BudgetCalculator::class);

        $budgets = Budget::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Budget $budget) use ($calculator) {
                $latest = $budget->months()->max('month');
                $latest = $latest ? Carbon::parse($latest) : $budget->start_month;
                $month = now()->gt($latest) ? now()->copy() : $latest;
                $budget->month = $month;
                $budget->total = $calculator->tailTotal($budget, $month);
                $budget->net_borrowed = $calculator->netBorrowed($budget);
                $budget->negative_months = $calculator->negativeMonths($budget);

                // Latest recorded month's budgeted vs realized amounts (for the
                // cash-flow chart). Uses the newest month that actually exists so
                // the chart always reflects real data.
                $latestMonth = $budget->months()->latest('month')->first();
                $budget->budgeted = $latestMonth ? (float) $latestMonth->budgeted_amount : 0.0;
                $budget->realized = $latestMonth ? (float) $latestMonth->realized_amount : 0.0;
                $budget->cashflow_month = $latestMonth?->month;

                return $budget;
            });

        // Budgets whose running envelope dips below zero in at least one month.
        $alerts = $budgets->filter(fn (Budget $b) => $b->negative_months->isNotEmpty())->values();

        // Chart payloads. The pie chart shows each budget's share of the current
        // envelope total; the cash-flow chart compares budgeted vs realized for the
        // most recent month.
        $pie = [
            'labels' => $budgets->map(fn ($b) => $b->name)->values()->all(),
            'values' => $budgets->map(fn ($b) => (float) $b->total)->values()->all(),
        ];

        // The newest month that has any recorded amounts across all budgets, used
        // for the cash-flow chart subtitle (falls back to the dashboard month).
        $cashflowMonth = $budgets
            ->pluck('cashflow_month')
            ->filter()
            ->max();

        $cashflow = [
            'month' => $cashflowMonth
                ? Carbon::parse($cashflowMonth)->format('F Y')
                : ($budgets->first()?->month?->format('F Y') ?? now()->format('F Y')),
            'labels' => $budgets->map(fn ($b) => $b->name)->values()->all(),
            'budgeted' => $budgets->map(fn ($b) => $b->budgeted)->values()->all(),
            'realized' => $budgets->map(fn ($b) => $b->realized)->values()->all(),
        ];

        return view('dashboard', compact('budgets', 'alerts', 'pie', 'cashflow'));
    }
}
