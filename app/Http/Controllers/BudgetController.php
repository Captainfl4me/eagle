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
    public function show($id)
    {
        $budget = Budget::where('user_id', Auth::id())->findOrFail($id);
        return view('budgets.show', compact('budget'));
    }
}

