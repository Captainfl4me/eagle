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
        <div class="w-full max-w-md space-y-8">
            <div class="flex justify-between items-start">
                <a href="{{ route('budgets.show', $budget->id) }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to {{ $budget->name }}</a>
                <h1 class="text-2xl font-bold text-center flex-1 text-center">Reallocations</h1>
            </div>

            @if (session('status'))
                <div class="p-3 text-sm bg-gray-100 text-gray-700 rounded">{{ session('status') }}</div>
            @endif

            @if ($reallocations->isEmpty())
                <p class="text-center">No reallocations yet.</p>
            @else
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
            @endif
        </div>
    </body>
</html>
