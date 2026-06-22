<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }} - WSOP Fantasy</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-gray-100 min-h-screen">
        <nav class="bg-white shadow mb-6">
            <div class="container mx-auto px-4 py-4">
                <div class="flex justify-between items-center">
                    <a href="{{ route('wsop-fantasy.leaderboard') }}" class="font-bold text-lg">WSOP Fantasy 2026</a>
                    <div>
                        <a href="{{ route('wsop-fantasy.team.create') }}" class="mr-4">Моя команда</a>
                        <a href="{{ route('wsop-fantasy.leaderboard') }}">Лидерборд</a>
                    </div>
                </div>
            </div>
        </nav>

        @yield('content')
    </body>
</html>
