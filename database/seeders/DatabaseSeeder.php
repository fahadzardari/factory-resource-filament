<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoInventorySeeder::class,      // Create users first
            UnitSeeder::class,               // Create units before resources
            SupplierSeeder::class,
            ProjectSeeder::class,
            ResourceSeeder::class,           // Now resources can validate units
            // GoodsReceiptNoteSeeder::class,   // Now GoodsReceiptNoteSeeder has users available
        ]);
    }
}
