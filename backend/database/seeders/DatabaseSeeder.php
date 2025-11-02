<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);
        $this->call(ServiceSeeder::class);
        $this->call(OptionSeeder::class);
        // $this->call(CategorySeeder::class);
        // $this->call(ArticlesSeeder::class);
    }
}
