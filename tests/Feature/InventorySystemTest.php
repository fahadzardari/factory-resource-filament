<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Resource;
use App\Models\Project;
use App\Models\User;
use App\Models\InventoryTransaction;
use App\Services\InventoryTransactionService;
use App\Services\StockCalculator;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

class InventorySystemTest extends TestCase
{
    use RefreshDatabase;

    private InventoryTransactionService $inventoryService;
    private StockCalculator $stockCalculator;
    private ReportingService $reportingService;
    private Resource $resource;
    private Project $projectA;
    private Project $projectB;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryService = new InventoryTransactionService();
        $this->stockCalculator = new StockCalculator();
        $this->reportingService = new ReportingService($this->stockCalculator);

        // Create test data
        $this->user = User::factory()->create();
        $this->resource = Resource::factory()->create([
            'name' => 'Cement',
            'sku' => 'CEM-001',
            'base_unit' => 'kg',
        ]);
        $this->projectA = Project::factory()->create(['name' => 'Factory A']);
        $this->projectB = Project::factory()->create(['name' => 'Factory B']);
    }

    /** @test */
    public function it_can_record_a_purchase()
    {
        $transaction = $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10.50,
            transactionDate: '2026-01-30',
            supplier: 'ABC Suppliers',
            invoiceNumber: 'INV-001',
            user: $this->user
        );

        $this->assertDatabaseHas('inventory_transactions', [
            'resource_id' => $this->resource->id,
            'project_id' => null, // Hub
            'transaction_type' => InventoryTransaction::TYPE_PURCHASE,
            'quantity' => 1000,
            'unit_price' => 10.50,
            'total_value' => 10500.00,
        ]);

        // Verify hub stock
        $hubStock = $this->inventoryService->getCurrentStock($this->resource, null);
        $this->assertEquals(1000, $hubStock);
    }

    /** @test */
    public function it_prevents_negative_purchase_quantity()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Purchase quantity must be positive');

        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: -100,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );
    }

    /** @test */
    public function it_can_allocate_from_hub_to_project()
    {
        // First purchase stock
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        // Then allocate to project
        [$outTxn, $inTxn] = $this->inventoryService->recordAllocation(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 300,
            transactionDate: '2026-01-30'
        );

        // Verify hub decreased
        $hubStock = $this->inventoryService->getCurrentStock($this->resource, null);
        $this->assertEquals(700, $hubStock);

        // Verify project increased
        $projectStock = $this->inventoryService->getCurrentStock($this->resource, $this->projectA->id);
        $this->assertEquals(300, $projectStock);

        // Verify transactions are balanced
        $this->assertEquals(-300, $outTxn->quantity);
        $this->assertEquals(300, $inTxn->quantity);
    }

    /** @test */
    public function it_prevents_allocation_exceeding_hub_stock()
    {
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 100,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock in Central Hub');

        $this->inventoryService->recordAllocation(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 150, // More than available
            transactionDate: '2026-01-30'
        );
    }

    /** @test */
    public function it_can_record_consumption_at_project()
    {
        // Setup: Purchase and allocate
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        $this->inventoryService->recordAllocation(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 500,
            transactionDate: '2026-01-30'
        );

        // Record consumption
        $transaction = $this->inventoryService->recordConsumption(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 200,
            transactionDate: '2026-01-31',
            notes: 'Used for foundation'
        );

        // Verify project stock decreased
        $projectStock = $this->inventoryService->getCurrentStock($this->resource, $this->projectA->id);
        $this->assertEquals(300, $projectStock);

        // Verify transaction recorded
        $this->assertEquals(-200, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::TYPE_CONSUMPTION, $transaction->transaction_type);
    }

    /** @test */
    public function it_prevents_consumption_exceeding_project_stock()
    {
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        $this->inventoryService->recordAllocation(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 100,
            transactionDate: '2026-01-30'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock at project');

        $this->inventoryService->recordConsumption(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 150, // More than available
            transactionDate: '2026-01-31'
        );
    }

    /** @test */
    public function it_can_transfer_between_projects()
    {
        // Setup
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        $this->inventoryService->recordAllocation(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 500,
            transactionDate: '2026-01-30'
        );

        // Transfer from Project A to Project B
        [$outTxn, $inTxn] = $this->inventoryService->recordTransfer(
            resource: $this->resource,
            fromProject: $this->projectA,
            toProject: $this->projectB,
            quantity: 200,
            transactionDate: '2026-01-31'
        );

        // Verify Project A decreased
        $projectAStock = $this->inventoryService->getCurrentStock($this->resource, $this->projectA->id);
        $this->assertEquals(300, $projectAStock);

        // Verify Project B increased
        $projectBStock = $this->inventoryService->getCurrentStock($this->resource, $this->projectB->id);
        $this->assertEquals(200, $projectBStock);
    }

    /** @test */
    public function it_prevents_transfer_to_same_project()
    {
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        $this->inventoryService->recordAllocation(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 500,
            transactionDate: '2026-01-30'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transfer to the same project');

        $this->inventoryService->recordTransfer(
            resource: $this->resource,
            fromProject: $this->projectA,
            toProject: $this->projectA,
            quantity: 100,
            transactionDate: '2026-01-31'
        );
    }

    /** @test */
    public function it_calculates_opening_and_closing_balances_correctly()
    {
        // Day 1: Purchase
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        // Day 2: More purchases and allocation
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 500,
            unitPrice: 12,
            transactionDate: '2026-01-31'
        );

        $this->inventoryService->recordAllocation(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 300,
            transactionDate: '2026-01-31'
        );

        // Opening balance on Jan 31 should be 1000 (from Jan 30)
        $opening = $this->stockCalculator->getOpeningBalance($this->resource, '2026-01-31', null);
        $this->assertEquals(1000, $opening);

        // Closing balance on Jan 31 should be 1000 + 500 - 300 = 1200
        $closing = $this->stockCalculator->getClosingBalance($this->resource, '2026-01-31', null);
        $this->assertEquals(1200, $closing);
    }

    /** @test */
    public function it_generates_daily_report_correctly()
    {
        // Setup transactions for Jan 30
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        $this->inventoryService->recordAllocation(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 300,
            transactionDate: '2026-01-30'
        );

        // Generate report
        $report = $this->stockCalculator->getDailyReport($this->resource, '2026-01-30', null);

        $this->assertEquals(0, $report['opening_balance']); // No stock before this day
        $this->assertEquals(1000, $report['total_in']); // Purchase
        $this->assertEquals(300, $report['total_out']); // Allocation out
        $this->assertEquals(700, $report['closing_balance']); // 0 + 1000 - 300
    }

    /** @test */
    public function it_calculates_weighted_average_price_correctly()
    {
        // Purchase at different prices
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 100,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 50,
            unitPrice: 12,
            transactionDate: '2026-01-30'
        );

        // Total value = (100 * 10) + (50 * 12) = 1000 + 600 = 1600
        // Total quantity = 150
        // Weighted average = 1600 / 150 = 10.67

        $avgPrice = $this->inventoryService->getWeightedAveragePrice($this->resource, null);
        $this->assertEquals(10.67, round($avgPrice, 2));
    }

    /** @test */
    public function it_prevents_modification_of_existing_transactions()
    {
        $transaction = $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inventory transactions cannot be modified');

        $transaction->quantity = 500;
        $transaction->save();
    }

    /** @test */
    public function it_prevents_deletion_of_transactions()
    {
        $transaction = $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10,
            transactionDate: '2026-01-30'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inventory transactions cannot be deleted');

        $transaction->delete();
    }

    /** @test */
    public function it_supports_historical_queries()
    {
        // Create transactions across multiple days
        $this->inventoryService->recordPurchase(
            resource: $this->resource,
            quantity: 1000,
            unitPrice: 10,
            transactionDate: '2026-01-28'
        );

        $this->inventoryService->recordAllocation(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 200,
            transactionDate: '2026-01-29'
        );

        $this->inventoryService->recordAllocation(
            resource: $this->resource,
            project: $this->projectA,
            quantity: 300,
            transactionDate: '2026-01-30'
        );

        // Query stock as of Jan 29
        $stockOnJan29 = $this->stockCalculator->getClosingBalance($this->resource, '2026-01-29', null);
        $this->assertEquals(800, $stockOnJan29); // 1000 - 200

        // Query stock as of Jan 30
        $stockOnJan30 = $this->stockCalculator->getClosingBalance($this->resource, '2026-01-30', null);
        $this->assertEquals(500, $stockOnJan30); // 1000 - 200 - 300
    }
}
