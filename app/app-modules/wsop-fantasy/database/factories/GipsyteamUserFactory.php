<?php

namespace Modules\WsopFantasy\Database\Factories;

use Modules\WsopFantasy\Models\GipsyteamUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\WsopFantasy\Models\GipsyteamUser>
 */
class GipsyteamUserFactory extends Factory
{
    /**
     * The current factory state name.
     *
     * @var string
     */
    protected $model = GipsyteamUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'login' => $this->faker->unique()->userName(),
            'insert_datetime' => now(),
        ];
    }
}
