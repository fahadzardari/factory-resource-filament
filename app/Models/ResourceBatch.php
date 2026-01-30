<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ResourceBatch extends Model
{
    protected $fillable = [
        'resource_id',
        'batch_number',
        'unit_type',
        'conversion_factor',
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
        'conversion_factor' => 'decimal:6',
        'purchase_date' => 'date',
    ];

    /**
     * Common unit types available for batches
     */
    public const UNIT_TYPES = [
        // Weight
        'kg' => 'Kilograms (kg)',
        'g' => 'Grams (g)',
        'ton' => 'Metric Tons',
        'lb' => 'Pounds (lb)',
        'oz' => 'Ounces (oz)',
        
        // Volume
        'liter' => 'Liters (L)',
        'ml' => 'Milliliters (mL)',
        'gallon' => 'Gallons',
        'cubic_m' => 'Cubic Meters (m³)',
        'cubic_ft' => 'Cubic Feet (ft³)',
        
        // Length
        'meter' => 'Meters (m)',
        'cm' => 'Centimeters (cm)',
        'mm' => 'Millimeters (mm)',
        'inch' => 'Inches (in)',
        'ft' => 'Feet (ft)',
        'yard' => 'Yards (yd)',
        
        // Area
        'sq_m' => 'Square Meters (m²)',
        'sq_ft' => 'Square Feet (ft²)',
        
        // Count
        'piece' => 'Pieces (pcs)',
        'box' => 'Boxes',
        'carton' => 'Cartons',
        'bundle' => 'Bundles',
        'roll' => 'Rolls',
        'sheet' => 'Sheets',
        'set' => 'Sets',
        'pair' => 'Pairs',
        'dozen' => 'Dozens',
    ];

    /**
     * Boot method to add validation
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Ensure quantities are never negative
            if ($model->quantity_purchased < 0) {
                throw new InvalidArgumentException('Quantity purchased cannot be negative.');
            }
            if ($model->quantity_remaining < 0) {
                throw new InvalidArgumentException('Quantity remaining cannot be negative. You cannot consume more than available.');
            }
            if ($model->quantity_remaining > $model->quantity_purchased) {
                throw new InvalidArgumentException('Quantity remaining cannot exceed quantity purchased.');
            }
            if ($model->purchase_price < 0) {
                throw new InvalidArgumentException('Purchase price cannot be negative.');
            }
        });

        // After creating or updating a batch, sync the parent resource quantity
        static::saved(function ($model) {
            if ($model->resource) {
                $model->resource->syncQuantityFromBatches();
            }
        });

        static::deleted(function ($model) {
            if ($model->resource) {
                $model->resource->syncQuantityFromBatches();
            }
        });
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
    
    /**
     * Get the total value of remaining stock in this batch
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity_remaining * $this->purchase_price;
    }
    
    /**
     * Get the quantity that has been used/consumed from this batch
     */
    public function getQuantityUsedAttribute(): float
    {
        return $this->quantity_purchased - $this->quantity_remaining;
    }

    /**
     * Get the usage percentage of this batch
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->quantity_purchased == 0) {
            return 0;
        }
        return ($this->quantity_used / $this->quantity_purchased) * 100;
    }

    /**
     * Check if this batch is fully consumed
     */
    public function getIsDepletedAttribute(): bool
    {
        return $this->quantity_remaining <= 0;
    }

    /**
     * Check if this batch has any remaining stock
     */
    public function getHasStockAttribute(): bool
    {
        return $this->quantity_remaining > 0;
    }

    /**
     * Consume quantity from this batch
     * Returns the actual amount consumed (may be less if batch depletes)
     */
    public function consume(float $quantity): float
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Cannot consume negative quantity.');
        }

        $consumable = min($quantity, $this->quantity_remaining);
        $this->quantity_remaining -= $consumable;
        $this->save();

        return $consumable;
    }

    /**
     * Add quantity back to this batch (e.g., for returns)
     */
    public function restore(float $quantity): void
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Cannot restore negative quantity.');
        }

        $newRemaining = $this->quantity_remaining + $quantity;
        
        if ($newRemaining > $this->quantity_purchased) {
            throw new InvalidArgumentException(
                'Cannot restore more than original quantity. ' .
                "Max restorable: " . ($this->quantity_purchased - $this->quantity_remaining)
            );
        }

        $this->quantity_remaining = $newRemaining;
        $this->save();
    }

    /**
     * Get the unit label for display
     */
    public function getUnitLabelAttribute(): string
    {
        return self::UNIT_TYPES[$this->unit_type] ?? $this->unit_type;
    }
}
