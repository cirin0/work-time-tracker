<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\DailySchedule;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkScheduleFactory extends Factory
{
    protected $model = WorkSchedule::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'company_id' => Company::factory(),
            'is_default' => false,
        ];
    }

    public function configure(): WorkScheduleFactory
    {
        return $this->afterCreating(function (WorkSchedule $workSchedule) {
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            foreach ($days as $day) {
                DailySchedule::factory()->create([
                    'work_schedule_id' => $workSchedule->id,
                    'day_of_week' => $day,
                    'is_working_day' => true,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'break_duration' => 60,
                ]);
            }
        });
    }
}
