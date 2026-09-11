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
    <body class="bg-background text-text flex min-h-screen flex-col">
    @include('partials.header')
        <div class="w-full max-w-2xl mx-auto px-4 py-8 space-y-8">
            <div class="flex justify-between items-start">
                <a href="{{ url('/') }}" class="text-sm text-muted hover:text-text">← Back to Home</a>
                <h1 class="text-2xl font-bold text-center flex-1 text-center">My Budgets</h1>
                <a href="{{ route('budgets.create') }}" class="inline-block px-4 py-2 bg-primary hover:bg-secondary text-white rounded transition-colors">Create Budget</a>
            </div>

            @if ($budgets->isEmpty())
                <p class="text-center">You have no budgets yet. <a href="{{ route('budgets.create') }}" class="text-primary hover:underline">Create one</a>.</p>
            @else
                <ul class="space-y-4">
                    @foreach ($budgets as $budget)
                         <li class="flex justify-between items-center gap-4 p-4 border border-line rounded-lg bg-surface hover:bg-surface-alt transition-colors">
                             <a href="{{ route('budgets.show', $budget->id) }}" class="font-medium text-primary hover:underline">
                                 {{ $budget->name }}
                             </a>
                             <span class="{{ $budget->total < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">${{ number_format($budget->total, 2) }}</span>
                             <span class="text-sm text-muted">{{ $budget->net_borrowed >= 0 ? 'Lent $' : 'Borrowed $' }}{{ number_format(abs($budget->net_borrowed), 2) }}</span>
                         </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </body>
</html>
