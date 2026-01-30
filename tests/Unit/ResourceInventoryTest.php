<?php

namespace Tests\Unit;

use App\Models\Resource;
use App\Models\ResourceBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ResourceInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected Resource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test resource
        $this->resource = Resource::create([
            'name' => 'Test Steel',
            'sku' => 'TST-STL-001',
            'category' => 'Raw Materials',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'total_quantity' => 0,
            'available_quantity' => 0,
        ]);
    }

    // ========================================
    // RESOURCE NON-NEGATIVE TESTS
    // ========================================

    public function test_resource_cannot_have_negative_total_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total quantity cannot be negative');

        $this->resource->total_quantity = -10;
        $this->resource->save();
    }

    public function test_resource_cannot_have_negative_available_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Available quantity cannot be negative');

        $this->resource->available_quantity = -5;
        $this->resource->save();
    }

    public function test_resource_cannot_have_negative_purchase_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Purchase price cannot be negative');

        $this->resource->purchase_price = -1.50;
        $this->resource->save();
    }

    public function test_resource_allows_zero_quantities(): void
    {
        $this->resource->total_quantity = 0;
        $this->resource->available_quantity = 0;
        $this->resource->save();

        $this->assertEquals(0, $this->resource->fresh()->total_quantity);
        $this->assertEquals(0, $this->resource->fresh()->available_quantity);
    }

    // ========================================
    // BATCH NON-NEGATIVE TESTS
    // ========================================

    public function test_batch_cannot_have_negative_quantity_purchased(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity purchased cannot be negative');

        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-001',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => -100,
            'quantity_remaining' => 0,
            'purchase_date' => now(),
        ]);
    }

    public function test_batch_cannot_have_negative_quantity_remaining(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity remaining cannot be negative');

        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-002',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => -10,
            'purchase_date' => now(),
        ]);
    }

    public function test_batch_quantity_remaining_cannot_exceed_purchased(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity remaining cannot exceed quantity purchased');

        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-003',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 150, // More than purchased
            'purchase_date' => now(),
        ]);
    }

    public function test_batch_cannot_have_negative_purchase_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Purchase price cannot be negative');

        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-004',
            'unit_type' => 'kg',
            'purchase_price' => -25.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 100,
            'purchase_date' => now(),
        ]);
    }

    // ========================================
    // BATCH AUTO-SYNC TESTS
    // ========================================

    public function test_creating_batch_updates_resource_quantity(): void
    {
        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-SYNC-001',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 100,
            'purchase_date' => now(),
        ]);

        $this->resource->refresh();
        $this->assertEquals(100, $this->resource->available_quantity);
    }

    public function test_multiple_batches_sum_correctly(): void
    {
        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-MULTI-001',
            'unit_type' => 'kg',
            'purchase_price' => 45.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 80,
            'purchase_date' => now()->subDays(30),
        ]);

        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-MULTI-002',
            'unit_type' => 'kg',
            'purchase_price' => 55.00,
            'quantity_purchased' => 200,
            'quantity_remaining' => 200,
            'purchase_date' => now(),
        ]);

        $this->resource->refresh();
        $this->assertEquals(280, $this->resource->available_quantity); // 80 + 200
    }

    // ========================================
    // MULTI-UNIT BATCH TESTS
    // ========================================

    public function test_batch_can_have_different_unit_than_resource(): void
    {
        $batch = ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-UNIT-001',
            'unit_type' => 'ton', // Different from resource's 'kg'
            'conversion_factor' => 1000, // 1 ton = 1000 kg
            'purchase_price' => 45000.00,
            'quantity_purchased' => 5,
            'quantity_remaining' => 5,
            'purchase_date' => now(),
        ]);

        $this->assertEquals('ton', $batch->unit_type);
        $this->assertEquals(1000, $batch->conversion_factor);
        $this->assertEquals('Metric Tons', $batch->unit_label);
    }

    public function test_resource_tracks_units_used(): void
    {
        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-UNITS-001',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 100,
            'purchase_date' => now(),
        ]);

        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-UNITS-002',
            'unit_type' => 'ton',
            'purchase_price' => 45000.00,
            'quantity_purchased' => 2,
            'quantity_remaining' => 2,
            'purchase_date' => now(),
        ]);

        $this->resource->refresh();
        $unitsUsed = $this->resource->units_used;

        $this->assertContains('kg', $unitsUsed);
        $this->assertContains('ton', $unitsUsed);
    }

    // ========================================
    // FIFO CONSUMPTION TESTS
    // ========================================

    public function test_fifo_consumption_uses_oldest_batch_first(): void
    {
        // Create older batch
        $oldBatch = ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-OLD',
            'unit_type' => 'kg',
            'purchase_price' => 45.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 100,
            'purchase_date' => now()->subDays(30),
        ]);

        // Create newer batch
        $newBatch = ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-NEW',
            'unit_type' => 'kg',
            'purchase_price' => 55.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 100,
            'purchase_date' => now(),
        ]);

        // Consume 50 units
        $this->resource->consumeQuantityFifo(50);

        $oldBatch->refresh();
        $newBatch->refresh();

        // Old batch should be reduced, new batch untouched
        $this->assertEquals(50, $oldBatch->quantity_remaining);
        $this->assertEquals(100, $newBatch->quantity_remaining);
    }

    public function test_fifo_consumption_spans_multiple_batches(): void
    {
        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-SPAN-1',
            'unit_type' => 'kg',
            'purchase_price' => 45.00,
            'quantity_purchased' => 30,
            'quantity_remaining' => 30,
            'purchase_date' => now()->subDays(30),
        ]);

        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-SPAN-2',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 50,
            'quantity_remaining' => 50,
            'purchase_date' => now()->subDays(15),
        ]);

        // Consume 60 units (spans both batches)
        $cost = $this->resource->consumeQuantityFifo(60);

        $this->resource->refresh();
        
        // Should have 20 remaining (30 + 50 - 60)
        $this->assertEquals(20, $this->resource->available_quantity);
        
        // Cost should be 30*45 + 30*50 = 1350 + 1500 = 2850
        $this->assertEquals(2850, $cost);
    }

    public function test_consumption_fails_for_insufficient_quantity(): void
    {
        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-INSUF',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 50,
            'quantity_remaining' => 50,
            'purchase_date' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient quantity');

        $this->resource->consumeQuantityFifo(100); // More than available
    }

    // ========================================
    // BATCH CONSUME/RESTORE TESTS
    // ========================================

    public function test_batch_consume_method(): void
    {
        $batch = ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-CONSUME',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 100,
            'purchase_date' => now(),
        ]);

        $consumed = $batch->consume(30);

        $batch->refresh();
        $this->assertEquals(30, $consumed);
        $this->assertEquals(70, $batch->quantity_remaining);
    }

    public function test_batch_consume_cannot_exceed_remaining(): void
    {
        $batch = ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-OVER',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 50,
            'quantity_remaining' => 50,
            'purchase_date' => now(),
        ]);

        // Try to consume more than available
        $consumed = $batch->consume(100);

        $batch->refresh();
        $this->assertEquals(50, $consumed); // Only consumed what was available
        $this->assertEquals(0, $batch->quantity_remaining);
    }

    public function test_batch_restore_method(): void
    {
        $batch = ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-RESTORE',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 70, // 30 used
            'purchase_date' => now(),
        ]);

        $batch->restore(20); // Restore 20 of the 30 used

        $batch->refresh();
        $this->assertEquals(90, $batch->quantity_remaining);
    }

    public function test_batch_restore_cannot_exceed_original_quantity(): void
    {
        $batch = ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-OVER-RESTORE',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 80,
            'purchase_date' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot restore more than original quantity');

        $batch->restore(50); // Would make remaining 130 > purchased 100
    }

    // ========================================
    // COMPUTED ATTRIBUTE TESTS
    // ========================================

    public function test_batch_computed_attributes(): void
    {
        $batch = ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-ATTR',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 60,
            'purchase_date' => now(),
        ]);

        $this->assertEquals(40, $batch->quantity_used);
        $this->assertEquals(40.0, $batch->usage_percentage);
        $this->assertEquals(3000, $batch->total_value); // 60 * 50
        $this->assertFalse($batch->is_depleted);
        $this->assertTrue($batch->has_stock);
    }

    public function test_depleted_batch_attributes(): void
    {
        $batch = ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-EMPTY',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 0,
            'purchase_date' => now(),
        ]);

        $this->assertEquals(100, $batch->quantity_used);
        $this->assertEquals(100.0, $batch->usage_percentage);
        $this->assertEquals(0, $batch->total_value);
        $this->assertTrue($batch->is_depleted);
        $this->assertFalse($batch->has_stock);
    }

    public function test_resource_weighted_average_price(): void
    {
        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-AVG-1',
            'unit_type' => 'kg',
            'purchase_price' => 40.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 100,
            'purchase_date' => now()->subDays(30),
        ]);

        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-AVG-2',
            'unit_type' => 'kg',
            'purchase_price' => 60.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 100,
            'purchase_date' => now(),
        ]);

        $this->resource->refresh();
        
        // Weighted average: (100*40 + 100*60) / 200 = 10000 / 200 = 50
        $this->assertEquals(50, $this->resource->weighted_average_price);
        $this->assertEquals(10000, $this->resource->total_value);
    }

    public function test_resource_has_sufficient_quantity(): void
    {
        ResourceBatch::create([
            'resource_id' => $this->resource->id,
            'batch_number' => 'BATCH-SUF',
            'unit_type' => 'kg',
            'purchase_price' => 50.00,
            'quantity_purchased' => 100,
            'quantity_remaining' => 100,
            'purchase_date' => now(),
        ]);

        $this->resource->refresh();

        $this->assertTrue($this->resource->hasSufficientQuantity(50));
        $this->assertTrue($this->resource->hasSufficientQuantity(100));
        $this->assertFalse($this->resource->hasSufficientQuantity(150));
    }
}
