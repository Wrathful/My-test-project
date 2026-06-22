<?php

namespace Modules\WsopFantasy\Services;

use Modules\WsopFantasy\Models\Player;
use Modules\WsopFantasy\Models\PoyScore;
use Modules\WsopFantasy\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис импорта POY очков.
 */
class PoyImportService
{
    /**
     * URL для импорта POY очков.
     */
    private const POY_SOURCE_URL = 'https://www.25kfantasy.com/players/';

    /**
     * @param TeamService $validationService
     */
    public function __construct(
        private TeamService $validationService
    ) {}

    /**
     * Импортирует POY очки для всех игроков.
     *
     * @return int Количество обновленных записей
     */
    public function importScores(): int
    {
        $players = Player::all();
        $importedCount = 0;

        //Если API будет давать данные сразу по всем игрокам, то можно будет переделать это.
        foreach ($players as $player) {
            $score = $this->fetchPlayerScore($player->name);
            if ($score !== null) {
                // Обновляем/создаем очки - каждый импорт создает новую запись
                // (история изменения очков сохраняется)
                // четкого описания в задаче нет, поэтому без уточнения сделаем так.
                PoyScore::create([
                    'player_id' => $player->id,
                    'score' => $score,
                    'scored_at' => now(),
                ]);
                $importedCount++;
            }
        }

        // Обновляем суммарные очки для всех команд
        $this->updateTeamScores();

        return $importedCount;
    }

    /**
      * Обновляет суммарные очки для всех команд.
      */
    private function updateTeamScores(): void
    {
        Team::with('teamPlayers.player.latestPoyScore')
            ->chunkById(100, function ($teams) {
                foreach ($teams as $team) {
                    $totalScore = $this->validationService->calculateTeamScore($team);
                    $team->update(['total_score' => $totalScore]);
                }
            });
    }

    /**
     * Получает POY очки для конкретного игрока.
     * В реальном приложении здесь будет парсинг HTML или API вызов.
     * Для демонстрации генерируем случайные очки.
     *
     * @param string $playerName
     * @return int
     */
    protected function fetchPlayerScore(string $playerName): ?int
    {
        if (!\config('wsop-fantasy.enable_import_score_from_api', false)) {
            // Для демонстрации генерируем случайные очки от 0 до 1000
            return random_int(0, 1000);
        }

        // В реальном приложении здесь будет:
        // 1. HTTP запрос к внешнему ресурсу
        // 2. Парсинг HTML/использование API
        // 3. Поиск игрока по имени и получение очков
        return null;
    }
}
