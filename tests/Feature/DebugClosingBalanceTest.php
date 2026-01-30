<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Resource;
use App\Models\Project;
use App\Models\InventoryTransaction;
use App\Services\InventoryTransactionService;
use App\Services\StockCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebugClosingBalanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_closing_balance()
    {
        $resource = Resource::factory()->create(['base_unit' => 'kg']);
        $project = Project::factory()->create();
        
        $service = new InventoryTransactionService();
        $calc = new StockCalculator();

        // Day 1: Purchase
        $service->recordPurchase($resource, 1000, 10, '2026-01-30');
        
        // Day 2: More purchases and allocation
        $service->recordPurchase($resource, 500, 12, '2026-01-31');
        $service->recordAllocation($resource, $project, 300, '2026-01-31');

        // Check all hub transactions
        $hubTxns = InventoryTransaction::where('resource_id', $resource->id)
            ->whereNull('project_id')
            ->orderBy('transaction_date')
            ->get();

        dump('All hub transactions:');
        foreach ($hubTxns as $txn) {
            dump([
                'type' => $txn->transaction_type,
                'qty' => $txn->quantity,
                'date' => $txn->transaction_date->format('Y-m-d'),
            ]);
        }

        $opening = $calc->getOpeningBalance($resource, '2026-01-31', null);
        
        // Get ALL hub transactions to see what dates they have
        $allTxns = InventoryTransaction::where('resource_id', $resource->id)
            ->whereNull('project_id')
            ->get();
            
        dump('ALL hub transactions:');
        foreach ($allTxns as $txn) {
            dump([
                'type' => $txn->transaction_type,
                'qty' => $txn->quantity,
                'date' => $txn->transaction_date->format('Y-m-d'),
                'date_raw' => $txn->getAttributes()['transaction_date'], // Raw DB value
            ]);
        }
        
        // Now with filter
        $filtered = InventoryTransaction::where('resource_id', $resource->id)
            ->whereNull('project_id')
            ->where('transaction_date', '<=', '2026-01-31')
            ->get();
            
        dump('Filtered transactions (date <= 2026-01-31):');
        dump('Count: ' . $filtered->count());
        dump('Sum: ' . $filtered->sum('quantity'));
        
        $closing = $calc->getClosingBalance($resource, '2026-01-31', null);

        dump("Opening Jan 31: $opening (expected 1000)");
        dump("Closing Jan 31: $closing (expected 1200)");

        $this->assertEquals(1000, $opening);
        $this->assertEquals(1200, $closing);
    }
}
