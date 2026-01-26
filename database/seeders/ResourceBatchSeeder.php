<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\ResourceBatch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ResourceBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all resources
        $resources = Resource::all();
        
        foreach ($resources as $resource) {
            // Create 2-4 batches per resource with different prices
            $batchCount = rand(2, 4);
            
            for ($i = 0; $i < $batchCount; $i++) {
                // Calculate purchase date (spread over last 6 months)
                $daysAgo = rand(0, 180);
                $purchaseDate = now()->subDays($daysAgo);
                
                // Calculate quantity for this batch (distribute total quantity)
                $remainingQty = $resource->available_quantity;
                if ($i == $batchCount - 1) {
                    // Last batch gets remaining quantity
                    $quantity = $remainingQty;
                } else {
                    // Random portion of remaining
                    $quantity = round($remainingQty * (rand(20, 40) / 100), 2);
                }
                
                // Price varies by ±30% from base price
                $priceVariation = (rand(70, 130) / 100);
                $price = round($resource->purchase_price * $priceVariation, 2);
                
                // Some batches are partially consumed
                $quantityRemaining = $quantity;
                if (rand(0, 100) > 40) { // 60% chance of partial consumption
                    $consumedPercent = rand(0, 60) / 100; // 0-60% consumed
                    $quantityRemaining = round($quantity * (1 - $consumedPercent), 2);
                }
                
                ResourceBatch::create([
                    'resource_id' => $resource->id,
                    'batch_number' => 'BATCH-' . $purchaseDate->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
                    'purchase_price' => $price,
                    'quantity_purchased' => $quantity,
                    'quantity_remaining' => $quantityRemaining,
                    'purchase_date' => $purchaseDate,
                    'supplier' => $this->getRandomSupplier(),
                    'notes' => rand(0, 100) > 70 ? $this->getRandomNote() : null,
                ]);
            }
            
            // Update resource's available quantity to match batch totals
            $totalRemaining = ResourceBatch::where('resource_id', $resource->id)
                ->sum('quantity_remaining');
            $resource->update(['available_quantity' => $totalRemaining]);
        }
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
    
    private function getRandomNote(): string
    {
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
            'Certified material',
        ];
        
        return $notes[array_rand($notes)];
    }
}
