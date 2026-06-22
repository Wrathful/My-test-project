<?php

namespace Modules\WsopFantasy\Database\Factories;

use Modules\WsopFantasy\Models\Team;
use Modules\WsopFantasy\Models\GipsyteamUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\WsopFantasy\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * The current factory state name.
     *
     * @var string
     */
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gipsyteam_user_id' => GipsyteamUser::factory(),
            'name' => $this->faker->word(),
        ];
    }
}
