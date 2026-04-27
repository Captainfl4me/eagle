<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }} - Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="w-full max-w-md space-y-8">
            <div class="flex justify-between items-start">
                <a href="/" class="text-sm text-gray-500 hover:text-gray-700">← Back to Home</a>
                <h1 class="text-2xl font-bold text-center flex-1 text-center">Login to {{ config('app.name', 'Laravel') }}</h1>
            </div>
            
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium mb-2">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        value="{{ old('username') }}"
                        required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('username')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium mb-2">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit -->
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Sign In
                </button>
            </form>

            <p class="mt-6 text-center text-sm">
                Don't have an account? 
                <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Register here</a>
            </p>
        </div>
    </body>
</html>