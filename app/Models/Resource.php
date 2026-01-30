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
     * Synchronize available_quantity with actual batch quantities
     * This ensures the single source of truth
     * Applies conversion factors to convert all batches to the resource's base unit
     */
    public function syncQuantityFromBatches(): self
    {
        // We must get() the batches and sum with conversion factor
        // Cannot use sum() on query builder because it doesn't apply conversion_factor
        $this->available_quantity = $this->batches()
            ->get()
            ->sum(fn($batch) => $batch->quantity_remaining * $batch->conversion_factor);
        
        $this->saveQuietly();
        return $this;
    }

    /**
     * Check if the resource has sufficient quantity available
     */
    public function hasSufficientQuantity(float $requiredQuantity): bool
    {
        return $this->available_quantity >= $requiredQuantity;
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
     * Get total quantity allocated to projects
     */
    public function allocatedQuantity(): float
    {
        return (float) $this->projects()->sum('quantity_allocated');
    }

    /**
     * Get quantity available for allocation (not yet assigned to projects)
     */
    public function availableForAllocation(): float
    {
        return max(0, $this->available_quantity - $this->allocatedQuantity());
    }

    /**
     * Allocate resources to a project
     * This TRANSFERS inventory from warehouse to project without consuming from batches yet
     * Batches are only consumed when the project actually uses the resources
     */
    public function allocateToProject(Project $project, float $quantity, ?string $notes = null): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Allocation quantity must be positive");
        }

        if ($this->availableForAllocation() < $quantity) {
            throw new InvalidArgumentException(
                "Insufficient quantity for allocation. Available: {$this->availableForAllocation()}, Requested: {$quantity}"
            );
        }

        // Create transfer record (from warehouse to project)
        ResourceTransfer::create([
            'resource_id' => $this->id,
            'from_project_id' => null, // From warehouse
            'to_project_id' => $project->id,
            'quantity' => $quantity,
            'transfer_type' => 'warehouse_to_project',
            'notes' => $notes,
            'transferred_by' => auth()->id(),
            'transferred_at' => now(),
        ]);

        // Update or create pivot record
        $existing = $project->resources()->where('resource_id', $this->id)->first();
        
        if ($existing) {
            $project->resources()->updateExistingPivot($this->id, [
                'quantity_allocated' => $existing->pivot->quantity_allocated + $quantity,
                'quantity_available' => $existing->pivot->quantity_available + $quantity,
            ]);
        } else {
            $project->resources()->attach($this->id, [
                'quantity_allocated' => $quantity,
                'quantity_consumed' => 0,
                'quantity_available' => $quantity,
                'notes' => $notes,
            ]);
        }
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

