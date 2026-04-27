<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }} - Create Budget</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="w-full max-w-md space-y-8">
            <div class="flex justify-between items-start">
                <a href="{{ url('/') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Home</a>
                <h1 class="text-2xl font-bold text-center flex-1 text-center">Create New Budget</h1>
            </div>
            
            @if (session('status'))
                <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('budgets.store') }}" class="space-y-6">
                @csrf

                <!-- Budget Name -->
                <div>
                    <label for="name" class="block text-sm font-medium mb-2">Budget name</label>
                    <input 
                        type="text" 
                        name="name" 
                        id="name" 
                        value="{{ old('name') }}"
                        placeholder="e.g., Monthly Groceries"
                        required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-5 0 focus:border-transparent"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Starting month -->
                <div>
                    <label for="start_month" class="block text-sm font-medium mb-2">Starting month</label>
                    <input 
                        type="month" 
                        name="start_month" 
                        id="start_month" 
                        value="{{ old('start_month') }}"
                        placeholder="YYYY-MM"
                        required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('start_month')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Starting amount -->
                <div>
                    <label for="start_amount" class="block text-sm font-medium mb-2">Starting amount</label>
                    <input 
                        type="number" 
                        name="start_amount" 
                        id="start_amount" 
                        step="0.01"
                        min="0"
                        placeholder="e.g., 500.00"
                        value="{{ old('start_amount') }}"
                        required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('start_amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Create Budget
                </button>
            </form>
        </div>
    </body>
</html>
