<?php

namespace Modules\WsopFantasy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WsopFantasy\Models\GipsyteamUser;
use Modules\WsopFantasy\Models\Group;
use Modules\WsopFantasy\Models\Player;
use Modules\WsopFantasy\Models\Team;
use Modules\WsopFantasy\Models\TeamPlayer;
use Modules\WsopFantasy\Models\PoyScore;
use Modules\WsopFantasy\Services\TeamService;

/**
 * Сиддер для заполнения тестовыми данными WSOP Fantasy.
 */
class WsopFantasySeeder extends Seeder
{
    /**
     * Группы игроков.
     */
    private const GROUPS = [
        'Волшебники',
        'Ковбои',
        'Мечтатели',
        'Новички',
        'Пиковая дама',
        'Старички',
        'Старый Свет',
    ];

    /**
     * Имена игроков (примерные, для демонстрации).
     */
    private const PLAYER_NAMES = [
        'Josh Reichard',
        'Josh Arieh',
        'Frank Brannan',
        'Matt Glantz',
        'Paul Volpe',
        'Jared Bleznick',
        'Chris Hunichen',
        'Daniel Negreanu',
        'Phil Hellmuth',
        'Doyle Brunson',
        'Phil Ivey',
        'Tom Dwan',
        'Fedor Holz',
        'Justin Bonomo',
        'Antonio Esfandiari',
        'Gus Hansen',
        'Scott Seiver',
        'Michael Mizrachi',
        'Erik Seidel',
        'John Juanda',
        'Stu Ungar',
        'Johnny Moss',
        'Amarillo Slim',
        'Jackie Robinson',
        'Williams Smith',
        'Johnson Brown',
        'Miller Davis',
        'Wilson Wilson',
        'Moore Taylor',
        'Taylor Anderson',
        'Anderson Thomas',
        'Thomas Jackson',
        'Jackson White',
        'Harris Martin',
        'Martin Thompson',
        'Thompson Garcia',
        'Garcia Martinez',
        'Robinson Lopez',
        'Clark Lee',
        'Rodriguez Walker',
        'Lewis Hall',
        'Lee Robinson',
        'Walker Lewis',
        'Hall Clark',
        'Allen Young',
        'King Perez',
        'Wright Carter',
        'Scott Perez',
        'Green Brooks',
        'Adams Price',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем группы
        $groups = collect(self::GROUPS)->map(function ($name) {
            return Group::create([
                'name' => $name,
                'max_players_per_team' => 1,
            ]);
        });

        // Создаем игроков (по 27 в каждой группе)
        $groups->each(function ($group, $index) {
            for ($i = 0; $i < 27; $i++) {
                $playerNameIndex = $index * 27 + $i;
                $playerName = self::PLAYER_NAMES[$playerNameIndex % count(self::PLAYER_NAMES)];

                // Добавляем номер к имени, если игроков много
                if ($i > 0) {
                    $playerName .= " ($i)";
                }

                Player::create([
                    'name' => $playerName,
                    'group_id' => $group->id,
                    'cost' => random_int(1, 100),
                ]);
            }
        });

        // Создаем 10 пользователей с командами
        $this->createUsersWithTeams($groups);
    }

    /**
      * Создает пользователей с командами.
      *
      * @param \Illuminate\Support\Collection $groups
      */
    private function createUsersWithTeams($groups): void
    {
        $validationService = new TeamService();

        for ($u = 1; $u <= 10; $u++) {
            $user = GipsyteamUser::create([
                'login' => "player{$u}",
                'insert_datetime' => now()->subDays(random_int(1, 365)),
            ]);

            // Создаем команду
            $team = Team::create([
                'gipsyteam_user_id' => $user->id,
                'name' => "{$user->login}'s Team",
            ]);

            // Выбираем по одному игроку из каждой группы
            $groups->each(function ($group, $index) use ($team) {
                $player = Player::where('group_id', $group->id)
                    ->inRandomOrder()
                    ->first();

                TeamPlayer::create([
                    'team_id' => $team->id,
                    'player_id' => $player->id,
                    'is_captain' => $index === 0, // Первый игрок - капитан
                ]);

                // Создаем POY очки для игрока
                PoyScore::create([
                    'player_id' => $player->id,
                    'score' => random_int(0, 1000),
                    'scored_at' => now(),
                ]);
            });

            // Обновляем суммарные очки команды
            $team->load('teamPlayers.player.latestPoyScore');
            $totalScore = $validationService->calculateTeamScore($team);
            $team->update(['total_score' => $totalScore]);
        }
    }
}
