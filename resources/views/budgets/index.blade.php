<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }} - My Budgets</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="w-full max-w-2xl space-y-8">
            <div class="flex justify-between items-start">
                <a href="{{ url('/') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Home</a>
                <h1 class="text-2xl font-bold text-center flex-1 text-center">My Budgets</h1>
                <a href="{{ route('budgets.create') }}" class="mt-4 inline-block px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">Create Budget</a>
            </div>

            @if ($budgets->isEmpty())
                <p class="text-center">You have no budgets yet. <a href="{{ route('budgets.create') }}" class="text-blue-600 hover:underline">Create one</a>.</p>
            @else
                <ul class="space-y-4">
                    @foreach ($budgets as $budget)
                        <li class="flex justify-between items-center p-4 border rounded-lg hover:bg-gray-100">
                            <a href="{{ route('budgets.show', $budget->id) }}" class="font-medium text-blue-600 hover:underline">
                                {{ $budget->name }}
                            </a>
@php
                                 $total = $budget->start_amount;
                                 foreach ($budget->months as $m) {
                                     $total += $m->budgeted_amount - $m->realized_amount;
                                 }
                             @endphp
                             <span class="{{ $total < 0 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($total, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </body>
</html>
