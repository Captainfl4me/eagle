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
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="w-full lg:max-w-md max-w-[335px]">
            <h1 class="text-2xl font-semibold mb-6">Register to {{ config('app.name', 'Laravel') }}</h1>

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
                        class="w-full px-3 py-2 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm bg-transparent focus:outline-none focus:border-[#4a4a45] dark:focus:border-[#62605b]"
                    >
                    @error('username')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Password</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        class="w-full px-3 py-2 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm bg-transparent focus:outline-none focus:border-[#4a4a45] dark:focus:border-[#62605b]"
                    >
                    @error('password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirm Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        required
                        class="w-full px-3 py-2 border border-[#19140035] dark:border-[#3E3E3A] rounded-sm bg-transparent focus:outline-none focus:border-[#4a4a45] dark:focus:border-[#62605b]"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full px-4 py-2 bg-[#1b1b18] dark:bg-[#EDEDEC] text-[#FDFDFC] dark:text-[#0a0a0a] rounded-sm font-medium hover:opacity-90"
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