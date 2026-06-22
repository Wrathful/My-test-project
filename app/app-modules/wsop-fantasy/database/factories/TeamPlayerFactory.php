<?php

namespace Modules\WsopFantasy\Database\Factories;

use Modules\WsopFantasy\Models\TeamPlayer;
use Modules\WsopFantasy\Models\Team;
use Modules\WsopFantasy\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\WsopFantasy\Models\TeamPlayer>
 */
class TeamPlayerFactory extends Factory
{
    /**
     * The current factory state name.
     *
     * @var string
     */
    protected $model = TeamPlayer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'player_id' => Player::factory(),
            'is_captain' => false,
        ];
    }
}
