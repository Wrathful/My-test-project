<?php

namespace Modules\WsopFantasy\Database\Factories;

use Modules\WsopFantasy\Models\PoyScore;
use Modules\WsopFantasy\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\WsopFantasy\Models\PoyScore>
 */
class PoyScoreFactory extends Factory
{
    /**
     * The current factory state name.
     *
     * @var string
     */
    protected $model = PoyScore::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'score' => $this->faker->numberBetween(0, 1000),
            'scored_at' => now(),
        ];
    }
}
