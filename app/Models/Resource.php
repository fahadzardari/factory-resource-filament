<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    
    // Get total value based on all batches (accurate inventory valuation)
    public function getTotalValueAttribute(): float
    {
        return $this->batches->sum(function ($batch) {
            return $batch->quantity_remaining * $batch->purchase_price;
        });
    }
    
    // Get weighted average price across all batches
    public function getWeightedAveragePriceAttribute(): float
    {
        $totalQuantity = $this->batches->sum('quantity_remaining');
        
        if ($totalQuantity == 0) {
            return 0;
        }
        
        return $this->total_value / $totalQuantity;
    }
    
    // Get total quantity from batches (should match available_quantity)
    public function getTotalQuantityFromBatchesAttribute(): float
    {
        return $this->batches->sum('quantity_remaining');
    }
}

