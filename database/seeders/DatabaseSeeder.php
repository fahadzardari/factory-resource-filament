<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * 
     * For Module 1 Testing: Central Inventory Control
     * - Users: Admin and regular users
     * - Resources: Core resource catalog with proper categorization
     * - ResourceBatches: Purchase batches with multiple units per resource
     * 
     * Projects are excluded for now - focusing on inventory management first.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ResourceSeeder::class,
            ResourceBatchSeeder::class,
            // ProjectSeeder::class, // Disabled for Module 1 testing
        ]);
    }
}
