<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }} - Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] min-h-screen flex flex-col">
    @include('partials.header')
    <div class="container mx-auto p-8">
        <h1 class="text-3xl font-bold mb-4">Dashboard</h1>
        <p class="text-gray-700">This is a placeholder dashboard page. Content will be added soon.</p>
    </div>
</body>
</html>
