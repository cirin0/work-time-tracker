<?php

namespace Database\Seeders;

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
     * Creates one user for each role (Employee, Manager, Admin) with associated entities
     * in various states (pending, approved, rejected, completed, active, etc.)
     */
    public function run(): void
    {
        // Create a company
        $company = Company::create([
            'name' => 'Demo Tech Corp',
            'email' => 'contact@demotech.com',
            'phone' => '+380501234567',
            'logo' => null,
            'description' => 'A demo technology company for testing purposes',
            'address' => '123 Demo Street, Kyiv, Ukraine',
            'manager_id' => null,
        ]);

        // Create a work schedule for the company
        $workSchedule = WorkSchedule::create([
            'name' => 'Standard 9-5 Schedule',
            'company_id' => $company->id,
            'is_default' => true,
        ]);

        // Create daily schedules for working days (Monday-Friday)
        $workingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        foreach ($workingDays as $day) {
            DailySchedule::create([
                'work_schedule_id' => $workSchedule->id,
                'day_of_week' => $day,
                'is_working_day' => true,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'break_duration' => 60,
            ]);
        }

        // Create weekend daily schedules (non-working)
        $weekendDays = ['saturday', 'sunday'];
        foreach ($weekendDays as $day) {
            DailySchedule::create([
                'work_schedule_id' => $workSchedule->id,
                'day_of_week' => $day,
                'is_working_day' => false,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'break_duration' => 0,
            ]);
        }

        // Create Admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@demotech.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'company_id' => $company->id,
            'manager_id' => null,
            'avatar' => null,
            'work_schedule_id' => $workSchedule->id,
            'email_verified_at' => now(),
        ]);

        // Create Manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@demotech.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
            'company_id' => $company->id,
            'manager_id' => $admin->id,
            'avatar' => null,
            'work_schedule_id' => $workSchedule->id,
            'email_verified_at' => now(),
        ]);

        // Update company manager
        $company->update(['manager_id' => $manager->id]);

        // Create Employee user
        $employee = User::create([
            'name' => 'Employee User',
            'email' => 'employee@demotech.com',
            'password' => Hash::make('password'),
            'role' => UserRole::EMPLOYEE,
            'company_id' => $company->id,
            'manager_id' => $manager->id,
            'avatar' => null,
            'work_schedule_id' => $workSchedule->id,
            'email_verified_at' => now(),
        ]);

        // Create Time Entries for each user
        // Admin - completed time entry
        TimeEntry::create([
            'user_id' => $admin->id,
            'start_time' => Carbon::now()->subDays(1)->setTime(9, 0),
            'stop_time' => Carbon::now()->subDays(1)->setTime(17, 30),
            'duration' => 510, // 8.5 hours in minutes
            'start_comment' => 'Admin completed full day work',
            'stop_comment' => 'Admin stopped working for the day',
        ]);

        // Manager - completed and active time entry
        TimeEntry::create([
            'user_id' => $manager->id,
            'start_time' => Carbon::now()->subDays(2)->setTime(8, 30),
            'stop_time' => Carbon::now()->subDays(2)->setTime(16, 45),
            'duration' => 495, // ~8.25 hours in minutes
            'start_comment' => 'Manager completed work with meetings',
            'stop_comment' => 'Manager stopped working for the day',
        ]);

        TimeEntry::create([
            'user_id' => $manager->id,
            'start_time' => Carbon::now()->setTime(9, 15),
            'stop_time' => null,
            'duration' => 0,
            'start_comment' => 'Manager currently working',
            'stop_comment' => null,
        ]);

        // Employee - completed and active time entries
        TimeEntry::create([
            'user_id' => $employee->id,
            'start_time' => Carbon::now()->subDays(3)->setTime(9, 0),
            'stop_time' => Carbon::now()->subDays(3)->setTime(18, 0),
            'duration' => 540, // 9 hours in minutes
            'start_comment' => 'Employee worked on project tasks',
            'stop_comment' => 'Employee stopped working for the day',
        ]);

        TimeEntry::create([
            'user_id' => $employee->id,
            'start_time' => Carbon::now()->setTime(8, 45),
            'stop_time' => null,
            'duration' => 0,
            'start_comment' => 'Employee currently clocked in',
            'stop_comment' => null,
        ]);

        // Create Leave Requests with different statuses
        // Admin - approved leave request
        LeaveRequest::create([
            'user_id' => $admin->id,
            'type' => 'vacation',
            'start_date' => Carbon::now()->addWeeks(2)->format('Y-m-d'),
            'end_date' => Carbon::now()->addWeeks(2)->addDays(5)->format('Y-m-d'),
            'reason' => 'Planned vacation',
            'status' => 'approved',
            'processed_by' => null,
            'manager_comment' => 'Admin vacation auto-approved',
        ]);

        // Manager - pending leave request
        LeaveRequest::create([
            'user_id' => $manager->id,
            'type' => 'personal',
            'start_date' => Carbon::now()->addWeeks(3)->format('Y-m-d'),
            'end_date' => Carbon::now()->addWeeks(3)->addDays(2)->format('Y-m-d'),
            'reason' => 'Personal matters to attend',
            'status' => 'pending',
            'processed_by' => null,
            'manager_comment' => null,
        ]);

        // Employee - multiple leave requests with different statuses
        LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => 'sick',
            'start_date' => Carbon::now()->addWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->addWeek()->addDays(2)->format('Y-m-d'),
            'reason' => 'Medical appointment',
            'status' => 'pending',
            'processed_by' => null,
            'manager_comment' => null,
        ]);

        LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => 'vacation',
            'start_date' => Carbon::now()->addMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->addMonth()->addDays(7)->format('Y-m-d'),
            'reason' => 'Summer vacation',
            'status' => 'approved',
            'processed_by' => $manager->id,
            'manager_comment' => 'Approved. Enjoy your vacation!',
        ]);

        LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => 'personal',
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->subWeek()->addDays(1)->format('Y-m-d'),
            'reason' => 'Family event',
            'status' => 'rejected',
            'processed_by' => $manager->id,
            'manager_comment' => 'Already too many people on leave during this period. Please reschedule.',
        ]);

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Users created:');
        $this->command->info('- Admin: admin@demotech.com (password: password)');
        $this->command->info('- Manager: manager@demotech.com (password: password)');
        $this->command->info('- Employee: employee@demotech.com (password: password)');
        $this->command->info('');
        $this->command->info('Entities created:');
        $this->command->info('- 1 Company');
        $this->command->info('- 1 Work Schedule with 7 Daily Schedules');
        $this->command->info('- 3 Users (Admin, Manager, Employee)');
        $this->command->info('- 5 Time Entries (2 active, 3 completed)');
        $this->command->info('- 5 Leave Requests (2 pending, 2 approved, 1 rejected)');
    }
}
