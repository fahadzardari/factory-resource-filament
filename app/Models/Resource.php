<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Resource extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'name',
        'sku',
        'category',
        'unit_type',
        'purchase_price',
        'total_quantity',
        'available_quantity',
        'description',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'total_quantity' => 'decimal:2',
        'available_quantity' => 'decimal:2',
    ];

    /**
     * Boot method to add validation
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Ensure quantities are never negative
            if ($model->total_quantity < 0) {
                throw new InvalidArgumentException('Total quantity cannot be negative. Current value: ' . $model->total_quantity);
            }
            if ($model->available_quantity < 0) {
                throw new InvalidArgumentException('Available quantity cannot be negative. Current value: ' . $model->available_quantity);
            }
            if ($model->purchase_price < 0) {
                throw new InvalidArgumentException('Purchase price cannot be negative.');
            }
        });
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot(['quantity_allocated', 'quantity_consumed', 'quantity_available', 'notes'])
            ->withTimestamps();
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ResourcePriceHistory::class)->orderBy('purchase_date', 'desc');
    }
    
    public function batches(): HasMany
    {
        return $this->hasMany(ResourceBatch::class)->orderBy('purchase_date', 'asc');
    }

    /**
     * Get active batches (with remaining quantity > 0)
     */
    public function activeBatches(): HasMany
    {
        return $this->hasMany(ResourceBatch::class)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('purchase_date', 'asc');
    }
    
    /**
     * Get total value based on all batches (accurate inventory valuation)
     */
    public function getTotalValueAttribute(): float
    {
        return $this->batches->sum(function ($batch) {
            return $batch->quantity_remaining * $batch->purchase_price;
        });
    }
    
    /**
     * Get weighted average price across all batches
     */
    public function getWeightedAveragePriceAttribute(): float
    {
        $totalQuantity = $this->batches->sum('quantity_remaining');
        
        if ($totalQuantity == 0) {
            return 0;
        }
        
        return $this->total_value / $totalQuantity;
    }
    
    /**
     * Get total quantity from batches (should match available_quantity)
     */
    public function getTotalQuantityFromBatchesAttribute(): float
    {
        return $this->batches->sum('quantity_remaining');
    }

    /**
     * Get all unique units used across batches
     */
    public function getUnitsUsedAttribute(): array
    {
        return $this->batches->pluck('unit_type')->unique()->values()->toArray();
    }

    /**
     * Synchronize available_quantity with CENTRAL HUB batch quantities ONLY
     * This ensures the single source of truth
     * Applies conversion factors to convert all batches to the resource's base unit
     * 
     * IMPORTANT: Only counts batches in Central Hub (project_id = NULL)
     * Batches transferred to projects are NOT counted here
     */
    public function syncQuantityFromBatches(): self
    {
        // Only sum batches in Central Hub (not transferred to projects)
        $this->available_quantity = $this->batches()
            ->centralHub() // Only batches with project_id = NULL
            ->get()
            ->sum(fn($batch) => $batch->quantity_remaining * $batch->conversion_factor);
        
        $this->saveQuietly();
        return $this;
    }

    /**
     * Check if the Central Hub has sufficient quantity available for transfer
     */
    public function hasSufficientQuantity(float $requiredQuantity): bool
    {
        return $this->available_quantity >= $requiredQuantity;
    }
    
    /**
     * Get total quantity in Central Hub (same as available_quantity but more semantic)
     */
    public function getCentralHubQuantityAttribute(): float
    {
        return $this->available_quantity;
    }

    /**
     * Consume quantity using FIFO (First In, First Out) method
     * Returns the total cost of consumed items
     */
    public function consumeQuantityFifo(float $quantity): float
    {
        // Refresh to get latest batch data
        $this->refresh();
        
        if (!$this->hasSufficientQuantity($quantity)) {
            throw new InvalidArgumentException(
                "Insufficient quantity. Required: {$quantity}, Available: {$this->available_quantity}"
            );
        }

        $remainingToConsume = $quantity;
        $totalCost = 0;

        // Get batches in FIFO order (oldest first)
        $batches = $this->activeBatches()->get();

        foreach ($batches as $batch) {
            if ($remainingToConsume <= 0) {
                break;
            }

            // Convert batch quantity to base unit for comparison
            $batchQuantityInBaseUnit = $batch->quantity_remaining * $batch->conversion_factor;
            $consumeInBaseUnit = min($batchQuantityInBaseUnit, $remainingToConsume);
            
            // Convert back to batch's unit for actual consumption
            $consumeInBatchUnit = $consumeInBaseUnit / $batch->conversion_factor;
            
            // Cost is based on the batch's unit price
            $totalCost += $consumeInBatchUnit * $batch->purchase_price;
            
            $batch->quantity_remaining -= $consumeInBatchUnit;
            $batch->save();
            
            $remainingToConsume -= $consumeInBaseUnit;
        }

        // Sync the resource quantity
        $this->syncQuantityFromBatches();

        return $totalCost;
    }

    /**
     * TRANSFER resources from Central Hub to a Project
     * 
     * This is a PHYSICAL MOVE - inventory is REMOVED from Central Hub and CREATED in Project
     * NOT a copy - follows single source of truth principle
     * 
     * Process:
     * 1. Validate sufficient quantity in Central Hub
     * 2. Consume from Central Hub batches (FIFO) - reduces central hub inventory
     * 3. Create new batch(es) in the project with same cost basis
     * 4. Record transfer transaction
     * 
     * @param Project $project The destination project
     * @param float $quantity Quantity to transfer (in resource's base unit)
     * @param string|null $notes Optional transfer notes
     * @throws InvalidArgumentException if insufficient quantity
     */
    public function transferToProject(Project $project, float $quantity, ?string $notes = null): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Transfer quantity must be positive");
        }

        // Refresh to get latest central hub data
        $this->refresh();
        
        if ($this->central_hub_quantity < $quantity) {
            throw new InvalidArgumentException(
                "Insufficient quantity in Central Hub. Available: {$this->central_hub_quantity}, Requested: {$quantity}"
            );
        }

        // Step 1: Consume from Central Hub batches using FIFO
        // This REDUCES central hub inventory and tracks the cost
        $batches = $this->batches()
            ->centralHub() // Only central hub batches
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->get();

        $remainingToTransfer = $quantity;
        $transferredBatches = []; // Track what we're transferring
        
        foreach ($batches as $batch) {
            if ($remainingToTransfer <= 0) {
                break;
            }

            // Convert batch quantity to base unit for comparison
            $batchAvailableInBaseUnit = $batch->quantity_remaining * $batch->conversion_factor;
            
            if ($batchAvailableInBaseUnit <= 0) {
                continue;
            }

            // Calculate how much to take from this batch (in base unit)
            $takeFromBatch = min($remainingToTransfer, $batchAvailableInBaseUnit);
            
            // Convert back to batch's unit for updating the batch
            $takeInBatchUnit = $takeFromBatch / $batch->conversion_factor;
            
            // Record what we're transferring
            $transferredBatches[] = [
                'unit_type' => $batch->unit_type,
                'conversion_factor' => $batch->conversion_factor,
                'purchase_price' => $batch->purchase_price,
                'quantity_in_batch_unit' => $takeInBatchUnit,
                'quantity_in_base_unit' => $takeFromBatch,
                'purchase_date' => $batch->purchase_date,
                'supplier' => $batch->supplier,
            ];
            
            // Reduce central hub batch
            $batch->quantity_remaining -= $takeInBatchUnit;
            $batch->save();
            
            $remainingToTransfer -= $takeFromBatch;
        }

        // Step 2: Create corresponding batches in the project
        foreach ($transferredBatches as $transferData) {
            ResourceBatch::create([
                'resource_id' => $this->id,
                'project_id' => $project->id, // This marks it as belonging to the project
                'batch_number' => 'PROJ-' . $project->code . '-' . now()->format('YmdHis') . '-' . substr(md5(uniqid()), 0, 6),
                'unit_type' => $transferData['unit_type'],
                'conversion_factor' => $transferData['conversion_factor'],
                'purchase_price' => $transferData['purchase_price'],
                'quantity_purchased' => $transferData['quantity_in_batch_unit'],
                'quantity_remaining' => $transferData['quantity_in_batch_unit'],
                'purchase_date' => $transferData['purchase_date'],
                'supplier' => $transferData['supplier'],
                'notes' => "Transferred from Central Hub to {$project->name}",
            ]);
        }

        // Step 3: Create transfer record for audit trail
        ResourceTransfer::create([
            'resource_id' => $this->id,
            'from_project_id' => null, // From Central Hub
            'to_project_id' => $project->id,
            'quantity' => $quantity,
            'transfer_type' => 'warehouse_to_project',
            'notes' => $notes,
            'transferred_by' => auth()->id(),
            'transferred_at' => now(),
        ]);

        // Sync quantities (central hub will be reduced automatically)
        $this->syncQuantityFromBatches();
    }

    /**
     * RETURN unused resources from Project back to Central Hub
     * 
     * Used when a project ends or has excess inventory to return
     * 
     * Process:
     * 1. Validate project has sufficient quantity
     * 2. Move batches from project back to Central Hub
     * 3. Record return transaction
     * 
     * @param Project $project The source project
     * @param float $quantity Quantity to return (in resource's base unit)
     * @param string|null $notes Optional return notes
     * @throws InvalidArgumentException if insufficient quantity in project
     */
    public function returnToHub(Project $project, float $quantity, ?string $notes = null): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Return quantity must be positive");
        }

        // Get project's batches for this resource
        $projectBatches = $this->batches()
            ->forProject($project->id)
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->get();
            
        // Calculate available quantity in project
        $projectQuantity = $projectBatches->sum(fn($batch) => $batch->quantity_remaining * $batch->conversion_factor);
        
        if ($projectQuantity < $quantity) {
            throw new InvalidArgumentException(
                "Insufficient quantity in Project. Available: {$projectQuantity}, Requested: {$quantity}"
            );
        }

        $remainingToReturn = $quantity;
        $returnedBatches = [];
        
        // Process batches FIFO
        foreach ($projectBatches as $batch) {
            if ($remainingToReturn <= 0) {
                break;
            }

            $batchAvailableInBaseUnit = $batch->quantity_remaining * $batch->conversion_factor;
            
            if ($batchAvailableInBaseUnit <= 0) {
                continue;
            }

            $takeFromBatch = min($remainingToReturn, $batchAvailableInBaseUnit);
            $takeInBatchUnit = $takeFromBatch / $batch->conversion_factor;
            
            $returnedBatches[] = [
                'unit_type' => $batch->unit_type,
                'conversion_factor' => $batch->conversion_factor,
                'purchase_price' => $batch->purchase_price,
                'quantity_in_batch_unit' => $takeInBatchUnit,
                'quantity_in_base_unit' => $takeFromBatch,
                'purchase_date' => $batch->purchase_date,
                'supplier' => $batch->supplier,
            ];
            
            // Reduce or delete project batch
            if ($batch->quantity_remaining <= $takeInBatchUnit + 0.001) {
                // Entire batch is being returned
                $batch->delete();
            } else {
                $batch->quantity_remaining -= $takeInBatchUnit;
                $batch->save();
            }
            
            $remainingToReturn -= $takeFromBatch;
        }

        // Create corresponding batches in Central Hub
        foreach ($returnedBatches as $returnData) {
            ResourceBatch::create([
                'resource_id' => $this->id,
                'project_id' => null, // NULL = Central Hub
                'batch_number' => 'RETURN-' . $project->code . '-' . now()->format('YmdHis') . '-' . substr(md5(uniqid()), 0, 6),
                'unit_type' => $returnData['unit_type'],
                'conversion_factor' => $returnData['conversion_factor'],
                'purchase_price' => $returnData['purchase_price'],
                'quantity_purchased' => $returnData['quantity_in_batch_unit'],
                'quantity_remaining' => $returnData['quantity_in_batch_unit'],
                'purchase_date' => $returnData['purchase_date'],
                'supplier' => $returnData['supplier'],
                'notes' => "Returned from {$project->name} to Central Hub",
            ]);
        }

        // Create transfer record
        ResourceTransfer::create([
            'resource_id' => $this->id,
            'from_project_id' => $project->id,
            'to_project_id' => null, // To Central Hub
            'quantity' => $quantity,
            'transfer_type' => 'project_to_warehouse',
            'notes' => $notes,
            'transferred_by' => auth()->id(),
            'transferred_at' => now(),
        ]);

        // Sync quantities
        $this->syncQuantityFromBatches();
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sku', 'category', 'available_quantity', 'purchase_price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Resource {$eventName}")
            ->useLogName('resource');
    }
}

