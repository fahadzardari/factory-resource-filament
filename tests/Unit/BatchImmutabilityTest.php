<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Resource;
use App\Models\ResourceBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

class BatchImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that critical batch fields cannot be edited after creation
     */
    public function test_cannot_edit_quantity_purchased_after_creation(): void
    {
        $resource = Resource::create([
            'name' => 'Test Resource',
            'sku' => 'TEST-001',
            'unit_type' => 'kg',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 100,
        ]);

        $batch = ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'BATCH-001',
            'quantity_purchased' => 1000,
            'quantity_remaining' => 1000,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_price' => 100,
            'purchase_date' => now(),
        ]);

        // Try to edit quantity_purchased
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cannot modify 'quantity_purchased' after batch creation");
        
        $batch->quantity_purchased = 2000;
        $batch->save();
    }

    /**
     * Test that unit_type cannot be changed after creation
     */
    public function test_cannot_edit_unit_type_after_creation(): void
    {
        $resource = Resource::create([
            'name' => 'Test Resource',
            'sku' => 'TEST-002',
            'unit_type' => 'kg',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 100,
        ]);

        $batch = ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'BATCH-002',
            'quantity_purchased' => 1000,
            'quantity_remaining' => 1000,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_price' => 100,
            'purchase_date' => now(),
        ]);

        // Try to change unit type
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cannot modify 'unit_type' after batch creation");
        
        $batch->unit_type = 'tons';
        $batch->save();
    }

    /**
     * Test that purchase_price cannot be changed after creation
     */
    public function test_cannot_edit_purchase_price_after_creation(): void
    {
        $resource = Resource::create([
            'name' => 'Test Resource',
            'sku' => 'TEST-003',
            'unit_type' => 'kg',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 100,
        ]);

        $batch = ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'BATCH-003',
            'quantity_purchased' => 1000,
            'quantity_remaining' => 1000,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_price' => 100,
            'purchase_date' => now(),
        ]);

        // Try to change price
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cannot modify 'purchase_price' after batch creation");
        
        $batch->purchase_price = 150;
        $batch->save();
    }

    /**
     * Test that non-critical fields CAN still be edited (like notes)
     */
    public function test_can_edit_non_critical_fields(): void
    {
        $resource = Resource::create([
            'name' => 'Test Resource',
            'sku' => 'TEST-004',
            'unit_type' => 'kg',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 100,
        ]);

        $batch = ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'BATCH-004',
            'quantity_purchased' => 1000,
            'quantity_remaining' => 1000,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_price' => 100,
            'purchase_date' => now(),
            'notes' => 'Original note',
        ]);

        // Edit notes - should succeed
        $batch->notes = 'Updated note';
        $batch->save();
        
        $this->assertEquals('Updated note', $batch->fresh()->notes);
    }

    /**
     * Test that batches can be deleted
     */
    public function test_batches_can_be_deleted(): void
    {
        $resource = Resource::create([
            'name' => 'Test Resource',
            'sku' => 'TEST-005',
            'unit_type' => 'kg',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 100,
        ]);

        $batch = ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'BATCH-005',
            'quantity_purchased' => 1000,
            'quantity_remaining' => 1000,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_price' => 100,
            'purchase_date' => now(),
        ]);

        $batchId = $batch->id;
        
        // Delete should work
        $batch->delete();
        
        $this->assertDatabaseMissing('resource_batches', ['id' => $batchId]);
    }
}
