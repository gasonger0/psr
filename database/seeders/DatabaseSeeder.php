<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            LinesSeeder::class,
            ProductsCategoriesSeeder::class,
            // ResponsibleSeeder::class,
            // WorkerSeeder::class
        ]);
    }
}
