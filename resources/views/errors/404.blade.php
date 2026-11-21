<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>404 - {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background text-text min-h-screen flex flex-col">
        @include('partials.header')

        <div class="flex flex-col items-center justify-center w-full grow space-y-4">
            <h1 class="text-6xl font-bold">404</h1>
            <p class="text-muted">This page could not be found.</p>
            <a href="{{ url('/') }}" class="text-primary hover:underline">&larr; Back to Home</a>
        </div>

        <div class="h-14.5 hidden lg:block"></div>
    </body>
</html>
