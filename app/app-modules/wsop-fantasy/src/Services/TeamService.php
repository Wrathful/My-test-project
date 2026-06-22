<?php

namespace Modules\WsopFantasy\Services;

use Modules\WsopFantasy\Models\Group;
use Modules\WsopFantasy\Models\Player;
use Modules\WsopFantasy\Models\Team;
use Modules\WsopFantasy\Models\TeamPlayer;
use Illuminate\Validation\ValidationException;

/**
 * Сервис валидации команды WSOP Fantasy.
 */
class TeamService
{
    /**
     * Максимальная стоимость команды.
     */
    public const MAX_TEAM_COST = 180;

    /**
     * Коэффициент капитана.
     */
    public const CAPTAIN_MULTIPLIER = 1.5;

    /**
     * Валидирует данные команды.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function validateTeamData(array $data): array
    {
        $groups = Group::with('players')->get();
        $groupIds = $groups->pluck('id')->toArray();
        $groupNames = $groups->pluck('name', 'id')->toArray();

        // Проверяем, что выбрано игрок из каждой группы
        $selectedPlayers = $data['players'] ?? [];
        $selectedGroupIds = array_keys($selectedPlayers);
        $expectedGroupCount = count($groupIds);

        if (count($selectedGroupIds) !== $expectedGroupCount) {
            throw ValidationException::withMessages([
                'players' => ['Должно быть выбрано ровно ' . $expectedGroupCount . ' игроков, по одному из каждой группы.'],
            ]);
        }

        // Проверяем, что все группы присутствуют
        $missingGroups = array_diff($groupIds, $selectedGroupIds);
        if (!empty($missingGroups)) {
            $missingGroupNames = array_map(fn($id) => $groupNames[$id] ?? "ID: $id", $missingGroups);
            throw ValidationException::withMessages([
                'players' => ['Отсутствуют игроки из групп: ' . implode(', ', $missingGroupNames)],
            ]);
        }

        // Проверяем, что игроки существуют и принадлежат своим группам
        $playerIds = array_values($selectedPlayers);
        $players = Player::whereIn('id', $playerIds)->get()->keyBy('id');

        foreach ($selectedPlayers as $groupId => $playerId) {
            if (!isset($players[$playerId])) {
                throw ValidationException::withMessages([
                    "players.{$groupId}" => ['Игрок не найден.'],
                ]);
            }

            if ($players[$playerId]->group_id !== (int) $groupId) {
                throw ValidationException::withMessages([
                    "players.{$groupId}" => ['Игрок не принадлежит выбранной группе.'],
                ]);
            }
        }

        // Проверяем общую стоимость
        $totalCost = $players->sum('cost');
        $maxTeamCost = self::MAX_TEAM_COST;
        if ($totalCost > $maxTeamCost) {
            throw ValidationException::withMessages([
                'players' => ["Общая стоимость команды ({$totalCost}) превышает максимальную ({$maxTeamCost})."],
            ]);
        }

        // Проверяем капитана
        $captainId = $data['captain_id'] ?? null;
        if ($captainId === null || !in_array($captainId, $playerIds, true)) {
            throw ValidationException::withMessages([
                'captain_id' => ['Выбранный капитан не входит в команду.'],
            ]);
        }

        return $data;
    }

    /**
     * Вычисляет общий POY балл команды.
     *
     * @param Team $team
     * @return int
     */
    public function calculateTeamScore(Team $team): int
    {
        $teamPlayers = $team->teamPlayers->loadMissing('player.latestPoyScore');

        $totalScore = 0;
        foreach ($teamPlayers as $teamPlayer) {
            $score = $teamPlayer->player->latestPoyScore?->score ?? 0;
            if ($teamPlayer->is_captain) {
                $score = (int) round($score * self::CAPTAIN_MULTIPLIER);
            }
            $totalScore += $score;
        }

        return $totalScore;
    }
}
