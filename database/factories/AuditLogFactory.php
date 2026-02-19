<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['created', 'updated', 'deleted', 'login', 'logout'];
        $modelTypes = [TimeEntry::class, User::class, null];

        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement($actions),
            'model_type' => $this->faker->randomElement($modelTypes),
            'model_id' => $this->faker->optional()->numberBetween(1, 100),
            'old_values' => $this->faker->optional()->passthrough(['status' => 'pending']),
            'new_values' => $this->faker->optional()->passthrough(['status' => 'approved']),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}
