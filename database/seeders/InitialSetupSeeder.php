<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class InitialSetupSeeder extends Seeder
{
    /**
     * Seed the initial admin user if no users exist in the system.
     */
    public function run(): void
    {
        // Check if any users exist
        if (User::count() > 0) {
            $this->command->warn('Users already exist. Skipping initial setup.');

            return;
        }

        // Create the first admin user
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => UserRole::ADMIN,
            'company_id' => null,
            'manager_id' => null,
            'work_schedule_id' => null,
            'email_verified_at' => now(),
        ]);

        $this->command->info('-------------------------------------------------------');
        $this->command->info(' Initial Setup Complete!');
        $this->command->info('-------------------------------------------------------');
        $this->command->info(' First Admin User Created:');
        $this->command->info('   Email:    admin@example.com');
        $this->command->info('   Password: password');
        $this->command->info('   Role:     Admin');
        $this->command->info('-------------------------------------------------------');
        $this->command->info(' IMPORTANT: Change the password after first login!');
        $this->command->info('-------------------------------------------------------');
    }
}
