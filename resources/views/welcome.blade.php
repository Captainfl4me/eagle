<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background text-text min-h-screen flex flex-col">
        @include('partials.header')

        <!-- Hero -->
        <section class="w-full max-w-4xl mx-auto px-4 pt-16 pb-12 text-center space-y-6">
            <h1 class="text-4xl sm:text-5xl font-bold">Take control of your money with {{ config('app.name', 'Laravel') }}</h1>
            <p class="text-lg text-muted max-w-2xl mx-auto">
                {{ config('app.name', 'Laravel') }} is a personal budget manager based on the envelope method:
                create budgets, track budgeted vs realized amounts month by month, and move money
                between budgets when you need to.
            </p>
            <div class="flex items-center justify-center space-x-4">
                <a href="{{ route('register') }}" class="bg-primary hover:bg-secondary text-white font-bold py-2 px-6 rounded transition-colors">Get started</a>
                <a href="{{ route('login') }}" class="border border-line bg-surface hover:bg-surface-alt text-text py-2 px-6 rounded transition-colors">Log in</a>
            </div>
        </section>

        <!-- Dashboard preview (placeholder card with fake charts) -->
        <section class="w-full max-w-4xl mx-auto px-4 pb-12">
            <div class="p-4 border border-line rounded-lg bg-surface shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Dashboard preview</h2>
                    <span class="text-sm text-muted">Envelope &amp; cash flow</span>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Placeholder pie chart -->
                    <div class="p-3 border border-line rounded-lg bg-surface-alt">
                        <p class="text-sm text-muted mb-2">Envelope allocation</p>
                        <div class="flex items-center space-x-4">
                            <div class="h-24 w-24 rounded-full shrink-0" style="background: conic-gradient(var(--color-primary) 0 55%, var(--color-secondary) 55% 80%, var(--color-muted) 80% 100%);"></div>
                            <ul class="text-sm space-y-1">
                                <li class="flex items-center space-x-2"><span class="h-3 w-3 rounded-sm bg-primary inline-block"></span><span>Groceries</span></li>
                                <li class="flex items-center space-x-2"><span class="h-3 w-3 rounded-sm bg-secondary inline-block"></span><span>Rent</span></li>
                                <li class="flex items-center space-x-2"><span class="h-3 w-3 rounded-sm bg-muted inline-block"></span><span>Savings</span></li>
                            </ul>
                        </div>
                    </div>
                    <!-- Placeholder bar chart -->
                    <div class="p-3 border border-line rounded-lg bg-surface-alt">
                        <p class="text-sm text-muted mb-2">Budgeted vs realized</p>
                        <div class="flex items-end space-x-2 h-24">
                            <div class="w-8 bg-primary rounded-t" style="height: 80%"></div>
                            <div class="w-8 bg-secondary rounded-t" style="height: 60%"></div>
                            <div class="w-8 bg-primary rounded-t" style="height: 90%"></div>
                            <div class="w-8 bg-secondary rounded-t" style="height: 45%"></div>
                            <div class="w-8 bg-primary rounded-t" style="height: 70%"></div>
                            <div class="w-8 bg-secondary rounded-t" style="height: 65%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="w-full max-w-4xl mx-auto px-4 pb-16 grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div class="p-4 border border-line rounded-lg bg-surface shadow-sm space-y-2">
                <h2 class="text-lg font-semibold">Budgets</h2>
                <p class="text-sm text-muted">Create as many budgets as you need, each with its own starting month and envelope balance.</p>
            </div>
            <div class="p-4 border border-line rounded-lg bg-surface shadow-sm space-y-2">
                <h2 class="text-lg font-semibold">Monthly tracking</h2>
                <p class="text-sm text-muted">Record budgeted and realized amounts every month; the running envelope is computed for you and alerts you when it dips below zero.</p>
            </div>
            <div class="p-4 border border-line rounded-lg bg-surface shadow-sm space-y-2">
                <h2 class="text-lg font-semibold">Reallocations</h2>
                <p class="text-sm text-muted">Move money between your budgets month by month to keep every envelope positive.</p>
            </div>
        </section>

        <div class="h-14.5 hidden lg:block"></div>
    </body>
</html>
