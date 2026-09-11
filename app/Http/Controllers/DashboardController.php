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

                return $budget;
            });

        // Budgets whose running envelope dips below zero in at least one month.
        $alerts = $budgets->filter(fn (Budget $b) => $b->negative_months->isNotEmpty())->values();

        return view('dashboard', compact('budgets', 'alerts'));
    }
}
