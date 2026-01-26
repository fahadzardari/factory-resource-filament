<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\ResourcePriceHistory;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            // Raw Materials - Metals
            ['name' => 'Steel Rebar 12mm', 'sku' => 'STL-RB-12', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 45.50],
            ['name' => 'Steel Rebar 16mm', 'sku' => 'STL-RB-16', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 52.00],
            ['name' => 'Steel Rebar 20mm', 'sku' => 'STL-RB-20', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 58.75],
            ['name' => 'Steel Plate 6mm', 'sku' => 'STL-PL-06', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 62.30],
            ['name' => 'Steel Plate 8mm', 'sku' => 'STL-PL-08', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 68.90],
            ['name' => 'Aluminum Sheet 3mm', 'sku' => 'ALU-SH-03', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 125.00],
            ['name' => 'Copper Wire 10mm', 'sku' => 'COP-WR-10', 'category' => 'Raw Materials', 'unit_type' => 'meter', 'price' => 8.50],
            ['name' => 'Brass Rod 15mm', 'sku' => 'BRS-RD-15', 'category' => 'Raw Materials', 'unit_type' => 'meter', 'price' => 12.75],
            ['name' => 'Stainless Steel 304', 'sku' => 'SS-304-SH', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 185.00],
            ['name' => 'Galvanized Steel Coil', 'sku' => 'GS-COIL-1', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 72.50],
            
            // Raw Materials - Concrete
            ['name' => 'Portland Cement Type I', 'sku' => 'CEM-PT-01', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 0.15],
            ['name' => 'Portland Cement Type II', 'sku' => 'CEM-PT-02', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 0.18],
            ['name' => 'Coarse Sand', 'sku' => 'AGG-CS-01', 'category' => 'Raw Materials', 'unit_type' => 'ton', 'price' => 25.00],
            ['name' => 'Fine Sand', 'sku' => 'AGG-FS-01', 'category' => 'Raw Materials', 'unit_type' => 'ton', 'price' => 28.50],
            ['name' => 'Gravel 20mm', 'sku' => 'AGG-GR-20', 'category' => 'Raw Materials', 'unit_type' => 'ton', 'price' => 32.00],
            
            // Consumables
            ['name' => 'Industrial Paint White', 'sku' => 'CHM-PNT-WH', 'category' => 'Consumables', 'unit_type' => 'liter', 'price' => 12.50],
            ['name' => 'Epoxy Resin', 'sku' => 'CHM-EPX-01', 'category' => 'Consumables', 'unit_type' => 'liter', 'price' => 45.00],
            ['name' => 'Welding Rod E6013', 'sku' => 'WLD-E6013-32', 'category' => 'Consumables', 'unit_type' => 'kg', 'price' => 4.50],
            ['name' => 'Bolt M10x50mm', 'sku' => 'FST-BLT-M1050', 'category' => 'Consumables', 'unit_type' => 'piece', 'price' => 0.50],
            ['name' => 'Safety Helmet', 'sku' => 'SAF-HLM-01', 'category' => 'Consumables', 'unit_type' => 'piece', 'price' => 12.00],
            
            // Tools
            ['name' => 'Angle Grinder 9inch', 'sku' => 'TL-AG-09', 'category' => 'Tools', 'unit_type' => 'piece', 'price' => 185.00],
            ['name' => 'Cordless Drill 18V', 'sku' => 'TL-DRL-18V', 'category' => 'Tools', 'unit_type' => 'piece', 'price' => 225.00],
            ['name' => 'Hammer 500g', 'sku' => 'TL-HM-500', 'category' => 'Tools', 'unit_type' => 'piece', 'price' => 15.00],
            
            // Equipment
            ['name' => 'Forklift 3 Ton', 'sku' => 'EQ-FL-03T', 'category' => 'Equipment', 'unit_type' => 'piece', 'price' => 28500.00],
            ['name' => 'Concrete Mixer 350L', 'sku' => 'EQ-CM-350', 'category' => 'Equipment', 'unit_type' => 'piece', 'price' => 1250.00],
        ];

        foreach ($resources as $resourceData) {
            $totalQty = rand(100, 5000);
            $allocatedQty = rand(0, (int)($totalQty * 0.6));
            $availableQty = $totalQty - $allocatedQty;
            
            $resource = Resource::create([
                'name' => $resourceData['name'],
                'sku' => $resourceData['sku'],
                'category' => $resourceData['category'],
                'unit_type' => $resourceData['unit_type'],
                'purchase_price' => $resourceData['price'],
                'total_quantity' => $totalQty,
                'available_quantity' => $availableQty,
                'description' => 'High quality ' . strtolower($resourceData['category']) . ' used in various manufacturing processes.',
            ]);

            // Add price history
            $historyCount = rand(2, 5);
            $currentPrice = $resourceData['price'];
            
            for ($i = 0; $i < $historyCount; $i++) {
                $daysAgo = rand(30 * $i, 30 * ($i + 2));
                $priceVariation = rand(-15, 10) / 100;
                $historicalPrice = $currentPrice * (1 + $priceVariation);
                
                $suppliers = ['Global Suppliers Inc.', 'Prime Materials Ltd.', 'Industrial Resources Co.', 'Quality Trade Partners', 'Metro Supply Chain'];
                
                ResourcePriceHistory::create([
                    'resource_id' => $resource->id,
                    'price' => round($historicalPrice, 2),
                    'supplier' => $suppliers[array_rand($suppliers)],
                    'purchase_date' => now()->subDays($daysAgo),
                    'quantity_purchased' => rand(50, 1000),
                    'notes' => $i === 0 ? 'Most recent purchase' : 'Bulk order',
                ]);
            }
        }
    }
}
