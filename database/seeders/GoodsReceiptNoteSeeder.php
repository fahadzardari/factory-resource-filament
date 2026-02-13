<?php

namespace Database\Seeders;

use App\Models\GoodsReceiptNote;
use App\Models\GoodsReceiptNoteLineItem;
use App\Models\Supplier;
use App\Models\Resource;
use App\Services\InventoryTransactionService;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class GoodsReceiptNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get service to record transactions
        $service = app(InventoryTransactionService::class);

        // Get user for created_by
        $user = \App\Models\User::first();

        if (!$user) {
            $this->command->warn('⚠️ No user found! Please run UserSeeder first. Skipping GRN seeding.');
            return;
        }

        // Get available resources for testing
        // If specific SKUs don't exist, we'll use available resources
        $availableResources = Resource::limit(20)->get();

        if ($availableResources->isEmpty()) {
            $this->command->warn('⚠️ No resources found in database. Please run ResourceSeeder first.');
            return;
        }

        // Sample GRN shipments data - structured for multi-item receipts
        $grnShipments = [
            [
                'supplier_name' => 'AL AJWA BLDG MAT TR. LLC',
                'receipt_date' => now()->subDays(10),
                'delivery_reference' => 'SHIP-2026-0001',
                'notes' => 'Grade A cement, properly bagged',
                'item_count' => 1,
            ],
            [
                'supplier_name' => 'ALZAN BUILDING MATERIALS TRADING LLC',
                'receipt_date' => now()->subDays(8),
                'delivery_reference' => 'SHIP-2026-0002',
                'notes' => 'Bulk cement order',
                'item_count' => 1,
            ],
            
            // Multi-item deliveries
            [
                'supplier_name' => 'TAREEQ AL MUSTAQBAL STEEL WORKSHOP L.L.C',
                'receipt_date' => now()->subDays(7),
                'delivery_reference' => 'SHIP-2026-0003',
                'notes' => 'Steel reinforcement bars, quality inspected',
                'item_count' => 2,
            ],
            [
                'supplier_name' => 'HALA BUILDING MATERIAL TRADING',
                'receipt_date' => now()->subDays(5),
                'delivery_reference' => 'SHIP-2026-0005',
                'notes' => 'Building materials',
                'item_count' => 1,
            ],
            [
                'supplier_name' => 'REALITY BUILDING MATERIALS TRADING LLC',
                'receipt_date' => now()->subDays(4),
                'delivery_reference' => 'SHIP-2026-0006',
                'notes' => 'Standard quality materials',
                'item_count' => 1,
            ],
            [
                'supplier_name' => 'CERAMICS & TILE CENTER',
                'receipt_date' => now()->subDays(3),
                'delivery_reference' => 'SHIP-2026-0007',
                'notes' => 'Ceramic tiles',
                'item_count' => 1,
            ],
            [
                'supplier_name' => 'EMILAM INDUSTRIES L.L.C',
                'receipt_date' => now()->subDays(2),
                'delivery_reference' => 'SHIP-2026-0008',
                'notes' => 'Gypsum and materials',
                'item_count' => 1,
            ],
            [
                'supplier_name' => 'LUMBER WORLD BUILDING MATERIAL TRADING LLC',
                'receipt_date' => now()->subDays(1),
                'delivery_reference' => 'SHIP-2026-0009',
                'notes' => 'Timber and plywood',
                'item_count' => 1,
            ],
            
            // Paint multi-item delivery
            [
                'supplier_name' => 'EVI PAINTS & COATINGS',
                'receipt_date' => now()->subDays(10),
                'delivery_reference' => 'SHIP-2026-0010',
                'notes' => 'Paint order - mixed colors',
                'item_count' => 2,
            ],
            [
                'supplier_name' => 'AL RAMADI PAINTS TRADING L L C',
                'receipt_date' => now()->subDays(8),
                'delivery_reference' => 'SHIP-2026-0011',
                'notes' => 'Oil paint delivery',
                'item_count' => 1,
            ],
            [
                'supplier_name' => 'MAS PAINTS & CHEMICALS INDUSRTY',
                'receipt_date' => now()->subDays(5),
                'delivery_reference' => 'SHIP-2026-0012',
                'notes' => 'Primer coats',
                'item_count' => 1,
            ],
            
            // Equipment multi-item delivery
            [
                'supplier_name' => 'FUTURE CHOICE MACHINERY TRADING LLC SP',
                'receipt_date' => now()->subDays(12),
                'delivery_reference' => 'SHIP-2026-0013',
                'notes' => 'Cement mixing equipment',
                'item_count' => 1,
            ],
            [
                'supplier_name' => 'AL WISAM AL FADHI BLDG. EQUIP. RENT LLC',
                'receipt_date' => now()->subDays(9),
                'delivery_reference' => 'SHIP-2026-0014',
                'notes' => 'Scaffolding and equipment',
                'item_count' => 1,
            ],
            
            // Safety equipment multi-item delivery
            [
                'supplier_name' => 'HANDS MIDDLE EAST LLC',
                'receipt_date' => now()->subDays(7),
                'delivery_reference' => 'SHIP-2026-0015-0016',
                'notes' => 'Safety equipment - helmets and gloves',
                'item_count' => 2,
            ],
            
            // Electrical equipment multi-item delivery
            [
                'supplier_name' => 'ELECTRICAL LIGHTING COMPANY L.L.C',
                'receipt_date' => now()->subDays(4),
                'delivery_reference' => 'SHIP-2026-0017-0019',
                'notes' => 'Electrical supplies - wire, breakers, LED lights',
                'item_count' => 3,
            ],
            [
                'supplier_name' => 'SPARK WORLD LIGHTING ELECTRICAL FITTINGS TRADING LLC',
                'receipt_date' => now()->subDays(2),
                'delivery_reference' => 'SHIP-2026-0019',
                'notes' => 'LED bulbs cool white',
                'item_count' => 1,
            ],
        ];

        $successCount = 0;
        $failureCount = 0;
        $resourceIndex = 0;

        foreach ($grnShipments as $shipmentData) {
            // Get or create supplier
            $supplier = Supplier::where('name', $shipmentData['supplier_name'])->first();
            if (!$supplier) {
                $supplier = Supplier::create([
                    'name' => $shipmentData['supplier_name'],
                    'is_active' => true,
                ]);
            }

            try {
                // Create GRN header
                $grn = GoodsReceiptNote::create([
                    'supplier_id' => $supplier->id,
                    'receipt_date' => $shipmentData['receipt_date'],
                    'delivery_reference' => $shipmentData['delivery_reference'],
                    'notes' => $shipmentData['notes'],
                    'created_by' => $user->id,
                ]);

                // Create line items for this GRN
                $itemCount = min($shipmentData['item_count'], $availableResources->count());
                
                for ($i = 0; $i < $itemCount; $i++) {
                    // Cycle through available resources
                    $resource = $availableResources[($resourceIndex + $i) % $availableResources->count()];
                    
                    // Random quantity between 100 and 5000
                    $quantity = rand(100, 5000);
                    $unitPrice = rand(10, 500) + (rand(0, 99) / 100);

                    \App\Models\GoodsReceiptNoteLineItem::create([
                        'grn_id' => $grn->id,
                        'resource_id' => $resource->id,
                        'quantity_received' => $quantity,
                        'receipt_unit' => $resource->base_unit,
                        'unit_price' => $unitPrice,
                        'total_value' => round($quantity * $unitPrice, 2),
                    ]);
                }

                // Advance resource index for next GRN
                $resourceIndex += $itemCount;

                // Refresh the GRN to load line items
                $grn->refresh();

                // Create inventory transactions automatically
                $service->recordGoodsReceipt($grn, $user);
                
                $successCount++;

            } catch (\Exception $e) {
                $this->command->warn("⚠️ Failed to create GRN for {$shipmentData['supplier_name']}: " . $e->getMessage());
                $failureCount++;
            }
        }

        $this->command->info('✅ Successfully seeded ' . $successCount . ' GRNs' . ($failureCount > 0 ? " ({$failureCount} failed)" : ''));
    }
}

