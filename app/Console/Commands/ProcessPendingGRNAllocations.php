<?php

namespace App\Console\Commands;

use App\Models\GoodsReceiptNote;
use App\Models\InventoryTransaction;
use App\Models\User;
use App\Services\InventoryTransactionService;
use Illuminate\Console\Command;

class ProcessPendingGRNAllocations extends Command
{
    protected $signature = 'grn:process-allocations {--grn-number=}';
    protected $description = 'Process pending GRN allocations - creates inventory transactions for GRNs without them';

    public function handle()
    {
        $service = app(InventoryTransactionService::class);
        $user = User::first();

        if (!$user) {
            $this->error('No user found in database!');
            return;
        }

        // Find GRNs without transactions
        $query = GoodsReceiptNote::whereDoesntHave('inventoryTransactions');
        
        if ($this->option('grn-number')) {
            $query->where('grn_number', $this->option('grn-number'));
        }

        $grns = $query->get();

        if ($grns->isEmpty()) {
            $this->info('No pending GRN allocations found!');
            return;
        }

        $this->info("Found " . $grns->count() . " GRNs without transactions\n");

        foreach ($grns as $grn) {
            try {
                $this->line("Processing {$grn->grn_number}...");
                
                // Create the transactions
                $transactions = $service->recordGoodsReceipt($grn, $user);
                
                $this->info("✅ Created " . count($transactions) . " transactions for {$grn->grn_number}");

                // Show what was allocated
                foreach ($transactions as $t) {
                    $destination = $t->project_id ? "Project {$t->project_id}" : "Hub";
                    $this->line("   • {$t->transaction_type}: {$t->resource->name} ({$t->quantity} {$t->resource->base_unit}) → {$destination}");
                }
                
                // If allocated to project, show the current inventory
                if ($grn->project_id) {
                    $stocks = \App\Services\StockCalculator::getProjectResourceStocks($grn->project_id);
                    $this->info("   Project Inventory:");
                    foreach ($stocks as $stock) {
                        $this->line("     • {$stock['resource_name']}: {$stock['quantity']} {$stock['unit']}");
                    }
                }

                $this->newLine();
            } catch (\Exception $e) {
                $this->error("❌ Failed to process {$grn->grn_number}: " . $e->getMessage());
            }
        }

        $this->info("✅ All pending allocations processed!");
    }
}
