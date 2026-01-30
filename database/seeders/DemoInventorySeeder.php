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
            'name' => 'Project Manager',
            'email' => 'manager@example.com',
            'role' => 'user',
        ]);

        $this->command->info('✅ Created 2 users');
        return $admin;
    }

    private function createResources()
    {
        $cement = Resource::create([
            'name' => 'Portland Cement',
            'sku' => 'CEM-001',
            'category' => 'Concrete & Cement',
            'base_unit' => 'kg',
            'description' => 'Type I Portland Cement for general construction',
        ]);

        $steel = Resource::create([
            'name' => 'Steel Reinforcement Bars',
            'sku' => 'STL-001',
            'category' => 'Steel & Metals',
            'base_unit' => 'piece',
            'description' => '12mm diameter steel rebar, 6m length',
        ]);

        $bricks = Resource::create([
            'name' => 'Red Clay Bricks',
            'sku' => 'BRK-001',
            'category' => 'Masonry',
            'base_unit' => 'piece',
            'description' => 'Standard red clay bricks 230x110x76mm',
        ]);

        $sand = Resource::create([
            'name' => 'Construction Sand',
            'sku' => 'SND-001',
            'category' => 'Raw Materials',
            'base_unit' => 'kg',
            'description' => 'Fine sand for concrete mixing',
        ]);

        $paint = Resource::create([
            'name' => 'Exterior Wall Paint',
            'sku' => 'PNT-001',
            'category' => 'Finishing Materials',
            'base_unit' => 'liter',
            'description' => 'Weather-resistant acrylic paint',
        ]);

        $this->command->info('✅ Created 5 resources');
        return [$cement, $steel, $bricks, $sand, $paint];
    }

    private function createProjects()
    {
        $factoryA = Project::create([
            'name' => 'Factory Building A',
            'code' => 'FAC-A-2026',
            'description' => 'Main production facility construction',
            'status' => 'active',
            'start_date' => '2026-01-15',
            'end_date' => '2026-06-30',
        ]);

        $factoryB = Project::create([
            'name' => 'Factory Building B',
            'code' => 'FAC-B-2026',
            'description' => 'Secondary warehouse construction',
            'status' => 'active',
            'start_date' => '2026-01-20',
            'end_date' => '2026-05-31',
        ]);

        $warehouse = Project::create([
            'name' => 'Storage Warehouse',
            'code' => 'WH-2026',
            'description' => 'Material storage facility',
            'status' => 'active',
            'start_date' => '2026-02-01',
            'end_date' => '2026-04-30',
        ]);

        $this->command->info('✅ Created 3 projects');
        return [$factoryA, $factoryB, $warehouse];
    }

    private function day1_InitialPurchases($cement, $steel, $bricks, $sand, $paint, $user)
    {
        $this->command->info("\n📦 DAY 1 (Jan 28, 2026): Initial Purchases");
        
        $this->service->recordPurchase(
            resource: $cement,
            quantity: 5000,
            unitPrice: 0.20,
            transactionDate: '2026-01-28',
            supplier: 'ABC Cement Co.',
            invoiceNumber: 'INV-2026-001',
            notes: 'Initial stock purchase - 5 tons',
            user: $user
        );
        $this->command->info('  • Purchased 5000kg Cement @ $0.20/kg = $1,000');

        $this->service->recordPurchase(
            resource: $steel,
            quantity: 100,
            unitPrice: 15.00,
            transactionDate: '2026-01-28',
            supplier: 'Steel Works Ltd.',
            invoiceNumber: 'INV-2026-002',
            notes: '12mm rebar, 6m length',
            user: $user
        );
        $this->command->info('  • Purchased 100 pieces Steel @ $15.00/piece = $1,500');

        $this->service->recordPurchase(
            resource: $bricks,
            quantity: 5000,
            unitPrice: 0.50,
            transactionDate: '2026-01-28',
            supplier: 'City Brick Factory',
            invoiceNumber: 'INV-2026-003',
            user: $user
        );
        $this->command->info('  • Purchased 5000 pieces Bricks @ $0.50/piece = $2,500');

        $this->service->recordPurchase(
            resource: $sand,
            quantity: 10000,
            unitPrice: 0.05,
            transactionDate: '2026-01-28',
            supplier: 'Desert Sand Inc.',
            invoiceNumber: 'INV-2026-004',
            notes: 'Fine sand - 10 tons',
            user: $user
        );
        $this->command->info('  • Purchased 10000kg Sand @ $0.05/kg = $500');
    }

    private function day2_AllocationsAndConsumption($cement, $steel, $bricks, $factoryA, $user)
    {
        $this->command->info("\n🚚 DAY 2 (Jan 29, 2026): Allocations to Factory A");
        
        $this->service->recordAllocation(
            resource: $cement,
            project: $factoryA,
            quantity: 2000,
            transactionDate: '2026-01-29',
            notes: 'Foundation work allocation',
            user: $user
        );
        $this->command->info('  • Allocated 2000kg Cement to Factory A');

        $this->service->recordAllocation(
            resource: $steel,
            project: $factoryA,
            quantity: 50,
            transactionDate: '2026-01-29',
            notes: 'Foundation reinforcement',
            user: $user
        );
        $this->command->info('  • Allocated 50 pieces Steel to Factory A');

        $this->service->recordAllocation(
            resource: $bricks,
            project: $factoryA,
            quantity: 2000,
            transactionDate: '2026-01-29',
            notes: 'Wall construction materials',
            user: $user
        );
        $this->command->info('  • Allocated 2000 pieces Bricks to Factory A');

        $this->command->info("\n⚙️ DAY 2 (Jan 29, 2026): First Consumption at Factory A");
        
        $this->service->recordConsumption(
            resource: $cement,
            project: $factoryA,
            quantity: 500,
            transactionDate: '2026-01-29',
            notes: 'Foundation concrete pour - Section A',
            user: $user
        );
        $this->command->info('  • Consumed 500kg Cement at Factory A');

        $this->service->recordConsumption(
            resource: $steel,
            project: $factoryA,
            quantity: 10,
            transactionDate: '2026-01-29',
            notes: 'Foundation reinforcement - Section A',
            user: $user
        );
        $this->command->info('  • Consumed 10 pieces Steel at Factory A');
    }

    private function day3_MorePurchasesAllocationsAndTransfers($cement, $sand, $paint, $factoryA, $factoryB, $warehouse, $user)
    {
        $this->command->info("\n📦 DAY 3 (Jan 30, 2026): Additional Purchases (Price Change!)");
        
        $this->service->recordPurchase(
            resource: $cement,
            quantity: 3000,
            unitPrice: 0.22,
            transactionDate: '2026-01-30',
            supplier: 'ABC Cement Co.',
            invoiceNumber: 'INV-2026-005',
            notes: 'Second batch - price increased 10%',
            user: $user
        );
        $this->command->info('  • Purchased 3000kg Cement @ $0.22/kg = $660 (price increased!)');

        $this->service->recordPurchase(
            resource: $paint,
            quantity: 200,
            unitPrice: 8.50,
            transactionDate: '2026-01-30',
            supplier: 'Paint World Inc.',
            invoiceNumber: 'INV-2026-006',
            notes: 'White exterior paint',
            user: $user
        );
        $this->command->info('  • Purchased 200 liters Paint @ $8.50/liter = $1,700');

        $this->command->info("\n🚚 DAY 3 (Jan 30, 2026): Allocations to Multiple Projects");
        
        $this->service->recordAllocation(
            resource: $cement,
            project: $factoryB,
            quantity: 1500,
            transactionDate: '2026-01-30',
            notes: 'Warehouse foundation work',
            user: $user
        );
        $this->command->info('  • Allocated 1500kg Cement to Factory B');

        $this->service->recordAllocation(
            resource: $sand,
            project: $factoryB,
            quantity: 3000,
            transactionDate: '2026-01-30',
            notes: 'Sand for concrete mixing',
            user: $user
        );
        $this->command->info('  • Allocated 3000kg Sand to Factory B');

        $this->service->recordAllocation(
            resource: $paint,
            project: $warehouse,
            quantity: 50,
            transactionDate: '2026-01-30',
            notes: 'Initial painting materials',
            user: $user
        );
        $this->command->info('  • Allocated 50 liters Paint to Warehouse');

        $this->command->info("\n🔄 DAY 3 (Jan 30, 2026): Transfers Between Projects");
        
        $this->service->recordTransfer(
            resource: $cement,
            fromProject: $factoryA,
            toProject: $factoryB,
            quantity: 300,
            transactionDate: '2026-01-30',
            notes: 'Factory A has excess, Factory B needs more',
            user: $user
        );
        $this->command->info('  • Transferred 300kg Cement from Factory A to Factory B');

        $this->command->info("\n⚙️ DAY 3 (Jan 30, 2026): Consumption at Multiple Projects");
        
        $this->service->recordConsumption(
            resource: $cement,
            project: $factoryA,
            quantity: 600,
            transactionDate: '2026-01-30',
            notes: 'Foundation concrete pour - Section B',
            user: $user
        );
        $this->command->info('  • Consumed 600kg Cement at Factory A');

        $this->service->recordConsumption(
            resource: $cement,
            project: $factoryB,
            quantity: 400,
            transactionDate: '2026-01-30',
            notes: 'Initial foundation work',
            user: $user
        );
        $this->command->info('  • Consumed 400kg Cement at Factory B');
    }

    private function displaySummary($cement, $steel, $bricks, $sand, $paint, $factoryA, $factoryB, $warehouse)
    {
        $this->command->info("\n" . str_repeat('=', 70));
        $this->command->info('📊 INVENTORY SUMMARY (End of Day 3 - Jan 30, 2026)');
        $this->command->info(str_repeat('=', 70));
        
        $this->command->info("\n🏢 CENTRAL HUB:");
        $this->command->info("  Cement: {$this->service->getCurrentStock($cement, null)} kg");
        $this->command->info("  Steel: {$this->service->getCurrentStock($steel, null)} pieces");
        $this->command->info("  Bricks: {$this->service->getCurrentStock($bricks, null)} pieces");
        $this->command->info("  Sand: {$this->service->getCurrentStock($sand, null)} kg");
        $this->command->info("  Paint: {$this->service->getCurrentStock($paint, null)} liters");
        
        $this->command->info("\n🏭 FACTORY A:");
        $this->command->info("  Cement: {$this->service->getCurrentStock($cement, $factoryA->id)} kg");
        $this->command->info("  Steel: {$this->service->getCurrentStock($steel, $factoryA->id)} pieces");
        $this->command->info("  Bricks: {$this->service->getCurrentStock($bricks, $factoryA->id)} pieces");
        
        $this->command->info("\n🏭 FACTORY B:");
        $this->command->info("  Cement: {$this->service->getCurrentStock($cement, $factoryB->id)} kg");
        $this->command->info("  Sand: {$this->service->getCurrentStock($sand, $factoryB->id)} kg");
        
        $this->command->info("\n🏪 WAREHOUSE:");
        $this->command->info("  Paint: {$this->service->getCurrentStock($paint, $warehouse->id)} liters");
        
        $txnCount = \App\Models\InventoryTransaction::count();
        $this->command->info("\n📝 Total Transactions: {$txnCount}");
        
        $this->command->info("\n" . str_repeat('=', 70));
        $this->command->info('✅ SEEDING COMPLETED SUCCESSFULLY!');
        $this->command->info(str_repeat('=', 70));
        $this->command->info("\n🔐 Login Credentials:");
        $this->command->info("   Email: admin@example.com");
        $this->command->info("   Password: password");
        $this->command->info("\n");
    }
}
