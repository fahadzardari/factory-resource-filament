<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoInventorySeeder::class,      // Create users first
            SupplierSeeder::class,
            ResourceSeeder::class,
            ProjectSeeder::class,
            GoodsReceiptNoteSeeder::class,   // Now GoodsReceiptNoteSeeder has users available
        ]);
    }
}
