<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }} - Profile</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="w-full lg:max-w-md max-w-[335px] space-y-8">
            <div class="flex justify-between items-start">
                <a href="/" class="text-sm text-gray-500 hover:text-gray-700">← Back to Home</a>
                <h1 class="text-2xl font-semibold text-center flex-1">Profile</h1>
            </div>
            
            <!-- Status message -->
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 border-l-4 border-green-500 dark:border-green-400 text-green-700 dark:text-green-300" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="space-y-6">
                    <!-- User Info -->
                    <div class="text-center">
                        <div class="flex items-center justify-center h-16 w-16 mb-4 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 text-lg font-bold">
                            {{ strtoupper(substr(Auth::user()->username, 0, 1)) }}
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ Auth::user()->username }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Username</p>
                    </div>

                    <!-- Change Password Form -->
                    <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="current_password" class="block text-sm font-medium mb-2">Current Password</label>
                            <input 
                                type="password" 
                                name="current_password" 
                                id="current_password" 
                                required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium mb-2">New Password</label>
                            <input 
                                type="password" 
                                name="new_password" 
                                id="new_password" 
                                required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <div>
                            <label for="new_password_confirmation" class="block text-sm font-medium mb-2">Confirm New Password</label>
                            <input 
                                type="password" 
                                name="new_password_confirmation" 
                                id="new_password_confirmation" 
                                required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <div class="flex items-center justify-between">
                            <button 
                                type="submit" 
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors"
                            >
                                Update Password
                            </button>
                        </div>
                    </form>

                    <!-- Logout Button -->
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button 
                            type="submit" 
                            class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors"
                        >
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>