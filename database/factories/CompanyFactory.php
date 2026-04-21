<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company,
            'email' => $this->faker->unique()->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'logo' => null,
            'description' => $this->faker->paragraph,
            'address' => $this->faker->streetAddress,
            'manager_id' => null,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'radius_meters' => $this->faker->numberBetween(50, 500),
            'lateness_grace_minutes' => 5,
            'overtime_threshold_hours' => 0.5,
            'qr_secret' => Str::uuid(),
        ];
    }
}
