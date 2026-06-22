<?php

namespace Modules\WsopFantasy\Services;

use Modules\WsopFantasy\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Сервис для формирования leaderboard данных.
 */
class LeaderboardService
{
    /**
     * Получает данные лидерборда.
     *
     * @return array{leaderboard: array, currentPage: int, perPage: int, total: int}
     */
    public function getLeaderboardData(): array
    {
        $teams = Team::with(['user', 'teamPlayers.player.group', 'teamPlayers.player.latestPoyScore'])
            ->orderByDesc('total_score')
            ->paginate(50);

        $leaderboard = $teams->getCollection()->map(function ($team) {
            $totalScore = $team->total_score;

            $players = $team->teamPlayers->map(function ($teamPlayer) {
                $score = $teamPlayer->player->latestPoyScore?->score ?? 0;
                if ($teamPlayer->is_captain) {
                    $score = (int) round($score * TeamService::CAPTAIN_MULTIPLIER);
                }

                return [
                    'name' => $teamPlayer->player->name,
                    'group' => $teamPlayer->player->group->name,
                    'cost' => $teamPlayer->player->cost,
                    'score' => $score,
                    'is_captain' => $teamPlayer->is_captain,
                ];
            });

            return [
                'id' => $team->id,
                'user_login' => $team->user->login,
                'total_score' => $totalScore,
                'players' => $players->sortByDesc('score')->values(),
            ];
        })->sortByDesc('total_score')->values();

        return [
            'leaderboard' => $leaderboard,
            'currentPage' => $teams->currentPage(),
            'perPage' => $teams->perPage(),
            'total' => $teams->total(),
        ];
    }
}
