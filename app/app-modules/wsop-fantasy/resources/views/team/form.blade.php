@extends('wsop-fantasy::layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">WSOP Fantasy 2026 - Моя команда</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('wsop-fantasy.team.submit') }}" id="team-form">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                @foreach($groups as $group)
                    <div class="border rounded-lg p-4">
                        <h3 class="font-bold text-lg mb-2">{{ $group->name }}</h3>
                        <select name="players[{{ $group->id }}]" class="w-full border rounded px-2 py-1 player-select"
                                data-group="{{ $group->id }}" data-cost="0">
                            <option value="">Выберите игрока</option>
                            @foreach($group->players as $player)
                                <option value="{{ $player->id }}"
                                        data-cost="{{ $player->cost }}"
                                        @if(isset($selectedPlayers[$group->id]) && $selectedPlayers[$group->id] == $player->id) selected @endif>
                                    {{ $player->name }} ({{ $player->cost }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            <div class="mb-4">
                <label class="block font-bold mb-2">Капитан:</label>
                <select name="captain_id" id="captain-select" class="w-full md:w-1/2 border rounded px-2 py-1" required>
                    <option value="">Выберите капитана</option>
                    @foreach($groups as $group)
                        @foreach($group->players as $player)
                            <option value="{{ $player->id }}"
                                    @if(isset($captainId) && $captainId == $player->id) selected @endif>
                                {{ $player->name }} ({{ $group->name }})
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <p class="text-lg">Общая стоимость: <span id="total-cost">0</span>
                    / {{ \Modules\WsopFantasy\Services\TeamService::MAX_TEAM_COST }}</p>
            </div>

            <div class="flex gap-4">
                <button type="submit" formaction="{{ route('wsop-fantasy.team.submit') }}"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                        onclick="return confirm('Вы уверены, что хотите подать команду? После подачи её нельзя будет изменить.');">
                    Подать команду
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selects = document.querySelectorAll('.player-select');
            const totalCostEl = document.getElementById('total-cost');
            const maxCost = {{ \Modules\WsopFantasy\Services\TeamService::MAX_TEAM_COST }};

            function updateTotalCost() {
                let total = 0;
                selects.forEach(select => {
                    const selectedOption = select.options[select.selectedIndex];
                    if (selectedOption && selectedOption.value) {
                        total += parseInt(selectedOption.dataset.cost || 0);
                    }
                });
                totalCostEl.textContent = total;
                totalCostEl.className = total > maxCost ? 'text-red-500 font-bold' : 'text-green-500 font-bold';
            }

            selects.forEach(select => {
                select.addEventListener('change', updateTotalCost);
            });

            // Initial calculation
            updateTotalCost();
        });
    </script>
@endsection
