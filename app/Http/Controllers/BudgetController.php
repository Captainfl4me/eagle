<?php

namespace App\Http\Controllers;

use App\Models\Budget;
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
        $startMonth = $request->input('start_month') . '-01';

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
        $budgets = Budget::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
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
        // Determine the current displayed month (default to today if within range, else start month)
        $currentMonth = $request->query('month') ? \Carbon\Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth() : now()->startOfMonth();
        // Ensure not before start month
        if ($currentMonth->lt($budget->start_month)) {
            $currentMonth = $budget->start_month;
        }
        // Find or create month record for current month
        $monthRecord = $budget->months()->firstOrCreate([
            'month' => $currentMonth,
        ], [
            'budgeted_amount' => $budget->start_amount,
            'realized_amount' => 0,
        ]);

        return view('budgets.show', compact('budget', 'months', 'currentMonth', 'monthRecord'));
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
        $month = \Carbon\Carbon::parse($request->input('month'))->startOfMonth();
        $budgetMonth = $budget->months()->firstOrCreate([
            'month' => $month,
        ]);
        $budgetMonth->update([
            'budgeted_amount' => $request->input('budgeted_amount'),
            'realized_amount' => $request->input('realized_amount'),
        ]);
        return redirect()->route('budgets.show', $budget->id)->with('status', 'Month updated.');
    }
}


