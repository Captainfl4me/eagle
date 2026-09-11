<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }} - Dashboard</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-text min-h-screen flex flex-col">
    @include('partials.header')
    <div class="container mx-auto p-8 space-y-6 max-w-2xl">
        <h1 class="text-3xl font-bold">Dashboard</h1>

        @if (session('status'))
            <div class="p-3 text-sm bg-surface-alt text-text rounded">{{ session('status') }}</div>
        @endif

        <!-- Envelope-positivity alert banner -->
        @if ($alerts->isNotEmpty())
            <div class="p-4 text-sm bg-red-50 text-red-700 border border-red-200 rounded-lg space-y-1 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800">
                <strong>Alert:</strong>
                <span>The envelope goes negative for the following budget(s). Resolve it by adding a reallocation.</span>
                <ul class="list-disc list-inside">
                    @foreach ($alerts as $budget)
                        <li>
                            <a href="{{ route('budgets.show', $budget->id) }}" class="underline hover:opacity-80">{{ $budget->name }}</a>
                            — {{ $budget->negative_months->map(fn($m) => $m->format('F Y'))->implode(', ') }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($budgets->isEmpty())
            <p class="text-center">You have no budgets yet. <a href="{{ route('budgets.create') }}" class="text-primary hover:underline">Create one</a>.</p>
        @else
            <ul class="space-y-4">
                @foreach ($budgets as $budget)
                    <li class="p-4 border border-line rounded-lg bg-surface shadow-sm">
                        <div class="flex justify-between items-center">
                            <a href="{{ route('budgets.show', $budget->id) }}" class="font-medium text-primary hover:underline">
                                {{ $budget->name }}
                            </a>
                            <span class="text-sm text-muted">{{ $budget->month->format('F Y') }}</span>
                        </div>
                        <div class="mt-2 flex justify-between items-center text-sm">
                            <span>Envelope:
                                <strong class="{{ $budget->total < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    ${{ number_format($budget->total, 2) }}
                                </strong>
                            </span>
                            <span class="text-muted">
                                {{ $budget->net_borrowed >= 0 ? 'Lent' : 'Borrowed' }}
                                ${{ number_format(abs($budget->net_borrowed), 2) }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</body>
</html>
