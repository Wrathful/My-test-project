<?php

namespace Modules\WsopFantasy\Tests\Unit;

use Tests\TestCase;
use Modules\WsopFantasy\Models\GipsyteamUser;
use Modules\WsopFantasy\Models\Group;
use Modules\WsopFantasy\Models\Player;
use Modules\WsopFantasy\Models\Team;
use Modules\WsopFantasy\Models\TeamPlayer;
use Modules\WsopFantasy\Models\PoyScore;
use Modules\WsopFantasy\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class TeamValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TeamService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = \app(TeamService::class);
    }

    /**
     * Тест валидации корректных данных команды.
     */
    public function test_validates_correct_team_data(): void
    {
        $groups = Group::factory()->count(7)->create();
        $players = collect();

        foreach ($groups as $group) {
            $players->push(
                Player::factory()->create([
                    'group_id' => $group->id,
                    'cost' => 20,
                ])
            );
        }

        $data = [
            'players' => $groups->mapWithKeys(fn($group, $i) => [$group->id => $players[$i]->id])->toArray(),
            'captain_id' => $players[0]->id,
        ];

        $result = $this->service->validateTeamData($data);

        $this->assertEquals($data, $result);
    }

    /**
     * Тест ошибки при неверном количестве игроков.
     */
    public function test_throws_exception_for_wrong_player_count(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'players' => [1 => 1, 2 => 2],
            'captain_id' => 1,
        ];

        $this->service->validateTeamData($data);
    }

    /**
     * Тест ошибки при превышении стоимости.
     */
    public function test_throws_exception_for_exceeded_cost(): void
    {
        $this->expectException(ValidationException::class);

        $groups = Group::factory()->count(7)->create();
        $players = collect();

        foreach ($groups as $group) {
            $players->push(
                Player::factory()->create([
                    'group_id' => $group->id,
                    'cost' => 100, // Высокая стоимость
                ])
            );
        }

        $data = [
            'players' => $groups->mapWithKeys(fn($group, $i) => [$group->id => $players[$i]->id])->toArray(),
            'captain_id' => $players[0]->id,
        ];

        $this->service->validateTeamData($data);
    }

    /**
     * Тест ошибки при выборе игрока из неправильной группы.
     */
    public function test_throws_exception_for_player_from_wrong_group(): void
    {
        $this->expectException(ValidationException::class);

        $groups = Group::factory()->count(7)->create();
        $players = collect();

        // Создаем игроков для всех групп
        foreach ($groups as $group) {
            $players->push(
                Player::factory()->create([
                    'group_id' => $group->id,
                    'cost' => 20,
                ])
            );
        }

        // Но выбираем игрока из первой группы для второй группы
        $data = [
            'players' => [
                $groups[0]->id => $players[0]->id,
                $groups[1]->id => $players[0]->id, // ОШИБКА: игрок из первой группы
            ],
            'captain_id' => $players[0]->id,
        ];

        $this->service->validateTeamData($data);
    }

    /**
     * Тест ошибки при отсутствии игрока в команде-капитане.
     */
    public function test_throws_exception_for_captain_not_in_team(): void
    {
        $this->expectException(ValidationException::class);

        $groups = Group::factory()->count(7)->create();
        $players = collect();

        foreach ($groups as $group) {
            $players->push(
                Player::factory()->create([
                    'group_id' => $group->id,
                    'cost' => 20,
                ])
            );
        }

        $data = [
            'players' => $groups->mapWithKeys(fn($group, $i) => [$group->id => $players[$i]->id])->toArray(),
            'captain_id' => 99999, // Не существующий ID
        ];

        $this->service->validateTeamData($data);
    }

    /**
     * Тест вычисления суммарного балла команды.
     */
    public function test_calculates_team_score(): void
    {
        $user = GipsyteamUser::factory()->create();
        $group = Group::factory()->create();
        $player = Player::factory()->create([
            'group_id' => $group->id,
            'cost' => 20,
        ]);

        $team = Team::factory()->create([
            'gipsyteam_user_id' => $user->id,
        ]);
        $teamPlayer = TeamPlayer::factory()->create([
            'team_id' => $team->id,
            'player_id' => $player->id,
            'is_captain' => true,
        ]);

        PoyScore::factory()->create([
            'player_id' => $player->id,
            'score' => 100,
            'scored_at' => now(),
        ]);

        $score = $this->service->calculateTeamScore($team);

        $this->assertEquals(150, $score); // 100 * 1.5 (капитан)
    }

    /**
     * Тест ошибки при отсутствии игроков из некоторых групп.
     */
    public function test_throws_exception_for_missing_groups(): void
    {
        $this->expectException(ValidationException::class);

        $groups = Group::factory()->count(7)->create();
        $players = collect();

        // Создаем игроков для всех 7 групп
        foreach ($groups as $group) {
            $players->push(
                Player::factory()->create([
                    'group_id' => $group->id,
                    'cost' => 20,
                ])
            );
        }

        // Выбираем игроков только из 6 групп (не хватает одной)
        $data = [
            'players' => $groups->take(6)->mapWithKeys(fn($group, $i) => [$group->id => $players[$i]->id])->toArray(),
            'captain_id' => $players[0]->id,
        ];

        $this->service->validateTeamData($data);
    }

    /**
     * Тест вычисления балла без капитана.
     */
    public function test_calculates_team_score_without_captain_multiplier(): void
    {
        $user = GipsyteamUser::factory()->create();
        $group = Group::factory()->create();
        $player = Player::factory()->create([
            'group_id' => $group->id,
            'cost' => 20,
        ]);

        $team = Team::factory()->create([
            'gipsyteam_user_id' => $user->id,
        ]);
        $teamPlayer = TeamPlayer::factory()->create([
            'team_id' => $team->id,
            'player_id' => $player->id,
            'is_captain' => false,
        ]);

        PoyScore::factory()->create([
            'player_id' => $player->id,
            'score' => 100,
            'scored_at' => now(),
        ]);

        $score = $this->service->calculateTeamScore($team);

        $this->assertEquals(100, $score); // Без множителя
    }

    /**
      * Тест вычисления балла для команды с несколькими игроками.
      */
    public function test_calculates_team_score_with_multiple_players(): void
    {
        $user = GipsyteamUser::factory()->create();
        $groups = Group::factory()->count(7)->create();
        $players = collect();

        foreach ($groups as $i => $group) {
            $players->push(
                Player::factory()->create([
                    'group_id' => $group->id,
                    'cost' => 20,
                ])
            );

            PoyScore::factory()->create([
                'player_id' => $players[$i]->id,
                'score' => 100,
                'scored_at' => now(),
            ]);
        }

        $team = Team::factory()->create([
            'gipsyteam_user_id' => $user->id,
        ]);

        // Создаем игроков явно, чтобы избежать конфликтов с unique index
        TeamPlayer::create([
            'team_id' => $team->id,
            'player_id' => $players[0]->id,
            'is_captain' => true,
        ]);
        TeamPlayer::create([
            'team_id' => $team->id,
            'player_id' => $players[1]->id,
            'is_captain' => false,
        ]);
        TeamPlayer::create([
            'team_id' => $team->id,
            'player_id' => $players[2]->id,
            'is_captain' => false,
        ]);
        TeamPlayer::create([
            'team_id' => $team->id,
            'player_id' => $players[3]->id,
            'is_captain' => false,
        ]);
        TeamPlayer::create([
            'team_id' => $team->id,
            'player_id' => $players[4]->id,
            'is_captain' => false,
        ]);
        TeamPlayer::create([
            'team_id' => $team->id,
            'player_id' => $players[5]->id,
            'is_captain' => false,
        ]);
        TeamPlayer::create([
            'team_id' => $team->id,
            'player_id' => $players[6]->id,
            'is_captain' => false,
        ]);

        $score = $this->service->calculateTeamScore($team);

        // 100 * 1.5 (капитан) + 100 * 6 = 750
        $this->assertEquals(750, $score);
    }
}
