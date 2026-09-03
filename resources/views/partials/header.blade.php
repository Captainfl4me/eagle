<header class="flex items-center justify-between bg-gray-800 p-4 text-white w-full self-stretch">
    <!-- Logo and App Name -->
    <div class="flex items-center space-x-2">
        <!-- Logo -->
        <div class="bg-background rounded-lg p-0">
            <img src="{{ asset('img/logo.svg') }}" alt="Eagle Budget" class="h-10 w-10 fill-current" />
        </div>
        <span class="text-xl font-semibold">Eagle Budget</span>
    </div>
    <!-- Navigation (placeholder links) -->
    <nav class="space-x-4">
        @auth
            <a href="{{ url('/') }}" class="hover:underline">Home</a>
            <a href="{{ route('budgets.index') }}" class="hover:underline">Budgets</a>
            <a href="{{ route('profile') }}" class="hover:underline">Profile</a>
        @else
            <a href="{{ route('login') }}" class="hover:underline">Login</a>
            <a href="{{ route('register') }}" class="hover:underline">Register</a>
        @endauth
    </nav>
</header>
