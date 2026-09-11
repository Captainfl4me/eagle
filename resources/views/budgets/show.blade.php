<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }} - Budget Details</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background text-text flex min-h-screen flex-col">
    @include('partials.header')
        <div class="w-full max-w-lg mx-auto px-4 py-8 space-y-6">
            <div class="flex justify-between items-start gap-4">
                <a href="{{ route('budgets.index') }}" class="text-sm text-muted hover:text-text">← Back to Budgets</a>
                <h1 class="text-2xl font-bold text-center flex-1">{{ $budget->name }}</h1>
            </div>

            @if (session('status'))
                <div class="p-3 text-sm bg-surface-alt text-text rounded">{{ session('status') }}</div>
            @endif

            <!-- Alert banner for negative cumulative envelope months -->
            @if ($negativeMonths->isNotEmpty())
                <div class="p-3 text-sm bg-red-50 text-red-700 border border-red-200 rounded dark:bg-red-900/20 dark:text-red-300 dark:border-red-800">
                    <strong>Alert:</strong> The cumulative envelope goes negative in the following month(s):
                    {{ $negativeMonths->map(fn($m) => $m->format('F Y'))->implode(', ') }}.
                </div>
            @endif

            <!-- Summary Card -->
            <div class="p-4 border border-line rounded-lg bg-surface shadow-sm space-y-1">
                <p><strong>Start month:</strong> {{ $budget->start_month->format('Y‑m') }}</p>
                <p><strong>Start amount:</strong> ${{ number_format($budget->start_amount, 2) }}</p>
                <p><strong>Total amount:</strong> <span class="{{ $totalAmount < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">${{ number_format($totalAmount, 2) }}</span></p>
            </div>

            <!-- Month navigation -->
            <div class="flex items-center justify-center space-x-4">
                @php
                    $prevMonth = $currentMonth->copy()->subMonth();
                    $nextMonth = $currentMonth->copy()->addMonth();
                    $disablePrev = $prevMonth->lt($budget->start_month);
                @endphp
                @if(!$disablePrev)
                    <a href="{{ route('budgets.show', ['id' => $budget->id, 'month' => $prevMonth->format('Y-m')]) }}" class="text-xl font-bold hover:text-primary">←</a>
                @else
                    <span class="text-xl text-muted">←</span>
                @endif
                <span class="font-medium">{{ $currentMonth->format('F Y') }}</span>
                <a href="{{ route('budgets.show', ['id' => $budget->id, 'month' => $nextMonth->format('Y-m')]) }}" class="text-xl font-bold hover:text-primary">→</a>
            </div>

            <!-- Edit form for the selected month -->
            <div class="p-4 border border-line rounded-lg bg-surface shadow-sm">
                <h2 class="text-lg font-semibold mb-4">Month details</h2>
                <form method="POST" action="{{ route('budgets.updateMonth', $budget->id) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="month" value="{{ ($currentMonth ?? $budget->start_month)->toDateString() }}" />
                    <div>
                        <label class="block text-sm font-medium">Budgeted Amount</label>
                        <input type="number" step="0.01" name="budgeted_amount" value="{{ old('budgeted_amount', number_format($budgetedDefault, 2, '.', '')) }}"
                               class="mt-1 block w-full rounded-md border border-line bg-surface text-text shadow-sm focus:outline-none focus:ring-2 focus:ring-primary" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Realized Amount</label>
                        <input type="number" step="0.01" name="realized_amount" value="{{ old('realized_amount', optional($monthRecord)->realized_amount ?? '') }}"
                               class="mt-1 block w-full rounded-md border border-line bg-surface text-text shadow-sm focus:outline-none focus:ring-2 focus:ring-primary" required />
                    </div>
                    <button type="submit" class="w-full bg-primary hover:bg-secondary text-white font-bold py-2 px-4 rounded transition-colors">
                        Save Month
                    </button>
                </form>
            </div>

            <!-- Reallocation selector -->
            <div class="p-4 border border-line rounded-lg bg-surface shadow-sm space-y-4">
                <h2 class="text-lg font-semibold">Reallocation</h2>
                <p class="text-sm text-muted">Move money into this budget from another of your budgets, for the month currently displayed ({{ $currentMonth->format('F Y') }}).</p>
                <form method="POST" action="{{ route('reallocations.store', $budget->id) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Recipient Budget</label>
                        <input type="text" readonly value="{{ $budget->name }}"
                               class="mt-1 block w-full rounded-md border border-line bg-surface-alt text-muted" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Source Budget</label>
                        <select name="source_budget_id" class="mt-1 block w-full rounded-md border border-line bg-surface text-text shadow-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Select a budget to reallocate from…</option>
                            @foreach ($selectorBudgets as $sb)
                                <option value="{{ $sb->id }}">
                                    {{ $sb->name }} — net ${{ number_format($sb->current_net, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Month</label>
                        <p class="mt-1 text-sm text-muted">{{ $currentMonth->format('F Y') }}</p>
                        <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Amount</label>
                        <input type="number" step="0.01" name="amount" value="{{ old('amount', '') }}"
                               class="mt-1 block w-full rounded-md border border-line bg-surface text-text shadow-sm focus:outline-none focus:ring-2 focus:ring-primary" required />
                    </div>
                    <button type="submit" class="w-full bg-primary hover:bg-secondary text-white font-bold py-2 px-4 rounded transition-colors">
                        Add Reallocation
                    </button>
                </form>
            </div>

            <!-- Reallocation list (manage) -->
            @if ($reallocations->isNotEmpty())
                <div class="p-4 border border-line rounded-lg bg-surface shadow-sm space-y-4">
                    <h2 class="text-lg font-semibold">Reallocations — {{ $currentMonth->format('F Y') }}</h2>
                    <ul class="space-y-2">
                        @foreach ($reallocations as $r)
                            <li class="flex flex-wrap justify-between items-center gap-2 p-3 border border-line rounded-lg">
                                <div class="min-w-0">
                                    <p class="font-medium">{{ $r->source->name }} → {{ $r->recipient->name }}</p>
                                    <p class="text-sm text-muted">
                                        {{ $r->month->format('F Y') }} ·
                                        <span class="{{ $r->recipient_budget_id === $budget->id ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $r->recipient_budget_id === $budget->id ? 'In' : 'Out' }}
                                        </span>
                                        ${{ number_format($r->amount, 2) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <form method="POST" action="{{ route('reallocations.update', [$budget->id, $r->id]) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', (float) $r->amount) }}"
                                               class="rounded-md border border-line bg-surface text-text shadow-sm text-sm w-24" />
                                        <button type="submit" class="text-sm text-primary hover:underline">Save</button>
                                    </form>
                                    <form method="POST" action="{{ route('reallocations.destroy', [$budget->id, $r->id]) }}" onsubmit="return confirm('Delete this reallocation?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 dark:text-red-400 hover:underline">Delete</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Delete budget button with confirmation -->
            <form method="POST" action="{{ route('budgets.destroy', $budget) }}" onsubmit="return confirm('Are you sure you want to delete this budget and all its data?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors">
                    Delete Budget
                </button>
            </form>
        </div>
    </body>
</html>
