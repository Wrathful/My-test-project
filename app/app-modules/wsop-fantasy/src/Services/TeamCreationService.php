<?php

namespace Modules\WsopFantasy\Services;

use Modules\WsopFantasy\Models\GipsyteamUser;
use Modules\WsopFantasy\Models\Group;
use Modules\WsopFantasy\Models\Team;
use Modules\WsopFantasy\Models\TeamPlayer;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для создания и сохранения команды.
 */
class TeamCreationService
{
    /**
     * @param TeamService $validationService
     */
    public function __construct(
        private TeamService $validationService
    ) {}

    /**
     * Получает данные для формы создания команды.
     *
     * @param int $userId
     * @return array{groups: \Illuminate\Database\Eloquent\Collection, team: Team|null, selectedPlayers: array, captainId: int|null}
     */
    public function getFormData(int $userId): array
    {
        $groups = Group::with('players')->orderBy('name')->get();

        $team = Team::where('gipsyteam_user_id', $userId)
            ->with('teamPlayers.player')
            ->first();

        $selectedPlayers = [];
        $captainId = null;

        if ($team) {
            foreach ($team->teamPlayers as $teamPlayer) {
                $selectedPlayers[$teamPlayer->player->group_id] = $teamPlayer->player_id;
                if ($teamPlayer->is_captain) {
                    $captainId = $teamPlayer->player_id;
                }
            }
        }

        return compact('groups', 'team', 'selectedPlayers', 'captainId');
    }

    /**
       * Создаёт команду.
       *
       * @param int $userId
       * @param array<string, mixed> $data
       * @return Team
       * @throws \Exception Если команда уже существует
       */
    public function createTeam(int $userId, array $data): Team
    {
        $this->validationService->validateTeamData($data);

        return DB::transaction(function () use ($userId, $data) {
            $team = Team::create([
                'gipsyteam_user_id' => $userId,
                'name' => $data['name'] ?? "User {$userId}'s Team",
            ]);

            foreach ($data['players'] as $groupId => $playerId) {
                TeamPlayer::create([
                    'team_id' => $team->id,
                    'player_id' => $playerId,
                    'is_captain' => $playerId === $data['captain_id'],
                ]);
            }

            return $team;
        });
    }



    public function canParticipate(GipsyteamUser $user)
    {
        return $user->insert_datetime->lt(\config('wsop-fantasy.date_registration_user', '2026-06-03'));
    }
}
