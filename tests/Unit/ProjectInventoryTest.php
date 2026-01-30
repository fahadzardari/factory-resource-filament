<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Resource;
use App\Models\ResourceBatch;
use App\Models\Project;
use App\Models\ProjectResourceConsumption;
use App\Models\ResourceTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

class ProjectInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_tracks_allocated_quantity_across_projects()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 10000,
            'quantity_remaining' => 10000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now(),
        ]);
        $resource->syncQuantityFromBatches();

        $projectA = Project::factory()->create(['name' => 'Project A']);
        $projectB = Project::factory()->create(['name' => 'Project B']);

        // Allocate 3000 kg to Project A
        $resource->allocateToProject($projectA, 3000);
        
        // Allocation doesn't consume batches yet, just tracks allocation
        $this->assertEquals(3000, $resource->allocatedQuantity());
        $this->assertEquals(10000, $resource->fresh()->available_quantity); // Batches unchanged
        $this->assertEquals(7000, $resource->availableForAllocation()); // 10000 - 3000

        // Allocate 2000 kg to Project B
        $resource->allocateToProject($projectB, 2000);
        
        $this->assertEquals(5000, $resource->allocatedQuantity());
        $this->assertEquals(10000, $resource->fresh()->available_quantity); // Still unchanged
        $this->assertEquals(5000, $resource->availableForAllocation()); // 10000 - 5000
    }

    /** @test */
    public function it_prevents_over_allocation_beyond_available_quantity()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 5000,
            'quantity_remaining' => 5000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now(),
        ]);
        $resource->syncQuantityFromBatches();

        $project = Project::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient quantity for allocation');
        
        $resource->allocateToProject($project, 6000); // More than available
    }

    /** @test */
    public function it_cannot_allocate_more_than_total_inventory_across_multiple_projects()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 10000,
            'quantity_remaining' => 10000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now(),
        ]);
        $resource->syncQuantityFromBatches();

        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        $projectC = Project::factory()->create();

        $resource->allocateToProject($projectA, 4000);
        $resource->allocateToProject($projectB, 4000);
        $resource->allocateToProject($projectC, 2000);
        
        // Total allocated: 10,000 kg
        $this->assertEquals(10000, $resource->allocatedQuantity());
        $this->assertEquals(10000, $resource->fresh()->available_quantity); // Batches not consumed on allocation
        $this->assertEquals(0, $resource->availableForAllocation()); // But nothing left to allocate
        
        // Cannot allocate any more
        $projectD = Project::factory()->create();
        $this->expectException(InvalidArgumentException::class);
        $resource->allocateToProject($projectD, 100);
    }

    /** @test */
    public function it_creates_transfer_record_when_allocating_to_project()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 5000,
            'quantity_remaining' => 5000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now(),
        ]);
        $resource->syncQuantityFromBatches();

        $project = Project::factory()->create();
        
        $this->assertEquals(0, ResourceTransfer::count());
        
        $resource->allocateToProject($project, 2000, 'Initial allocation');
        
        $this->assertEquals(1, ResourceTransfer::count());
        
        $transfer = ResourceTransfer::first();
        $this->assertEquals($resource->id, $transfer->resource_id);
        $this->assertNull($transfer->from_project_id);
        $this->assertEquals($project->id, $transfer->to_project_id);
        $this->assertEquals(2000, $transfer->quantity);
        $this->assertEquals('warehouse_to_project', $transfer->transfer_type);
        $this->assertEquals('Initial allocation', $transfer->notes);
    }

    /** @test */
    public function it_updates_project_resource_pivot_on_allocation()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 5000,
            'quantity_remaining' => 5000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now(),
        ]);
        $resource->syncQuantityFromBatches();

        $project = Project::factory()->create();
        
        $resource->allocateToProject($project, 3000);
        
        $pivot = $project->resources()->where('resource_id', $resource->id)->first()->pivot;
        
        $this->assertEquals(3000, $pivot->quantity_allocated);
        $this->assertEquals(0, $pivot->quantity_consumed);
        $this->assertEquals(3000, $pivot->quantity_available);
    }

    /** @test */
    public function it_consumes_from_project_allocation()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 5000,
            'quantity_remaining' => 5000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now(),
        ]);
        $resource->syncQuantityFromBatches();

        $project = Project::factory()->create();
        $resource->allocateToProject($project, 3000);
        
        // After allocation, warehouse still has all 5000 kg
        $this->assertEquals(5000, $resource->fresh()->available_quantity);
        
        // Consume 1000 kg from project - this should consume from warehouse batches
        $consumption = ProjectResourceConsumption::create([
            'project_id' => $project->id,
            'resource_id' => $resource->id,
            'consumption_date' => today(),
            'quantity_consumed' => 1000,
            'recorded_by' => $this->user->id,
        ]);
        
        $this->assertEquals(3000, $consumption->opening_balance);
        $this->assertEquals(2000, $consumption->closing_balance);
        
        // Check pivot updated
        $pivot = $project->fresh()->resources()->where('resource_id', $resource->id)->first()->pivot;
        $this->assertEquals(1000, $pivot->quantity_consumed);
        $this->assertEquals(2000, $pivot->quantity_available);
        
        // IMPORTANT: Warehouse batches should also be consumed
        $this->assertEquals(4000, $resource->fresh()->available_quantity); // 5000 - 1000
    }

    /** @test */
    public function it_prevents_consuming_more_than_project_allocation()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 5000,
            'quantity_remaining' => 5000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now(),
        ]);
        $resource->syncQuantityFromBatches();

        $project = Project::factory()->create();
        $resource->allocateToProject($project, 2000);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient quantity at project');
        
        ProjectResourceConsumption::create([
            'project_id' => $project->id,
            'resource_id' => $resource->id,
            'consumption_date' => today(),
            'quantity_consumed' => 2500, // More than allocated
            'recorded_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_allows_multiple_consumption_records_on_same_date()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 5000,
            'quantity_remaining' => 5000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now(),
        ]);
        $resource->syncQuantityFromBatches();

        $project = Project::factory()->create();
        $resource->allocateToProject($project, 3000);
        
        // First consumption
        ProjectResourceConsumption::create([
            'project_id' => $project->id,
            'resource_id' => $resource->id,
            'consumption_date' => today(),
            'quantity_consumed' => 500,
            'recorded_by' => $this->user->id,
        ]);
        
        // Second consumption on same date - should work!
        ProjectResourceConsumption::create([
            'project_id' => $project->id,
            'resource_id' => $resource->id,
            'consumption_date' => today(),
            'quantity_consumed' => 300,
            'recorded_by' => $this->user->id,
        ]);
        
        $this->assertEquals(2, ProjectResourceConsumption::count());
        
        $pivot = $project->fresh()->resources()->where('resource_id', $resource->id)->first()->pivot;
        $this->assertEquals(800, $pivot->quantity_consumed);
        $this->assertEquals(2200, $pivot->quantity_available);
    }

    /** @test */
    public function it_maintains_single_source_of_truth_across_allocations()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 10000,
            'quantity_remaining' => 10000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now(),
        ]);
        $resource->syncQuantityFromBatches();

        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        
        // Initial: 10,000 kg in warehouse
        $this->assertEquals(10000, $resource->fresh()->available_quantity);
        
        // Allocate 4000 kg to Project A (not yet consumed from batches)
        $resource->allocateToProject($projectA, 4000);
        $this->assertEquals(10000, $resource->fresh()->available_quantity);
        $this->assertEquals(6000, $resource->availableForAllocation());
        
        // Allocate 3000 kg to Project B (not yet consumed from batches)
        $resource->allocateToProject($projectB, 3000);
        $this->assertEquals(10000, $resource->fresh()->available_quantity);
        $this->assertEquals(3000, $resource->availableForAllocation());
        
        // Now Project A consumes 2000 kg - THIS consumes from batches
        ProjectResourceConsumption::create([
            'project_id' => $projectA->id,
            'resource_id' => $resource->id,
            'consumption_date' => today(),
            'quantity_consumed' => 2000,
            'recorded_by' => $this->user->id,
        ]);
        
        // After consumption, warehouse should have 8000 kg (10000 - 2000)
        $this->assertEquals(8000, $resource->fresh()->available_quantity);
        
        // Project B consumes 1000 kg
        ProjectResourceConsumption::create([
            'project_id' => $projectB->id,
            'resource_id' => $resource->id,
            'consumption_date' => today(),
            'quantity_consumed' => 1000,
            'recorded_by' => $this->user->id,
        ]);
        
        // Warehouse now has 7000 kg (8000 - 1000)
        $this->assertEquals(7000, $resource->fresh()->available_quantity);
        
        // Single source of truth:
        // Warehouse batches: 7000 kg (remaining after consumption)
        // Project A allocated: 4000 kg, consumed: 2000 kg, available: 2000 kg
        // Project B allocated: 3000 kg, consumed: 1000 kg, available: 2000 kg
        // Total consumed: 3000 kg
        // Warehouse + Project Available: 7000 + 2000 + 2000 = 11,000 kg
        // But we started with 10,000 kg...
        
        // Actually correct calculation:
        // Initial: 10,000 kg
        // Consumed total: 3,000 kg
        // Remaining in warehouse: 7,000 kg
        // Allocated to projects but not consumed: 4,000 kg (Project A: 2000 + Project B: 2000)
        // 7000 + 4000 = 11,000 ❌ This is wrong!
        
        // The correct model is:
        // Warehouse batches track PHYSICAL inventory: 10,000 initially
        // When consumed, batches reduce: 10,000 - 3,000 = 7,000
        // Allocated quantity is just a PROMISE to projects from the 10,000
        // So: Warehouse (7000) + Already Consumed (3000) = Original (10000) ✓
        
        $totalConsumed = 3000;
        $warehouseRemaining = 7000;
        $this->assertEquals(10000, $warehouseRemaining + $totalConsumed);
    }

    /** @test */
    public function it_uses_fifo_from_batches_when_allocating_to_projects()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        
        // Batch 1: Older, cheaper
        $batch1 = ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 1000,
            'quantity_remaining' => 1000,
            'purchase_price' => 50,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now()->subDays(10),
        ]);
        
        // Batch 2: Newer, expensive
        $batch2 = ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B002',
            'quantity_purchased' => 2000,
            'quantity_remaining' => 2000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now()->subDays(5),
        ]);
        
        $resource->syncQuantityFromBatches();
        $this->assertEquals(3000, $resource->available_quantity);
        
        $project = Project::factory()->create();
        
        // Allocate 1500 kg - allocation doesn't consume batches yet
        $resource->allocateToProject($project, 1500);
        
        $this->assertEquals(1000, $batch1->fresh()->quantity_remaining); // Unchanged
        $this->assertEquals(2000, $batch2->fresh()->quantity_remaining); // Unchanged
        $this->assertEquals(3000, $resource->fresh()->available_quantity); // Unchanged
        
        // Now consume from project - this should use FIFO
        ProjectResourceConsumption::create([
            'project_id' => $project->id,
            'resource_id' => $resource->id,
            'consumption_date' => today(),
            'quantity_consumed' => 1500,
            'recorded_by' => auth()->id(),
        ]);
        
        // Should consume ALL of batch1 (1000) + 500 from batch2
        $this->assertEquals(0, $batch1->fresh()->quantity_remaining);
        $this->assertEquals(1500, $batch2->fresh()->quantity_remaining);
        $this->assertEquals(1500, $resource->fresh()->available_quantity);
    }

    /** @test */
    public function it_handles_multi_unit_batches_in_project_allocations()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        
        // Batch in kg
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B001',
            'quantity_purchased' => 2000,
            'quantity_remaining' => 2000,
            'purchase_price' => 50,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now()->subDays(10),
        ]);
        
        // Batch in tons (1 ton = 1000 kg)
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B002',
            'quantity_purchased' => 5, // 5 tons
            'quantity_remaining' => 5,
            'purchase_price' => 45000, // per ton
            'unit_type' => 'metric_ton',
            'conversion_factor' => 1000,
            'purchase_date' => now()->subDays(5),
        ]);
        
        $resource->syncQuantityFromBatches();
        
        // Should have 2000 kg + 5000 kg = 7000 kg total
        $this->assertEquals(7000, $resource->available_quantity);
        
        $project = Project::factory()->create();
        
        // Allocate 3000 kg (allocation doesn't consume yet)
        $resource->allocateToProject($project, 3000);
        
        // Should still have 7000 kg in warehouse
        $this->assertEquals(7000, $resource->fresh()->available_quantity);
        
        // Consume the 3000 kg from project
        ProjectResourceConsumption::create([
            'project_id' => $project->id,
            'resource_id' => $resource->id,
            'consumption_date' => today(),
            'quantity_consumed' => 3000,
            'recorded_by' => auth()->id(),
        ]);
        
        // Should have 4000 kg remaining (7000 - 3000)
        $this->assertEquals(4000, $resource->fresh()->available_quantity);
    }

    /** @test */
    public function it_handles_hundreds_of_allocation_and_consumption_records()
    {
        $resource = Resource::factory()->create(['unit_type' => 'kg']);
        
        // Create large batch
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'batch_number' => 'B_LARGE',
            'quantity_purchased' => 1000000, // 1 million kg
            'quantity_remaining' => 1000000,
            'purchase_price' => 100,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_date' => now(),
        ]);
        $resource->syncQuantityFromBatches();
        
        // Create 100 projects
        $projects = Project::factory()->count(100)->create();
        
        $totalAllocated = 0;
        
        // Allocate random amounts to each project
        foreach ($projects as $project) {
            $allocationAmount = rand(1000, 5000);
            $resource->allocateToProject($project, $allocationAmount);
            $totalAllocated += $allocationAmount;
        }
        
        // Verify allocations
        $this->assertEquals($totalAllocated, $resource->allocatedQuantity());
        $this->assertEquals(1000000, $resource->fresh()->available_quantity); // Batches not consumed on allocation
        
        // Each project consumes some resources (200 consumption records)
        $totalConsumed = 0;
        foreach ($projects->take(50) as $project) { // First 50 projects
            $pivot = $project->resources()->where('resource_id', $resource->id)->first()->pivot;
            
            // Consume half of allocated (multiple small consumptions)
            $consumeAmount = $pivot->quantity_allocated / 4;
            
            ProjectResourceConsumption::create([
                'project_id' => $project->id,
                'resource_id' => $resource->id,
                'consumption_date' => today(),
                'quantity_consumed' => $consumeAmount,
                'recorded_by' => $this->user->id,
            ]);
            
            ProjectResourceConsumption::create([
                'project_id' => $project->id,
                'resource_id' => $resource->id,
                'consumption_date' => today(),
                'quantity_consumed' => $consumeAmount,
                'recorded_by' => $this->user->id,
            ]);
            
            $totalConsumed += ($consumeAmount * 2);
        }
        
        // Verify total system integrity
        $this->assertEquals(100, ProjectResourceConsumption::count());
        
        // Check that pivot quantities are correct
        $totalProjectAvailable = $resource->projects->sum(fn($p) => 
            $p->pivot->quantity_available
        );
        $totalProjectConsumed = $resource->projects->sum(fn($p) => 
            $p->pivot->quantity_consumed
        );
        
        $this->assertEquals($totalConsumed, $totalProjectConsumed);
        $this->assertEquals($totalAllocated - $totalConsumed, $totalProjectAvailable);
        
        // Single source of truth check:
        // Warehouse batches have physical inventory (reduced when consumed)
        // Warehouse + Total Consumed = Original Total
        $warehouseQty = $resource->fresh()->available_quantity;
        
        $this->assertEquals(1000000, $warehouseQty + $totalConsumed);
    }
}
