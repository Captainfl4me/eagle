<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }} - Register</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background text-text flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="w-full lg:max-w-md max-w-[335px] space-y-8">
            <div class="flex justify-between items-start">
                <a href="/" class="text-sm text-muted hover:text-text">← Back to Home</a>
                <h1 class="text-2xl font-semibold text-center flex-1">Register to {{ config('app.name', 'Laravel') }}</h1>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="username" class="block text-sm font-medium mb-1">Username</label>
                    <input
                        type="text"
                        name="username"
                        id="username"
                        value="{{ old('username') }}"
                        required
                        class="w-full px-3 py-2 rounded-md border border-line bg-surface text-text focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                    >
                    @error('username')
                        <p class="text-alert text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Password</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        class="w-full px-3 py-2 rounded-md border border-line bg-surface text-text focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                    >
                    @error('password')
                        <p class="text-alert text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirm Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        required
                        class="w-full px-3 py-2 rounded-md border border-line bg-surface text-text focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full px-4 py-2 bg-primary text-white rounded-md font-medium hover:bg-secondary transition-colors"
                >
                    Register
                </button>
            </form>

            <p class="mt-4 text-sm text-center">
                Already have an account? <a href="{{ route('login') }}" class="underline">Log in</a>
            </p>
        </div>
    </body>
</html>
