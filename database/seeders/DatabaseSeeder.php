<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Run initial setup seeder (creates first admin if no users exist)
        $this->call(InitialSetupSeeder::class);

        // Run demo data seeder (optional - comment out for production)
        // $this->call(DemoDataSeeder::class);
    }
}
