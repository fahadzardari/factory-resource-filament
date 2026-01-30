<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Resource extends Model
{
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
     */
    public function syncQuantityFromBatches(): self
    {
        $this->available_quantity = $this->batches()->sum('quantity_remaining');
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

            $consumeFromBatch = min($batch->quantity_remaining, $remainingToConsume);
            $totalCost += $consumeFromBatch * $batch->purchase_price;
            
            $batch->quantity_remaining -= $consumeFromBatch;
            $batch->save();
            
            $remainingToConsume -= $consumeFromBatch;
        }

        // Sync the resource quantity
        $this->syncQuantityFromBatches();

        return $totalCost;
    }
}

