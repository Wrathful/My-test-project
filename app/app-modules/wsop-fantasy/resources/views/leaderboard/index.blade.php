@extends('wsop-fantasy::layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">WSOP Fantasy 2026 - Лидерборд</h1>

    <table class="min-w-full bg-white border border-gray-200">
        <thead>
            <tr class="bg-gray-100">
                <th class="px-4 py-2 border">#</th>
                <th class="px-4 py-2 border">Пользователь</th>
                <th class="px-4 py-2 border">Общий POY</th>
                <th class="px-4 py-2 border">Игроки</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leaderboard as $index => $team)
                <tr class="border-t">
                    <td class="px-4 py-2 border text-center">{{ $index + 1 }}</td>
                    <td class="px-4 py-2 border">{{ $team['user_login'] }}</td>
                    <td class="px-4 py-2 border text-center font-bold">{{ $team['total_score'] }}</td>
                    <td class="px-4 py-2 border">
                        <table class="w-full">
                            <thead>
                                <tr class="text-xs bg-gray-50">
                                    <th class="px-2 py-1">Игрок</th>
                                    <th class="px-2 py-1">Группа</th>
                                    <th class="px-2 py-1">Стоимость</th>
                                    <th class="px-2 py-1">POY</th>
                                    <th class="px-2 py-1">Капитан</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($team['players'] as $player)
                                    <tr class="text-sm {{ $player['is_captain'] ? 'bg-yellow-50 font-bold' : '' }}">
                                        <td class="px-2 py-1">{{ $player['name'] }}</td>
                                        <td class="px-2 py-1">{{ $player['group'] }}</td>
                                        <td class="px-2 py-1">{{ $player['cost'] }}</td>
                                        <td class="px-2 py-1">{{ $player['score'] }}</td>
                                        <td class="px-2 py-1">
                                            @if($player['is_captain'])
                                                <span class="text-yellow-600">★</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($total > $perPage)
        <div class="mt-4">
            Страница {{ $currentPage }} из {{ ceil($total / $perPage) }}
        </div>
    @endif
</div>
@endsection
