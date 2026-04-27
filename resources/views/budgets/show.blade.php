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
                <h1 class="text-2xl font-bold text-center flex-1 text-center">Budget Details</h1>
            </div>

            <div class="p-4 border rounded-lg">
                <p><strong>Name:</strong> {{ $budget->name }}</p>
                <p><strong>Start month:</strong> {{ $budget->start_month->format('Y‑m') }}</p>
                <p><strong>Start amount:</strong> ${{ number_format($budget->start_amount, 2) }}</p>
                <p><strong>Total amount:</strong> <!-- TODO: replace with real calculation --> ${{ number_format(rand(100, 5000) + rand(0,99)/100, 2) }}</p>
            </div>
        </div>
    </body>
</html>
