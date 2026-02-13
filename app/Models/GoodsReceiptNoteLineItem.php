<?php

namespace App\Models;

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
     * Get unit conversion factor
     */
    public function getConversionFactor(string $fromUnit, string $toUnit): float
    {
        if ($fromUnit === $toUnit) {
            return 1.0;
        }

        // Weight conversions
        $weightConversions = [
            'mg' => 0.000001, 'g' => 0.001, 'kg' => 1, 'ton' => 1000,
            'oz' => 0.0283495, 'lb' => 0.453592,
        ];

        // Volume conversions (to liter)
        $volumeConversions = [
            'ml' => 0.001, 'liter' => 1, 'liters' => 1, 'gallon' => 3.78541, 'm3' => 1000,
        ];

        // Length conversions (to meter)
        $lengthConversions = [
            'mm' => 0.001, 'cm' => 0.01, 'm' => 1, 'km' => 1000, 'ft' => 0.3048, 'inch' => 0.0254,
        ];

        // Area conversions (to sqm)
        $areaConversions = [
            'sqcm' => 0.0001, 'sqm' => 1, 'sqft' => 0.092903,
        ];

        // Try to find conversion factors in the respective unit groups
        if (isset($weightConversions[$fromUnit]) && isset($weightConversions[$toUnit])) {
            return $weightConversions[$fromUnit] / $weightConversions[$toUnit];
        }
        if (isset($volumeConversions[$fromUnit]) && isset($volumeConversions[$toUnit])) {
            return $volumeConversions[$fromUnit] / $volumeConversions[$toUnit];
        }
        if (isset($lengthConversions[$fromUnit]) && isset($lengthConversions[$toUnit])) {
            return $lengthConversions[$fromUnit] / $lengthConversions[$toUnit];
        }
        if (isset($areaConversions[$fromUnit]) && isset($areaConversions[$toUnit])) {
            return $areaConversions[$fromUnit] / $areaConversions[$toUnit];
        }

        // Default: 1 (no conversion needed)
        return 1.0;
    }
}
