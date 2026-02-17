<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Resource;
use App\Models\Project;
use App\Services\InventoryTransactionService;

class DemoInventorySeeder extends Seeder
{
    private $service;

    public function run(): void
    {
        $this->service = app(InventoryTransactionService::class);

        // Create users
        $admin = $this->createUsers();

        // // Create resources
        // [$cement, $steel, $bricks, $sand, $paint] = $this->createResources();

        // // Create projects
        // [$factoryA, $factoryB, $warehouse] = $this->createProjects();

        // // Run complete workflow over 3 days
        // $this->day1_InitialPurchases($cement, $steel, $bricks, $sand, $paint, $admin);
        // $this->day2_AllocationsAndConsumption($cement, $steel, $bricks, $factoryA, $admin);
        // $this->day3_MorePurchasesAllocationsAndTransfers($cement, $sand, $paint, $factoryA, $factoryB, $warehouse, $admin);

        // // Display summary
        // $this->displaySummary($cement, $steel, $bricks, $sand, $paint, $factoryA, $factoryB, $warehouse);

    }

    private function createUsers()
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@spacebuilderinv.com',
            'role' => 'admin',
            'password' => bcrypt('!kjdfiReowR21re'),
        ]);

        User::factory()->create([
            'name' => 'Finance Controller',
            'email' => 'finance.controller@spacebuilderinv.com',
            'password' => bcrypt('gu3igyaklwrh'),
            'role' => 'user',
        ]);

        User::factory()->create([
            'name' => 'Accountant',
            'email' => 'accountant@spacebuilderinv.com',
            'password' => bcrypt('gwk73883brb'),
            'role' => 'user',
        ]);

        $this->command->info('✅ Created 3 users');
        return $admin;
    }

}
