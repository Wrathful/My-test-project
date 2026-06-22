<?php

namespace Modules\WsopFantasy\Database\Factories;

use Modules\WsopFantasy\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\WsopFantasy\Models\Group>
 */
class GroupFactory extends Factory
{
    /**
     * The current factory state name.
     *
     * @var string
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'max_players_per_team' => 1,
        ];
    }
}
