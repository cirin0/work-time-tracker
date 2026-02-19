<?php

namespace Database\Seeders;

use App\Enums\EntryType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\DailySchedule;
use App\Models\LeaveRequest;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // 1. Create one company
        $company = Company::create([
            'name' => 'Demo Tech Corp',
            'email' => 'contact@demotech.com',
            'phone' => '+380501234567',
            'logo' => null,
            'description' => 'A demo technology company for testing purposes',
            'address' => '123 Demo Street, Kyiv, Ukraine',
            'manager_id' => null,
            'latitude' => 50.4501,
            'longitude' => 30.5234,
            'radius_meters' => 200,
            'qr_secret' => 'demo-qr-secret-123',
        ]);

        // 2. Create 5 different work schedules
        $workSchedules = [];
        $scheduleData = [
            ['name' => 'Standard 9-18', 'start' => '09:00', 'end' => '18:00'],
            ['name' => 'Early 8-17', 'start' => '08:00', 'end' => '17:00'],
            ['name' => 'Late 10-19', 'start' => '10:00', 'end' => '19:00'],
            ['name' => 'Morning Shift 7-16', 'start' => '07:00', 'end' => '16:00'],
            ['name' => 'Night Owl 12-21', 'start' => '12:00', 'end' => '21:00'],
        ];

        foreach ($scheduleData as $index => $data) {
            $schedule = WorkSchedule::create([
                'name' => $data['name'],
                'company_id' => $company->id,
                'is_default' => $index === 0,
            ]);

            $workingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            foreach ($workingDays as $day) {
                DailySchedule::create([
                    'work_schedule_id' => $schedule->id,
                    'day_of_week' => $day,
                    'is_working_day' => true,
                    'start_time' => $data['start'],
                    'end_time' => $data['end'],
                    'break_duration' => 60,
                ]);
            }

            $weekendDays = ['saturday', 'sunday'];
            foreach ($weekendDays as $day) {
                DailySchedule::create([
                    'work_schedule_id' => $schedule->id,
                    'day_of_week' => $day,
                    'is_working_day' => false,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'break_duration' => 0,
                ]);
            }

            $workSchedules[] = $schedule;
        }

        // 3. Create 1 Admin user (optional but good for management)
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@demotech.com',
            'password' => $password,
            'role' => UserRole::ADMIN,
            'company_id' => $company->id,
            'manager_id' => null,
            'work_schedule_id' => $workSchedules[0]->id,
            'email_verified_at' => now(),
            'pin_code' => '1111',
        ]);

        // 4. Create 1 Manager for all employees
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@demotech.com',
            'password' => $password,
            'role' => UserRole::MANAGER,
            'company_id' => $company->id,
            'manager_id' => $admin->id,
            'work_schedule_id' => $workSchedules[0]->id,
            'email_verified_at' => now(),
            'pin_code' => '2222',
        ]);

        // Update company manager
        $company->update(['manager_id' => $manager->id]);

        // 5. Create 10 employees
        for ($i = 1; $i <= 10; $i++) {
            $employee = User::create([
                'name' => "Employee $i",
                'email' => "employee$i@demotech.com",
                'password' => $password,
                'role' => UserRole::EMPLOYEE,
                'company_id' => $company->id,
                'manager_id' => $manager->id,
                'work_schedule_id' => $workSchedules[($i - 1) % 5]->id,
                'email_verified_at' => now(),
                'pin_code' => $i <= 5 ? str_repeat($i, 4) : null, // PIN for first 5 employees
            ]);

            // 6. Create 5 Time Entries for each employee
            for ($j = 1; $j <= 5; $j++) {
                $date = Carbon::now()->subDays($j + 1);
                $startTime = (clone $date)->setTime(9, 0)->addMinutes(rand(-15, 15));
                $stopTime = (clone $startTime)->addHours(8)->addMinutes(rand(0, 60));

                TimeEntry::create([
                    'user_id' => $employee->id,
                    'start_time' => $startTime,
                    'stop_time' => $stopTime,
                    'duration' => $startTime->diffInMinutes($stopTime),
                    'entry_type' => EntryType::MANUAL,
                    'location_data' => ['lat' => 50.4501, 'lng' => 30.5234],
                    'start_comment' => "Clocked in on day $j",
                    'stop_comment' => "Clocked out on day $j",
                ]);
            }

            // 7. Create 5 Leave Requests for each employee
            $leaveTypes = ['sick', 'vacation', 'personal'];
            $statuses = ['pending', 'approved', 'rejected'];

            for ($k = 1; $k <= 5; $k++) {
                $startDate = Carbon::now()->addWeeks($k + 1)->startOfWeek();
                $endDate = (clone $startDate)->addDays(rand(1, 4));

                LeaveRequest::create([
                    'user_id' => $employee->id,
                    'type' => $leaveTypes[array_rand($leaveTypes)],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'reason' => "Request $k for employee $i",
                    'status' => $statuses[array_rand($statuses)],
                    'processed_by' => $k % 2 === 0 ? $manager->id : null,
                    'manager_comment' => $k % 2 === 0 ? 'Processed by manager' : null,
                ]);
            }
        }

        $this->command->info('-------------------------------------------------------');
        $this->command->info(' Demo Data Seeded Successfully!');
        $this->command->info('-------------------------------------------------------');
        $this->command->info(' Company:    Demo Tech Corp');
        $this->command->info(' Schedules:  5 different work schedules');
        $this->command->info(' Users:      1 Admin, 1 Manager, 10 Employees');
        $this->command->info(' Password:   "password" (for all users)');
        $this->command->info(' PIN Codes:');
        $this->command->info('   - Admin:    1111');
        $this->command->info('   - Manager:  2222');
        $this->command->info('   - Employee 1-5: 1111, 2222, 3333, 4444, 5555');
        $this->command->info('   - Employee 6-10: (no PIN set)');
        $this->command->info(' Activity:   5 Time Entries & 5 Leave Requests per employee');
    }
}
