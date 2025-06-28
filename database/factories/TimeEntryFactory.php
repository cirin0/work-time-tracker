<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'start_time' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'stop_time' => null,
            'duration' => 0,
            'comment' => $this->faker->sentence,
        ];
    }
}
