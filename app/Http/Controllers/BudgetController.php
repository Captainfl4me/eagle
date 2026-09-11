<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Reallocation;
use App\Services\BudgetCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    /**
     * Show the form to create a new budget.
     */
    public function create()
    {
        return view('budgets.create');
    }

    /**
     * Store a newly created budget.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_month' => ['required', 'date_format:Y-m'], // HTML month input returns YYYY-MM
            'start_amount' => ['required', 'numeric', 'min:0'],
        ]);

        // Convert month to a full date (first day of the month)
        $startMonth = $request->input('start_month').'-01';

        Budget::create([
            'user_id' => Auth::id(),
            'name' => $request->input('name'),
            'start_month' => $startMonth,
            'start_amount' => $request->input('start_amount'),
        ]);

        // Redirect to home with a simple flash message (optional)
        return redirect('/')->with('status', 'Budget created successfully.');
    }

    /**
     * Display a list of the authenticated user's budgets.
     */
    public function index()
    {
        $calculator = app(BudgetCalculator::class);
        $budgets = Budget::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get()
            ->map(function (Budget $budget) use ($calculator) {
                $latest = $budget->months()->pluck('month')->max() ?? $budget->start_month;
                $budget->total = $calculator->tailTotal($budget, \max(now(), $latest));
                $budget->net_borrowed = $calculator->netBorrowed($budget);

                return $budget;
            });

        return view('budgets.index', compact('budgets'));
    }

    /**
     * Show details for a single budget.
     */
    public function show(Request $request, $id)
    {
        $budget = Budget::where('user_id', Auth::id())->findOrFail($id);
        // Load months for this budget ordered by month
        $months = $budget->months()->orderBy('month', 'asc')->get();

        // Determine the current displayed month
        if ($request->has('month')) {
            $currentMonth = Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth();
        } else {
            // If there are no month records, default to the budget's start month.
            if ($budget->months()->count() === 0) {
                $currentMonth = $budget->start_month;
            } else {
                // Find the most recent month (from today back to the budget start) that already has a record.
                $today = now()->startOfMonth();
                $existing = $budget->months()->pluck('month')->map(fn ($d) => Carbon::parse($d)->format('Y-m'))->toArray();
                $cursor = $today->copy();
                $found = false;
                while ($cursor->gte($budget->start_month)) {
                    if (in_array($cursor->format('Y-m'), $existing)) {
                        $currentMonth = $cursor->copy();
                        $found = true;
                        break;
                    }
                    $cursor->subMonth();
                }
                if (! $found) {
                    $currentMonth = $budget->start_month;
                }
            }
        }
        // Ensure not before start month
        if ($currentMonth->lt($budget->start_month)) {
            $currentMonth = $budget->start_month;
        }
        // Get month record if it exists (do NOT auto‑create)
        $monthRecord = $budget->months()->where('month', $currentMonth)->first();

        // Pre-fill for the budgeted amount: the month's stored value if present,
        // otherwise the most recent month before it, otherwise 0.
        if ($monthRecord !== null) {
            $budgetedDefault = (float) $monthRecord->budgeted_amount;
        } else {
            $previousRecord = $budget->months()
                ->where('month', '<', $currentMonth->toDateString())
                ->orderBy('month', 'desc')
                ->first();
            $budgetedDefault = $previousRecord !== null ? (float) $previousRecord->budgeted_amount : 0.0;
        }

        // Compute total amount up to the selected month (including reallocations)
        $calculator = app(BudgetCalculator::class);
        $totalAmount = $calculator->envelopeAtMonth($budget, $currentMonth);
        $negativeMonths = $calculator->negativeMonths($budget);

        // Only reallocations for the currently displayed month.
        $reallocations = Reallocation::where(function ($q) use ($budget) {
            $q->where('recipient_budget_id', $budget->id)
                ->orWhere('source_budget_id', $budget->id);
        })
            ->where('month', $currentMonth->copy()->startOfMonth()->toDateString())
            ->with(['recipient', 'source'])
            ->orderBy('month', 'asc')
            ->get();

        // Selector: all other budgets of the user with their current net at the
        // selected month, sorted by net descending (negatives/zero not hidden).
        $selectorBudgets = Budget::where('user_id', Auth::id())->where('id', '!=', $budget->id)
            ->get()
            ->each(function (Budget $sb) use ($calculator, $currentMonth) {
                $sb->current_net = $calculator->tailTotal($sb, $currentMonth);
            })
            ->sortByDesc('current_net')
            ->values();

        return view('budgets.show', compact(
            'budget', 'months', 'currentMonth', 'monthRecord', 'budgetedDefault',
            'totalAmount', 'negativeMonths',
            'reallocations', 'selectorBudgets'
        ));
    }

    /**
     * Update budgeted and realized amounts for a specific month.
     */
    public function updateMonth(Request $request, $id)
    {
        $budget = Budget::where('user_id', Auth::id())->findOrFail($id);
        $request->validate([
            'month' => ['required', 'date_format:Y-m-d'],
            'budgeted_amount' => ['required', 'numeric', 'min:0'],
            'realized_amount' => ['required', 'numeric', 'min:0'],
        ]);
        $month = Carbon::parse($request->input('month'))->startOfMonth();
        // Enforce contiguous months: if this is not the start month, the previous month must already exist.
        if ($month->gt($budget->start_month)) {
            $prevMonth = $month->copy()->subMonth();
            $prevExists = $budget->months()->where('month', $prevMonth)->exists();
            if (! $prevExists) {
                return redirect()->back()->withErrors(['month' => "Previous month {$prevMonth->format('Y‑m')} must be created first."]);
            }
        }

        $budgetMonth = $budget->months()->firstOrCreate([
            'month' => $month,
        ]);
        $budgetMonth->update([
            'budgeted_amount' => $request->input('budgeted_amount'),
            'realized_amount' => $request->input('realized_amount'),
        ]);

        return redirect()->route('budgets.show', $budget->id)->with('status', 'Month updated.');
    }

    /**
     * Delete a budget and its month records.
     */
    public function destroy(Request $request, $id)
    {
        $budget = Budget::where('user_id', Auth::id())->findOrFail($id);
        $budget->months()->delete();
        $budget->delete();

        return redirect()->route('budgets.index')->with('status', 'Budget deleted successfully.');
    }
}
