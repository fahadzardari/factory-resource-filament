<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourcePriceHistory extends Model
{
    protected $fillable = [
        'resource_id',
        'price',
        'supplier',
        'purchase_date',
        'quantity_purchased',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity_purchased' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
