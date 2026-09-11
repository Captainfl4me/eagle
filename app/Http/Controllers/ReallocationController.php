<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Reallocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReallocationController extends Controller
{
    /**
     * List the reallocations involving a budget (as recipient or source).
     */
    public function index($budgetId)
    {
        $budget = Budget::where('user_id', Auth::id())->findOrFail($budgetId);
        $reallocations = Reallocation::where(function ($q) use ($budget) {
            $q->where('recipient_budget_id', $budget->id)
                ->orWhere('source_budget_id', $budget->id);
        })
            ->with(['recipient', 'source'])
            ->orderBy('month', 'asc')
            ->get();

        return view('reallocations.index', compact('budget', 'reallocations'));
    }

    /**
     * Store a new reallocation into the budget from the URL.
     */
    public function store(Request $request, $budgetId)
    {
        $recipient = Budget::where('user_id', Auth::id())->findOrFail($budgetId);

        $data = $request->validate([
            'source_budget_id' => ['required', 'integer'],
            'month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        // The source must be one of the user's own budgets (sharing not implemented yet).
        $source = Budget::where('user_id', Auth::id())->find($data['source_budget_id']);
        if (! $source) {
            return redirect()->back()
                ->withErrors(['source_budget_id' => 'The selected source budget is invalid.'])
                ->withInput();
        }
        if ($source->id === $recipient->id) {
            return redirect()->back()
                ->withErrors(['source_budget_id' => 'Cannot reallocate within the same budget.'])
                ->withInput();
        }

        $month = $data['month'].'-01';

        // One row per (recipient, source, month) — stacked rows are not allowed.
        $exists = Reallocation::where('recipient_budget_id', $recipient->id)
            ->where('source_budget_id', $source->id)
            ->where('month', $month)
            ->exists();
        if ($exists) {
            return redirect()->back()
                ->withErrors(['amount' => 'A reallocation from this source already exists for that month. Edit it instead.'])
                ->withInput();
        }

        Reallocation::create([
            'recipient_budget_id' => $recipient->id,
            'source_budget_id' => $source->id,
            'month' => $month,
            'amount' => $data['amount'],
        ]);

        return redirect()->route('budgets.show', $recipient->id)->with('status', 'Reallocation added.');
    }

    /**
     * Update an existing reallocation (amount only; the month is immutable).
     */
    public function update(Request $request, $budgetId, $reallocationId)
    {
        $budget = Budget::where('user_id', Auth::id())->findOrFail($budgetId);
        $reallocation = Reallocation::findOrFail($reallocationId);

        if (! $this->involves($reallocation, $budget)) {
            abort(403);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $reallocation->update([
            'amount' => $data['amount'],
        ]);

        return redirect()->route('budgets.show', $budget->id)->with('status', 'Reallocation updated.');
    }

    /**
     * Delete a reallocation.
     */
    public function destroy($budgetId, $reallocationId)
    {
        $budget = Budget::where('user_id', Auth::id())->findOrFail($budgetId);
        $reallocation = Reallocation::findOrFail($reallocationId);

        if (! $this->involves($reallocation, $budget)) {
            abort(403);
        }

        $reallocation->delete();

        return redirect()->route('budgets.show', $budget->id)->with('status', 'Reallocation deleted.');
    }

    /**
     * Whether the reallocation involves the given budget (as recipient or source).
     */
    private function involves(Reallocation $reallocation, Budget $budget): bool
    {
        return (int) $reallocation->recipient_budget_id === (int) $budget->id
            || (int) $reallocation->source_budget_id === (int) $budget->id;
    }
}
