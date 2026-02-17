<?php

namespace App\Models;

use App\Services\UnitConversionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptNoteLineItem extends Model
{
    use HasFactory;

    protected $table = 'goods_receipt_note_line_items';

    protected $fillable = [
        'grn_id',
        'resource_id',
        'quantity_received',
        'receipt_unit',
        'unit_price',
        'total_value',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    /**
     * Boot method - Auto-calculate total_value
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-calculate total value
            if ($model->quantity_received && $model->unit_price !== null) {
                $model->total_value = $model->quantity_received * $model->unit_price;
            }
        });

        static::updating(function ($model) {
            // Auto-calculate total value on update too
            if ($model->isDirty(['quantity_received', 'unit_price'])) {
                $model->total_value = $model->quantity_received * $model->unit_price;
            }
        });
    }

    /**
     * Relationships
     */
    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'grn_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Get base quantity (converted from receipt unit to base unit)
     */
    public function getBaseQuantity(): float
    {
        $resource = $this->resource;
        if (!$resource) {
            return 0;
        }

        // Get conversion factor from receipt unit to base unit
        $conversionFactor = $this->getConversionFactor($this->receipt_unit, $resource->base_unit);
        return $this->quantity_received * $conversionFactor;
    }

    /**
     * Get unit conversion factor using database-driven service
     */
    public function getConversionFactor(string $fromUnit, string $toUnit): float
    {
        $service = new UnitConversionService();
        $factor = $service->getConversionFactor($fromUnit, $toUnit);
        return $factor ?? 1.0;
    }
}
