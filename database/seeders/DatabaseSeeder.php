<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Run demo data seeder
        $this->call(DemoDataSeeder::class);
    }
}
