<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceBatch extends Model
{
    protected $fillable = [
        'resource_id',
        'batch_number',
        'purchase_price',
        'quantity_purchased',
        'quantity_remaining',
        'purchase_date',
        'supplier',
        'notes',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'quantity_purchased' => 'decimal:2',
        'quantity_remaining' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
    
    public function getTotalValueAttribute(): float
    {
        return $this->quantity_remaining * $this->purchase_price;
    }
    
    public function getQuantityUsedAttribute(): float
    {
        return $this->quantity_purchased - $this->quantity_remaining;
    }
}
