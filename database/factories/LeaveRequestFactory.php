<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['sick', 'vacation', 'personal']),
            'start_date' => $this->faker->dateTimeBetween('+1 week', '+2 weeks')->format('Y-m-d'),
            'end_date' => $this->faker->dateTimeBetween('+2 weeks', '+3 weeks')->format('Y-m-d'),
            'reason' => $this->faker->sentence,
            'status' => 'pending',
        ];
    }
}
