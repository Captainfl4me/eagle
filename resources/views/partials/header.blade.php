<header class="flex items-center justify-between bg-gray-800 p-4 text-white w-full self-stretch">
    <!-- Logo and App Name (clickable, routes to home/dashboard; not text-selectable) -->
    <a href="{{ url('/') }}" class="flex items-center space-x-2 select-none">
        <!-- Logo -->
        <div class="bg-background rounded-lg p-0">
            <img src="{{ asset('img/logo.svg') }}" alt="Eagle Budget" class="h-10 w-10 fill-current" />
        </div>
        <span class="text-xl font-semibold">Eagle Budget</span>
    </a>
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
