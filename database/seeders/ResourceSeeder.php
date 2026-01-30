<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\ResourcePriceHistory;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    /**
     * Seed the resource catalog for Module 1: Central Inventory Control
     * 
     * Creates a comprehensive catalog of factory resources with:
     * - Proper categorization
     * - Base unit types (batches can have different units)
     * - Initial zero quantities (batches will populate actual stock)
     */
    public function run(): void
    {
        $resources = [
            // ==========================================
            // RAW MATERIALS - Metals
            // ==========================================
            ['name' => 'Steel Rebar 12mm', 'sku' => 'STL-RB-12', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 45.50, 'desc' => 'Standard 12mm reinforcement steel bar for concrete structures'],
            ['name' => 'Steel Rebar 16mm', 'sku' => 'STL-RB-16', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 52.00, 'desc' => 'Heavy-duty 16mm reinforcement steel bar'],
            ['name' => 'Steel Plate 6mm', 'sku' => 'STL-PL-06', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 62.30, 'desc' => 'Flat steel plate, 6mm thickness'],
            ['name' => 'Aluminum Sheet 3mm', 'sku' => 'ALU-SH-03', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 125.00, 'desc' => 'Lightweight aluminum sheeting for fabrication'],
            ['name' => 'Copper Wire 10mm', 'sku' => 'COP-WR-10', 'category' => 'Raw Materials', 'unit_type' => 'meter', 'price' => 8.50, 'desc' => 'Electrical grade copper wiring'],
            
            // ==========================================
            // RAW MATERIALS - Wood & Timber
            // ==========================================
            ['name' => 'Timber Oak', 'sku' => 'TIM-OAK-01', 'category' => 'Raw Materials', 'unit_type' => 'cubic_ft', 'price' => 85.00, 'desc' => 'Premium oak timber for furniture and structural use. Can be measured in cubic feet, kg, or board feet per batch.'],
            ['name' => 'Timber Pine', 'sku' => 'TIM-PIN-01', 'category' => 'Raw Materials', 'unit_type' => 'cubic_ft', 'price' => 45.00, 'desc' => 'Standard pine timber for general construction'],
            ['name' => 'Plywood 18mm', 'sku' => 'PLY-18-STD', 'category' => 'Raw Materials', 'unit_type' => 'sheet', 'price' => 32.00, 'desc' => '4x8 ft plywood sheets, 18mm thickness'],
            
            // ==========================================
            // RAW MATERIALS - Concrete & Aggregates
            // ==========================================
            ['name' => 'Portland Cement Type I', 'sku' => 'CEM-PT-01', 'category' => 'Raw Materials', 'unit_type' => 'kg', 'price' => 0.15, 'desc' => 'General purpose portland cement for all concrete work'],
            ['name' => 'Coarse Sand', 'sku' => 'AGG-CS-01', 'category' => 'Raw Materials', 'unit_type' => 'ton', 'price' => 25.00, 'desc' => 'Washed coarse sand for concrete mixing'],
            ['name' => 'Fine Sand', 'sku' => 'AGG-FS-01', 'category' => 'Raw Materials', 'unit_type' => 'ton', 'price' => 28.50, 'desc' => 'Screened fine sand for plastering and finishing'],
            ['name' => 'Gravel 20mm', 'sku' => 'AGG-GR-20', 'category' => 'Raw Materials', 'unit_type' => 'ton', 'price' => 32.00, 'desc' => 'Crushed gravel aggregate, 20mm nominal size'],
            
            // ==========================================
            // CONSUMABLES
            // ==========================================
            ['name' => 'Industrial Paint White', 'sku' => 'CHM-PNT-WH', 'category' => 'Consumables', 'unit_type' => 'liter', 'price' => 12.50, 'desc' => 'Industrial-grade white paint for metal surfaces'],
            ['name' => 'Industrial Paint Grey', 'sku' => 'CHM-PNT-GR', 'category' => 'Consumables', 'unit_type' => 'liter', 'price' => 11.50, 'desc' => 'Industrial primer and finish paint'],
            ['name' => 'Epoxy Resin', 'sku' => 'CHM-EPX-01', 'category' => 'Consumables', 'unit_type' => 'liter', 'price' => 45.00, 'desc' => 'Two-part epoxy for bonding and coating'],
            ['name' => 'Welding Rod E6013', 'sku' => 'WLD-E6013', 'category' => 'Consumables', 'unit_type' => 'kg', 'price' => 4.50, 'desc' => 'General purpose mild steel welding electrodes'],
            ['name' => 'Welding Rod E7018', 'sku' => 'WLD-E7018', 'category' => 'Consumables', 'unit_type' => 'kg', 'price' => 6.75, 'desc' => 'Low hydrogen electrodes for structural work'],
            ['name' => 'Bolt M10x50mm', 'sku' => 'FST-BLT-M10', 'category' => 'Consumables', 'unit_type' => 'piece', 'price' => 0.50, 'desc' => 'Hex head bolt with zinc coating'],
            ['name' => 'Nut M10', 'sku' => 'FST-NUT-M10', 'category' => 'Consumables', 'unit_type' => 'piece', 'price' => 0.15, 'desc' => 'Hex nut, M10 thread, zinc plated'],
            ['name' => 'Safety Helmet', 'sku' => 'SAF-HLM-01', 'category' => 'Consumables', 'unit_type' => 'piece', 'price' => 12.00, 'desc' => 'OSHA-compliant hard hat for site work'],
            ['name' => 'Safety Gloves', 'sku' => 'SAF-GLV-01', 'category' => 'Consumables', 'unit_type' => 'pair', 'price' => 8.50, 'desc' => 'Heavy-duty work gloves, leather palm'],
            
            // ==========================================
            // TOOLS
            // ==========================================
            ['name' => 'Angle Grinder 9inch', 'sku' => 'TL-AG-09', 'category' => 'Tools', 'unit_type' => 'piece', 'price' => 185.00, 'desc' => 'Heavy-duty 9-inch angle grinder for metal cutting'],
            ['name' => 'Cordless Drill 18V', 'sku' => 'TL-DRL-18V', 'category' => 'Tools', 'unit_type' => 'piece', 'price' => 225.00, 'desc' => 'Professional 18V cordless drill with battery'],
            ['name' => 'Hammer 500g', 'sku' => 'TL-HM-500', 'category' => 'Tools', 'unit_type' => 'piece', 'price' => 15.00, 'desc' => 'Standard claw hammer, fiberglass handle'],
            ['name' => 'Measuring Tape 25m', 'sku' => 'TL-MT-25', 'category' => 'Tools', 'unit_type' => 'piece', 'price' => 18.00, 'desc' => 'Professional-grade 25m measuring tape'],
            
            // ==========================================
            // EQUIPMENT
            // ==========================================
            ['name' => 'Concrete Mixer 350L', 'sku' => 'EQ-CM-350', 'category' => 'Equipment', 'unit_type' => 'piece', 'price' => 1250.00, 'desc' => 'Electric concrete mixer, 350L drum capacity'],
            ['name' => 'Welding Machine 200A', 'sku' => 'EQ-WM-200', 'category' => 'Equipment', 'unit_type' => 'piece', 'price' => 850.00, 'desc' => 'MMA welding machine, 200 amp output'],
        ];

        foreach ($resources as $resourceData) {
            Resource::create([
                'name' => $resourceData['name'],
                'sku' => $resourceData['sku'],
                'category' => $resourceData['category'],
                'unit_type' => $resourceData['unit_type'],
                'purchase_price' => $resourceData['price'],
                'total_quantity' => 0, // Will be populated by batches
                'available_quantity' => 0, // Will be populated by batches
                'description' => $resourceData['desc'],
            ]);
        }
    }
}
