<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Resource;
use App\Models\ResourceBatch;
use App\Models\Project;
use App\Models\ProjectResourceConsumption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

class TransferSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Transfer physically moves inventory from Central Hub to Project
     * Central Hub quantity should DECREASE
     * Project should have NEW batches with same cost
     */
    public function test_transfer_reduces_central_hub_and_creates_project_batches(): void
    {
        // Setup: Central Hub has 10,000 kg
        $resource = Resource::create([
            'name' => 'Steel',
            'sku' => 'STEEL-001',
            'unit_type' => 'kg',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 100,
        ]);

        ResourceBatch::create([
            'resource_id' => $resource->id,
            'project_id' => null, // Central Hub
            'batch_number' => 'BATCH-001',
            'quantity_purchased' => 10000,
            'quantity_remaining' => 10000,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_price' => 100,
            'purchase_date' => now(),
        ]);

        $resource->refresh();
        $this->assertEquals(10000, $resource->central_hub_quantity);

        // Action: Transfer 4000 kg to Project A
        $projectA = Project::create([
            'name' => 'Factory A',
            'code' => 'FA01',
            'description' => 'Test Factory',
        ]);

        $resource->transferToProject($projectA, 4000, 'Initial transfer');

        // Assert: Central Hub reduced to 6000
        $resource->refresh();
        $this->assertEquals(6000, $resource->central_hub_quantity);

        // Assert: Project A has 4000
        $projectQuantity = $projectA->getResourceQuantity($resource);
        $this->assertEquals(4000, $projectQuantity);

        // Assert: Total in system is still 10000 (single source of truth)
        $centralBatches = $resource->batches()->centralHub()->get();
        $projectBatches = $resource->batches()->forProject($projectA->id)->get();
        
        $totalCentral = $centralBatches->sum(fn($b) => $b->quantity_remaining * $b->conversion_factor);
        $totalProject = $projectBatches->sum(fn($b) => $b->quantity_remaining * $b->conversion_factor);
        
        $this->assertEquals(6000, $totalCentral);
        $this->assertEquals(4000, $totalProject);
        $this->assertEquals(10000, $totalCentral + $totalProject);
    }

    /**
     * Test: Cannot transfer more than Central Hub has
     */
    public function test_transfer_prevents_over_transfer(): void
    {
        $resource = Resource::create([
            'name' => 'Cement',
            'sku' => 'CEM-001',
            'unit_type' => 'kg',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 50,
        ]);

        ResourceBatch::create([
            'resource_id' => $resource->id,
            'project_id' => null,
            'batch_number' => 'BATCH-002',
            'quantity_purchased' => 5000,
            'quantity_remaining' => 5000,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_price' => 50,
            'purchase_date' => now(),
        ]);

        $resource->refresh();

        $project = Project::create([
            'name' => 'Factory B',
            'code' => 'FB01',
            'description' => 'Test',
        ]);

        // Try to transfer 6000 when only 5000 available
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient quantity in Central Hub');
        
        $resource->transferToProject($project, 6000);
    }

    /**
     * Test: Multiple transfers to different projects reduce Central Hub correctly
     */
    public function test_multiple_transfers_maintain_single_source_of_truth(): void
    {
        $resource = Resource::create([
            'name' => 'Sand',
            'sku' => 'SAND-001',
            'unit_type' => 'kg',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 10,
        ]);

        ResourceBatch::create([
            'resource_id' => $resource->id,
            'project_id' => null,
            'batch_number' => 'BATCH-003',
            'quantity_purchased' => 10000,
            'quantity_remaining' => 10000,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_price' => 10,
            'purchase_date' => now(),
        ]);

        $resource->refresh();

        $projectA = Project::create(['name' => 'Project A', 'code' => 'PA', 'description' => 'A']);
        $projectB = Project::create(['name' => 'Project B', 'code' => 'PB', 'description' => 'B']);
        $projectC = Project::create(['name' => 'Project C', 'code' => 'PC', 'description' => 'C']);

        // Transfer 3000 to A
        $resource->transferToProject($projectA, 3000);
        $resource->refresh();
        $this->assertEquals(7000, $resource->central_hub_quantity);
        $this->assertEquals(3000, $projectA->getResourceQuantity($resource));

        // Transfer 4000 to B
        $resource->transferToProject($projectB, 4000);
        $resource->refresh();
        $this->assertEquals(3000, $resource->central_hub_quantity);
        $this->assertEquals(4000, $projectB->getResourceQuantity($resource));

        // Transfer 2000 to C
        $resource->transferToProject($projectC, 2000);
        $resource->refresh();
        $this->assertEquals(1000, $resource->central_hub_quantity);
        $this->assertEquals(2000, $projectC->getResourceQuantity($resource));

        // Try to transfer another 2000 - should fail (only 1000 left)
        $this->expectException(InvalidArgumentException::class);
        $resource->transferToProject($projectC, 2000);
    }

    /**
     * Test: Consumption uses PROJECT's batches, not Central Hub
     */
    public function test_consumption_uses_project_batches_not_central_hub(): void
    {
        $resource = Resource::create([
            'name' => 'Concrete',
            'sku' => 'CON-001',
            'unit_type' => 'kg',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 80,
        ]);

        ResourceBatch::create([
            'resource_id' => $resource->id,
            'project_id' => null,
            'batch_number' => 'BATCH-004',
            'quantity_purchased' => 10000,
            'quantity_remaining' => 10000,
            'unit_type' => 'kg',
            'conversion_factor' => 1.0,
            'purchase_price' => 80,
            'purchase_date' => now(),
        ]);

        $resource->refresh();

        $project = Project::create([
            'name' => 'Factory X',
            'code' => 'FX',
            'description' => 'Test',
        ]);

        // Transfer 5000 to project
        $resource->transferToProject($project, 5000);
        $resource->refresh();
        
        $this->assertEquals(5000, $resource->central_hub_quantity);
        $this->assertEquals(5000, $project->getResourceQuantity($resource));

        // Consume 2000 from project
        ProjectResourceConsumption::create([
            'project_id' => $project->id,
            'resource_id' => $resource->id,
            'consumption_date' => now(),
            'quantity_consumed' => 2000,
            'notes' => 'Daily usage',
        ]);

        // Central Hub should STILL be 5000 (unchanged)
        $resource->refresh();
        $this->assertEquals(5000, $resource->central_hub_quantity);

        // Project should have 3000 remaining
        $this->assertEquals(3000, $project->getResourceQuantity($resource));
    }

    /**
     * Test: Return unused inventory from Project back to Central Hub
     */
    public function test_return_to_hub_moves_inventory_back(): void
    {
        $resource = Resource::create([
            'name' => 'Bricks',
            'sku' => 'BRK-001',
            'unit_type' => 'pcs',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 5,
        ]);

        ResourceBatch::create([
            'resource_id' => $resource->id,
            'project_id' => null,
            'batch_number' => 'BATCH-005',
            'quantity_purchased' => 10000,
            'quantity_remaining' => 10000,
            'unit_type' => 'pcs',
            'conversion_factor' => 1.0,
            'purchase_price' => 5,
            'purchase_date' => now(),
        ]);

        $resource->refresh();

        $project = Project::create([
            'name' => 'Factory Y',
            'code' => 'FY',
            'description' => 'Test',
        ]);

        // Transfer 6000 to project
        $resource->transferToProject($project, 6000);
        $resource->refresh();
        $this->assertEquals(4000, $resource->central_hub_quantity);
        $this->assertEquals(6000, $project->getResourceQuantity($resource));

        // Project uses 2000
        ProjectResourceConsumption::create([
            'project_id' => $project->id,
            'resource_id' => $resource->id,
            'consumption_date' => now(),
            'quantity_consumed' => 2000,
            'notes' => 'Used',
        ]);

        $this->assertEquals(4000, $project->getResourceQuantity($resource));

        // Return remaining 4000 to Central Hub
        $resource->returnToHub($project, 4000, 'Project complete, returning excess');

        // Central Hub should now have 4000 + 4000 = 8000
        $resource->refresh();
        $this->assertEquals(8000, $resource->central_hub_quantity);

        // Project should have 0
        $this->assertEquals(0, $project->getResourceQuantity($resource));
    }

    /**
     * Test: Cannot return more than project has
     */
    public function test_return_validates_project_quantity(): void
    {
        $resource = Resource::create([
            'name' => 'Paint',
            'sku' => 'PNT-001',
            'unit_type' => 'ltr',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 20,
        ]);

        ResourceBatch::create([
            'resource_id' => $resource->id,
            'project_id' => null,
            'batch_number' => 'BATCH-006',
            'quantity_purchased' => 500,
            'quantity_remaining' => 500,
            'unit_type' => 'ltr',
            'conversion_factor' => 1.0,
            'purchase_price' => 20,
            'purchase_date' => now(),
        ]);

        $resource->refresh();

        $project = Project::create([
            'name' => 'Factory Z',
            'code' => 'FZ',
            'description' => 'Test',
        ]);

        $resource->transferToProject($project, 200);
        
        // Try to return 300 when only 200 in project
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient quantity in Project');
        
        $resource->returnToHub($project, 300);
    }

    /**
     * Test: Multi-unit batches work correctly with transfers
     */
    public function test_transfer_handles_multi_unit_batches(): void
    {
        $resource = Resource::create([
            'name' => 'Gravel',
            'sku' => 'GRV-001',
            'unit_type' => 'kg', // Base unit
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 15,
        ]);

        // Purchase 5 tons (stored in tons, but base unit is kg)
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'project_id' => null,
            'batch_number' => 'BATCH-007',
            'quantity_purchased' => 5, // 5 tons
            'quantity_remaining' => 5,
            'unit_type' => 'ton',
            'conversion_factor' => 1000, // 1 ton = 1000 kg
            'purchase_price' => 15000,
            'purchase_date' => now(),
        ]);

        $resource->refresh();
        // Should show 5000 kg in central hub
        $this->assertEquals(5000, $resource->central_hub_quantity);

        $project = Project::create([
            'name' => 'Factory Multi',
            'code' => 'FM',
            'description' => 'Test',
        ]);

        // Transfer 3000 kg (3 tons worth)
        $resource->transferToProject($project, 3000);
        $resource->refresh();

        // Central Hub should have 2000 kg (2 tons)
        $this->assertEquals(2000, $resource->central_hub_quantity);

        // Project should have 3000 kg
        $this->assertEquals(3000, $project->getResourceQuantity($resource));
    }

    /**
     * Test: Complete workflow - purchase, transfer, consume, return
     */
    public function test_complete_workflow_maintains_integrity(): void
    {
        $resource = Resource::create([
            'name' => 'Lumber',
            'sku' => 'LUM-001',
            'unit_type' => 'pcs',
            'total_quantity' => 0,
            'available_quantity' => 0,
            'purchase_price' => 25,
        ]);

        // 1. Purchase 1000 pcs to Central Hub
        ResourceBatch::create([
            'resource_id' => $resource->id,
            'project_id' => null,
            'batch_number' => 'BATCH-008',
            'quantity_purchased' => 1000,
            'quantity_remaining' => 1000,
            'unit_type' => 'pcs',
            'conversion_factor' => 1.0,
            'purchase_price' => 25,
            'purchase_date' => now(),
        ]);

        $resource->refresh();
        $this->assertEquals(1000, $resource->central_hub_quantity);

        $projectA = Project::create(['name' => 'Project Alpha', 'code' => 'PA', 'description' => 'A']);
        $projectB = Project::create(['name' => 'Project Beta', 'code' => 'PB', 'description' => 'B']);

        // 2. Transfer 600 to Project A
        $resource->transferToProject($projectA, 600);
        $resource->refresh();
        $this->assertEquals(400, $resource->central_hub_quantity);
        $this->assertEquals(600, $projectA->getResourceQuantity($resource));

        // 3. Transfer 300 to Project B
        $resource->transferToProject($projectB, 300);
        $resource->refresh();
        $this->assertEquals(100, $resource->central_hub_quantity);
        $this->assertEquals(300, $projectB->getResourceQuantity($resource));

        // 4. Project A consumes 400
        ProjectResourceConsumption::create([
            'project_id' => $projectA->id,
            'resource_id' => $resource->id,
            'consumption_date' => now(),
            'quantity_consumed' => 400,
            'notes' => 'Used',
        ]);
        
        $this->assertEquals(200, $projectA->getResourceQuantity($resource));

        // 5. Project B consumes 100
        ProjectResourceConsumption::create([
            'project_id' => $projectB->id,
            'resource_id' => $resource->id,
            'consumption_date' => now(),
            'quantity_consumed' => 100,
            'notes' => 'Used',
        ]);
        
        $this->assertEquals(200, $projectB->getResourceQuantity($resource));

        // 6. Return unused from both projects
        $resource->returnToHub($projectA, 200);
        $resource->returnToHub($projectB, 200);
        
        $resource->refresh();
        
        // Central Hub should have 100 + 200 + 200 = 500
        $this->assertEquals(500, $resource->central_hub_quantity);
        
        // Projects should have 0
        $this->assertEquals(0, $projectA->getResourceQuantity($resource));
        $this->assertEquals(0, $projectB->getResourceQuantity($resource));

        // Total consumed = 400 + 100 = 500
        // Total in system = 500 (in hub) + 0 (projects) = 500
        // Original = 1000, Consumed = 500, Remaining = 500 ✓
    }
}
