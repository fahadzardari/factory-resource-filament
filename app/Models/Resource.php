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
}
