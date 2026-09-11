<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }} - Reallocations</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background text-text flex min-h-screen flex-col">
    @include('partials.header')
        <div class="w-full max-w-lg mx-auto px-4 py-8 space-y-6">
            <div class="flex justify-between items-start gap-4">
                <a href="{{ route('budgets.show', $budget->id) }}" class="text-sm text-muted hover:text-text">← Back to {{ $budget->name }}</a>
                <h1 class="text-2xl font-bold text-center flex-1">Reallocations</h1>
            </div>

            @if (session('status'))
                <div class="p-3 text-sm bg-surface-alt text-text rounded">{{ session('status') }}</div>
            @endif

            @if ($reallocations->isEmpty())
                <p class="text-center text-muted">No reallocations yet.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($reallocations as $r)
                        <li class="flex flex-wrap justify-between items-center gap-2 p-3 border border-line rounded-lg bg-surface">
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
            @endif
        </div>
    </body>
</html>
