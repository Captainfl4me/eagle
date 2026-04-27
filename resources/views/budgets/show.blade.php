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
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="w-full max-w-md space-y-8">
            <div class="flex justify-between items-start">
                <a href="{{ route('budgets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Budgets</a>
                <h1 class="text-2xl font-bold text-center flex-1 text-center">{{ $budget->name }}</h1>
            </div>

            <!-- Summary Card -->
            <div class="p-4 border rounded-lg bg-white shadow-sm">
                <p><strong>Start month:</strong> {{ $budget->start_month->format('Y‑m') }}</p>
                <p><strong>Start amount:</strong> ${{ number_format($budget->start_amount, 2) }}</p>
                <p><strong>Total amount:</strong> <span class="{{ $totalAmount < 0 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($totalAmount, 2) }}</span></p>
            </div>

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
                <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m-01') }}" />
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
