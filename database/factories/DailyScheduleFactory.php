<?php

namespace Database\Factories;

use App\Models\DailySchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailyScheduleFactory extends Factory
{
    protected $model = DailySchedule::class;

    public function definition(): array
    {
        return [
            'day_of_week' => $this->faker->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'break_duration' => $this->faker->numberBetween(0, 120), // Break duration in minutes
            'is_working_day' => $this->faker->boolean(80), // 80% chance of being a working day
        ];
    }
}
