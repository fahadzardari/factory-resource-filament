<?php

namespace Database\Seeders;

use App\Models\GoodsReceiptNote;
use App\Models\Supplier;
use App\Models\Resource;
use App\Services\InventoryTransactionService;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get service to record transactions
        $service = app(InventoryTransactionService::class);

        // Sample GRN data with realistic information
        $grns = [
            // Cement deliveries
            [
                'supplier_name' => 'AL AJWA BLDG MAT TR. LLC',
                'resource_sku' => 'MAT-CEMENT-001',
                'quantity' => 5000,
                'unit_price' => 450,
                'receipt_date' => now()->subDays(10),
                'delivery_reference' => 'SHIP-2026-0001',
                'notes' => 'Grade A cement, properly bagged',
            ],
            [
                'supplier_name' => 'ALZAN BUILDING MATERIALS TRADING LLC',
                'resource_sku' => 'MAT-CEMENT-001',
                'quantity' => 3000,
                'unit_price' => 455,
                'receipt_date' => now()->subDays(8),
                'delivery_reference' => 'SHIP-2026-0002',
                'notes' => 'First delivery, 3000kg',
            ],
            
            // Steel deliveries
            [
                'supplier_name' => 'TAREEQ AL MUSTAQBAL STEEL WORKSHOP L.L.C',
                'resource_sku' => 'MAT-STEEL-016',
                'quantity' => 2500,
                'unit_price' => 2800,
                'receipt_date' => now()->subDays(7),
                'delivery_reference' => 'SHIP-2026-0003',
                'notes' => '16mm rebar, quality checked',
            ],
            [
                'supplier_name' => 'TAREEQ AL MUSTAQBAL STEEL WORKSHOP L.L.C',
                'resource_sku' => 'MAT-STEEL-012',
                'quantity' => 1800,
                'unit_price' => 2200,
                'receipt_date' => now()->subDays(6),
                'delivery_reference' => 'SHIP-2026-0004',
                'notes' => '12mm rebar delivery',
            ],
            
            // Sand deliveries
            [
                'supplier_name' => 'HALA BUILDING MATERIAL TRADING',
                'resource_sku' => 'MAT-SAND-WHITE',
                'quantity' => 50,
                'unit_price' => 85,
                'receipt_date' => now()->subDays(5),
                'delivery_reference' => 'SHIP-2026-0005',
                'notes' => '50 tons white sand, good quality',
            ],
            
            // Bricks deliveries
            [
                'supplier_name' => 'REALITY BUILDING MATERIALS TRADING LLC',
                'resource_sku' => 'MAT-BRICK-RED',
                'quantity' => 20000,
                'unit_price' => 1.25,
                'receipt_date' => now()->subDays(4),
                'delivery_reference' => 'SHIP-2026-0006',
                'notes' => '20,000 red bricks, standard quality',
            ],
            
            // Tiles delivery
            [
                'supplier_name' => 'CERAMICS & TILE CENTER',
                'resource_sku' => 'MAT-TILE-CERAMIC',
                'quantity' => 500,
                'unit_price' => 45,
                'receipt_date' => now()->subDays(3),
                'delivery_reference' => 'SHIP-2026-0007',
                'notes' => '500 pieces ceramic tiles 60x60',
            ],
            
            // Gypsum board delivery
            [
                'supplier_name' => 'EMILAM INDUSTRIES L.L.C',
                'resource_sku' => 'MAT-GYPSUM-125',
                'quantity' => 300,
                'unit_price' => 65,
                'receipt_date' => now()->subDays(2),
                'delivery_reference' => 'SHIP-2026-0008',
                'notes' => '300 sheets gypsum 12.5mm',
            ],
            
            // Plywood delivery
            [
                'supplier_name' => 'LUMBER WORLD BUILDING MATERIAL TRADING LLC',
                'resource_sku' => 'MAT-PLYWOOD-12',
                'quantity' => 150,
                'unit_price' => 285,
                'receipt_date' => now()->subDays(1),
                'delivery_reference' => 'SHIP-2026-0009',
                'notes' => 'Marine plywood 12mm sheets',
            ],
            
            // Paint deliveries
            [
                'supplier_name' => 'EVI PAINTS & COATINGS',
                'resource_sku' => 'PAINT-EMUL-WHITE',
                'quantity' => 200,
                'unit_price' => 125,
                'receipt_date' => now()->subDays(10),
                'delivery_reference' => 'SHIP-2026-0010',
                'notes' => '200L white emulsion paint',
            ],
            [
                'supplier_name' => 'AL RAMADI PAINTS TRADING L L C',
                'resource_sku' => 'PAINT-OIL-BROWN',
                'quantity' => 150,
                'unit_price' => 185,
                'receipt_date' => now()->subDays(8),
                'delivery_reference' => 'SHIP-2026-0011',
                'notes' => '150L brown oil paint',
            ],
            [
                'supplier_name' => 'MAS PAINTS & CHEMICALS INDUSRTY',
                'resource_sku' => 'PAINT-PRIMER',
                'quantity' => 100,
                'unit_price' => 95,
                'receipt_date' => now()->subDays(5),
                'delivery_reference' => 'SHIP-2026-0012',
                'notes' => '100L primer coating',
            ],
            
            // Equipment delivery
            [
                'supplier_name' => 'FUTURE CHOICE MACHINERY TRADING LLC SP',
                'resource_sku' => 'TOOL-MIXER-CEMENT',
                'quantity' => 3,
                'unit_price' => 8500,
                'receipt_date' => now()->subDays(12),
                'delivery_reference' => 'SHIP-2026-0013',
                'notes' => '3x cement mixers 200L capacity',
            ],
            
            // Scaffolding
            [
                'supplier_name' => 'AL WISAM AL FADHI BLDG. EQUIP. RENT LLC',
                'resource_sku' => 'TOOL-SCAFFOLD-1IN',
                'quantity' => 1000,
                'unit_price' => 45,
                'receipt_date' => now()->subDays(9),
                'delivery_reference' => 'SHIP-2026-0014',
                'notes' => '1000m scaffolding pipes 1 inch',
            ],
            
            // Safety equipment
            [
                'supplier_name' => 'HANDS MIDDLE EAST LLC',
                'resource_sku' => 'TOOL-HELMET-SAFETY',
                'quantity' => 500,
                'unit_price' => 85,
                'receipt_date' => now()->subDays(7),
                'delivery_reference' => 'SHIP-2026-0015',
                'notes' => '500 safety helmets',
            ],
            [
                'supplier_name' => 'HANDS MIDDLE EAST LLC',
                'resource_sku' => 'TOOL-GLOVES-LEATHER',
                'quantity' => 1000,
                'unit_price' => 35,
                'receipt_date' => now()->subDays(6),
                'delivery_reference' => 'SHIP-2026-0016',
                'notes' => '1000 pairs leather work gloves',
            ],
            
            // Electrical
            [
                'supplier_name' => 'ELECTRICAL LIGHTING COMPANY L.L.C',
                'resource_sku' => 'ELEC-WIRE-2.5',
                'quantity' => 5000,
                'unit_price' => 8,
                'receipt_date' => now()->subDays(4),
                'delivery_reference' => 'SHIP-2026-0017',
                'notes' => '5000m electrical wire 2.5mm',
            ],
            [
                'supplier_name' => 'HAROON ENG.MAT.MARKETING CO.LLC',
                'resource_sku' => 'ELEC-BREAKER-16A',
                'quantity' => 400,
                'unit_price' => 45,
                'receipt_date' => now()->subDays(3),
                'delivery_reference' => 'SHIP-2026-0018',
                'notes' => '400 MCBs 16A',
            ],
            [
                'supplier_name' => 'SPARK WORLD LIGHTING ELECTRICAL FITTINGS TRADING LLC',
                'resource_sku' => 'ELEC-LED-10W',
                'quantity' => 2000,
                'unit_price' => 25,
                'receipt_date' => now()->subDays(2),
                'delivery_reference' => 'SHIP-2026-0019',
                'notes' => '2000 LED bulbs 10W cool white',
            ],
        ];

        // Get user for created_by
        $user = \App\Models\User::first();

        if (!$user) {
            $this->command->warn('⚠️ No user found! Please run UserSeeder first. Seeding suppliers only.');
            return;
        }

        foreach ($grns as $grnData) {
            // Get supplier
            $supplier = Supplier::where('name', $grnData['supplier_name'])->first();
            if (!$supplier) {
                // Create supplier if doesn't exist
                $supplier = Supplier::create([
                    'name' => $grnData['supplier_name'],
                    'is_active' => true,
                ]);
            }

            // Get resource
            $resource = Resource::where('sku', $grnData['resource_sku'])->first();
            if (!$resource) {
                $this->command->warn("⚠️ Resource {$grnData['resource_sku']} not found, skipping GRN");
                continue;
            }

            // Create GRN
            $grn = GoodsReceiptNote::create([
                'supplier_id' => $supplier->id,
                'resource_id' => $resource->id,
                'quantity_received' => $grnData['quantity'],
                'unit_price' => $grnData['unit_price'],
                'total_value' => $grnData['quantity'] * $grnData['unit_price'],
                'receipt_date' => $grnData['receipt_date'],
                'delivery_reference' => $grnData['delivery_reference'],
                'notes' => $grnData['notes'],
                'created_by' => $user->id,
            ]);

            // Automatically create inventory transaction
            try {
                $service->recordGoodsReceipt($grn, $user);
            } catch (\Exception $e) {
                $this->command->warn("⚠️ Failed to create transaction for GRN {$grn->grn_number}: " . $e->getMessage());
            }
        }

        $this->command->info('✅ ' . count($grns) . ' goods receipts seeded successfully!');
    }
}
