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
        <div class="w-full max-w-md space-y-8">
            <div class="flex justify-between items-start">
                <a href="{{ route('budgets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Budgets</a>
                <h1 class="text-2xl font-bold text-center flex-1 text-center">{{ $budget->name }}</h1>
            </div>

            @if (session('status'))
                <div class="p-3 text-sm bg-gray-100 text-gray-700 rounded">{{ session('status') }}</div>
            @endif

            <!-- Summary Card -->
            <div class="p-4 border rounded-lg bg-white shadow-sm">
                <p><strong>Start month:</strong> {{ $budget->start_month->format('Y‑m') }}</p>
                <p><strong>Start amount:</strong> ${{ number_format($budget->start_amount, 2) }}</p>
                <p><strong>Total amount:</strong> <span class="{{ $totalAmount < 0 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($totalAmount, 2) }}</span></p>
            </div>

            <!-- Alert banner for negative cumulative envelope months -->
            @if ($negativeMonths->isNotEmpty())
                <div class="p-3 text-sm bg-red-50 text-red-700 rounded border border-red-200">
                    <strong>Alert:</strong> The cumulative envelope goes negative in the following month(s):
                    {{ $negativeMonths->map(fn($m) => $m->format('F Y'))->implode(', ') }}.
                </div>
            @endif

            <!-- Month navigation -->
            <div class="flex items-center justify-center space-x-4 mb-4">
                @php
                    $prevMonth = $currentMonth->copy()->subMonth();
                    $nextMonth = $currentMonth->copy()->addMonth();
                    $disablePrev = $prevMonth->lt($budget->start_month);
                @endphp
                @if(!$disablePrev)
                    <a href="{{ route('budgets.show', ['id' => $budget->id, 'month' => $prevMonth->format('Y-m')]) }}" class="text-xl font-bold">←</a>
                @else
                    <span class="text-xl text-gray-400">←</span>
                @endif
                <span class="font-medium">{{ $currentMonth->format('F Y') }}</span>
                <a href="{{ route('budgets.show', ['id' => $budget->id, 'month' => $nextMonth->format('Y-m')]) }}" class="text-xl font-bold">→</a>
            </div>

            <!-- Edit form for the selected month -->
            <form method="POST" action="{{ route('budgets.updateMonth', $budget->id) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="month" value="{{ ($currentMonth ?? $budget->start_month)->toDateString() }}" />
                <div>
                    <label class="block text-sm font-medium text-gray-700">Budgeted Amount</label>
                    <input type="number" step="0.01" name="budgeted_amount" value="{{ old('budgeted_amount', optional($monthRecord)->budgeted_amount ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Realized Amount</label>
                    <input type="number" step="0.01" name="realized_amount" value="{{ old('realized_amount', optional($monthRecord)->realized_amount ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                    Save Month
                </button>
            </form>

            <!-- Reallocation selector -->
            <div class="p-4 border rounded-lg bg-white shadow-sm space-y-4">
                <h2 class="text-lg font-semibold">Reallocation</h2>
                <p class="text-sm text-gray-500">Move money into this budget from another of your budgets, for the month currently displayed ({{ $currentMonth->format('F Y') }}).</p>
                <form method="POST" action="{{ route('reallocations.store', $budget->id) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Recipient Budget</label>
                        <input type="text" readonly value="{{ $budget->name }}"
                               class="mt-1 block w-full rounded-md bg-gray-100 border-gray-300 text-gray-700" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Source Budget</label>
                        <select name="source_budget_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Select a budget to reallocate from…</option>
                            @foreach ($selectorBudgets as $sb)
                                <option value="{{ $sb->id }}">
                                    {{ $sb->name }} — net ${{ number_format($sb->current_net, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Month</label>
                        <p class="mt-1 text-sm text-gray-700">{{ $currentMonth->format('F Y') }}</p>
                        <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="number" step="0.01" name="amount" value="{{ old('amount', '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                        Add Reallocation
                    </button>
                </form>
            </div>

            <!-- Reallocation list (manage) -->
            @if ($reallocations->isNotEmpty())
                <div class="p-4 border rounded-lg bg-white shadow-sm space-y-4">
                    <h2 class="text-lg font-semibold">Reallocations</h2>
                    <ul class="space-y-2">
                        @foreach ($reallocations as $r)
                            <li class="flex justify-between items-center gap-2 p-3 border rounded-lg">
                                <div>
                                    <p class="font-medium">{{ $r->source->name }} → {{ $r->recipient->name }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ $r->month->format('F Y') }} ·
                                        {{ $r->recipient_budget_id === $budget->id ? 'In' : 'Out' }}
                                        ${{ number_format($r->amount, 2) }}
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('reallocations.update', [$budget->id, $r->id]) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', (float) $r->amount) }}"
                                           class="rounded-md border-gray-300 shadow-sm text-sm w-24" />
                                    <button type="submit" class="text-sm text-blue-600 hover:underline">Save</button>
                                </form>
                                <form method="POST" action="{{ route('reallocations.destroy', [$budget->id, $r->id]) }}" onsubmit="return confirm('Delete this reallocation?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Delete budget button with confirmation -->
            <form method="POST" action="{{ route('budgets.destroy', $budget) }}" onsubmit="return confirm('Are you sure you want to delete this budget and all its data?');" class="mt-4">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    Delete Budget
                </button>
            </form>
        </div>
    </body>
</html>
