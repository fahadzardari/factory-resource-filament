<?php

namespace App\Console\Commands;

use App\Models\Resource;
use Illuminate\Console\Command;

class SyncResourceQuantities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resources:sync-quantities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all resource quantities from their batches (single source of truth)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing resource quantities from batches...');
        
        $resources = Resource::with('batches')->get();
        $bar = $this->output->createProgressBar($resources->count());
        $bar->start();
        
        $synced = 0;
        $errors = 0;
        
        foreach ($resources as $resource) {
            try {
                $oldQty = $resource->available_quantity;
                $resource->syncQuantityFromBatches();
                $newQty = $resource->fresh()->available_quantity;
                
                if ($oldQty != $newQty) {
                    $synced++;
                }
                
                $bar->advance();
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError syncing {$resource->name}: " . $e->getMessage());
            }
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Sync complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Resources', $resources->count()],
                ['Updated', $synced],
                ['Already Synced', $resources->count() - $synced - $errors],
                ['Errors', $errors],
            ]
        );
        
        return Command::SUCCESS;
    }
}
