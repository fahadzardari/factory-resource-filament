<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\ResourceBatch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ResourceBatchSeeder extends Seeder
{
    /**
     * Seed resource batches demonstrating multi-unit support
     * 
     * Key features demonstrated:
     * - Multiple batches per resource with different units
     * - FIFO inventory tracking (oldest batches first)
     * - Different suppliers and pricing per batch
     * - Quantity remaining accurately tracked
     */
    public function run(): void
    {
        $resources = Resource::all();
        
        // Map resource SKUs to specific batch configurations
        // This demonstrates multi-unit purchasing (e.g., timber in cubic feet AND kg)
        $customBatches = $this->getCustomBatchConfigs();
        
        foreach ($resources as $resource) {
            if (isset($customBatches[$resource->sku])) {
                // Use custom batch configuration for specific resources
                foreach ($customBatches[$resource->sku] as $batchConfig) {
                    $this->createBatch($resource, $batchConfig);
                }
            } else {
                // Generate default batches for other resources
                $this->createDefaultBatches($resource);
            }
        }
    }
    
    /**
     * Custom batch configurations to demonstrate multi-unit support
     */
    private function getCustomBatchConfigs(): array
    {
        return [
            // Timber Oak - purchased in different units (cubic feet, kg, cubic meters)
            'TIM-OAK-01' => [
                [
                    'unit_type' => 'cubic_ft',
                    'quantity' => 150.00,
                    'remaining' => 150.00,
                    'price' => 85.00,
                    'supplier' => 'Premium Woods Ltd.',
                    'days_ago' => 90,
                    'notes' => 'Initial stock in cubic feet',
                ],
                [
                    'unit_type' => 'kg',
                    'conversion_factor' => 22.5, // approx kg per cubic foot
                    'quantity' => 500.00,
                    'remaining' => 350.00,
                    'price' => 3.75, // per kg
                    'supplier' => 'Forest Direct',
                    'days_ago' => 45,
                    'notes' => 'Bulk purchase by weight',
                ],
                [
                    'unit_type' => 'cubic_m',
                    'conversion_factor' => 35.315, // cubic feet per cubic meter
                    'quantity' => 2.5,
                    'remaining' => 2.5,
                    'price' => 2800.00, // per cubic meter
                    'supplier' => 'International Timber Co.',
                    'days_ago' => 15,
                    'notes' => 'Imported batch in metric units',
                ],
            ],
            
            // Timber Pine - also multi-unit
            'TIM-PIN-01' => [
                [
                    'unit_type' => 'cubic_ft',
                    'quantity' => 300.00,
                    'remaining' => 200.00,
                    'price' => 45.00,
                    'supplier' => 'Local Sawmill',
                    'days_ago' => 60,
                    'notes' => 'Standard grade pine',
                ],
                [
                    'unit_type' => 'bundle',
                    'quantity' => 25,
                    'remaining' => 20,
                    'price' => 120.00, // per bundle
                    'supplier' => 'BuildRight Suppliers',
                    'days_ago' => 20,
                    'notes' => 'Pre-cut construction bundles',
                ],
            ],
            
            // Steel Rebar - different suppliers, same unit + ton purchase
            'STL-RB-12' => [
                [
                    'unit_type' => 'kg',
                    'quantity' => 2000.00,
                    'remaining' => 1500.00,
                    'price' => 44.00,
                    'supplier' => 'SteelMasters Inc.',
                    'days_ago' => 120,
                    'notes' => 'Bulk order - slight discount',
                ],
                [
                    'unit_type' => 'kg',
                    'quantity' => 1000.00,
                    'remaining' => 800.00,
                    'price' => 46.50,
                    'supplier' => 'Metro Steel Supply',
                    'days_ago' => 60,
                    'notes' => 'Standard pricing',
                ],
                [
                    'unit_type' => 'ton',
                    'conversion_factor' => 1000, // kg per ton
                    'quantity' => 3.0,
                    'remaining' => 3.0,
                    'price' => 43000.00, // per ton
                    'supplier' => 'Industrial Metals Corp.',
                    'days_ago' => 5,
                    'notes' => 'Bulk ton purchase with volume discount',
                ],
            ],
            
            // Paint - in liters and gallons
            'CHM-PNT-WH' => [
                [
                    'unit_type' => 'liter',
                    'quantity' => 200.00,
                    'remaining' => 150.00,
                    'price' => 12.50,
                    'supplier' => 'ColorTech Paints',
                    'days_ago' => 45,
                    'notes' => 'Standard white industrial paint',
                ],
                [
                    'unit_type' => 'gallon',
                    'conversion_factor' => 3.785, // liters per gallon
                    'quantity' => 20,
                    'remaining' => 18,
                    'price' => 45.00, // per gallon
                    'supplier' => 'Import Paints Ltd.',
                    'days_ago' => 10,
                    'notes' => 'Premium grade, gallon containers',
                ],
            ],
        ];
    }
    
    /**
     * Create a batch from configuration
     */
    private function createBatch(Resource $resource, array $config): void
    {
        $purchaseDate = now()->subDays($config['days_ago']);
        
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'BATCH-' . $purchaseDate->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
            'unit_type' => $config['unit_type'],
            'conversion_factor' => $config['conversion_factor'] ?? 1.0,
            'purchase_price' => $config['price'],
            'quantity_purchased' => $config['quantity'],
            'quantity_remaining' => $config['remaining'],
            'purchase_date' => $purchaseDate,
            'supplier' => $config['supplier'],
            'notes' => $config['notes'] ?? null,
        ]);
    }
    
    /**
     * Create default batches for resources without custom config
     */
    private function createDefaultBatches(Resource $resource): void
    {
        // Create 2-3 batches per resource
        $batchCount = rand(2, 3);
        
        for ($i = 0; $i < $batchCount; $i++) {
            $daysAgo = 120 - ($i * 40) + rand(-10, 10);
            $purchaseDate = now()->subDays(max(1, $daysAgo));
            
            // Base quantity varies by resource type
            $baseQuantity = $this->getBaseQuantity($resource);
            $quantity = $baseQuantity * (rand(50, 150) / 100);
            
            // Some consumption for older batches
            $consumptionRate = $i == 0 ? rand(20, 50) / 100 : rand(0, 30) / 100;
            $remaining = round($quantity * (1 - $consumptionRate), 2);
            
            // Price variation
            $priceVariation = (rand(90, 110) / 100);
            $price = round($resource->purchase_price * $priceVariation, 2);
            
            ResourceBatch::create([
                'resource_id' => $resource->id,
                'batch_number' => 'BATCH-' . $purchaseDate->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
                'unit_type' => $resource->unit_type, // Use resource's default unit
                'conversion_factor' => 1.0,
                'purchase_price' => $price,
                'quantity_purchased' => round($quantity, 2),
                'quantity_remaining' => $remaining,
                'purchase_date' => $purchaseDate,
                'supplier' => $this->getRandomSupplier(),
                'notes' => $this->getRandomNote(),
            ]);
        }
    }
    
    /**
     * Get base quantity based on resource category and unit type
     */
    private function getBaseQuantity(Resource $resource): float
    {
        return match($resource->unit_type) {
            'piece' => rand(10, 50),
            'pair' => rand(10, 30),
            'kg' => rand(100, 1000),
            'ton' => rand(2, 10),
            'liter' => rand(50, 200),
            'meter' => rand(50, 500),
            'cubic_ft', 'cubic_m' => rand(10, 100),
            'sheet' => rand(20, 100),
            default => rand(50, 200),
        };
    }
    
    private function getRandomSupplier(): string
    {
        $suppliers = [
            'Global Steel Co.',
            'Acme Manufacturing',
            'Industrial Supplies Inc.',
            'Premier Materials Ltd.',
            'Quality Components Corp.',
            'TechParts International',
            'BuildRight Suppliers',
            'MegaFactory Direct',
            'Wholesale Materials Co.',
            'FastShip Industries',
        ];
        
        return $suppliers[array_rand($suppliers)];
    }
    
    private function getRandomNote(): ?string
    {
        if (rand(0, 100) < 30) {
            return null; // 30% chance of no note
        }
        
        $notes = [
            'Bulk purchase discount applied',
            'Express delivery surcharge',
            'Premium quality grade',
            'Standard grade material',
            'Seasonal pricing',
            'Long-term contract pricing',
            'Spot market purchase',
            'Imported batch',
            'Domestic supplier',
            'Quality certified batch',
        ];
        
        return $notes[array_rand($notes)];
    }
}
