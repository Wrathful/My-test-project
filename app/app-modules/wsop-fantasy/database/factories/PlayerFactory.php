<?php

namespace Modules\WsopFantasy\Database\Factories;

use Modules\WsopFantasy\Models\Player;
use Modules\WsopFantasy\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\WsopFantasy\Models\Player>
 */
class PlayerFactory extends Factory
{
    /**
     * The current factory state name.
     *
     * @var string
     */
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'group_id' => Group::factory(),
            'cost' => $this->faker->numberBetween(1, 100),
        ];
    }
}
