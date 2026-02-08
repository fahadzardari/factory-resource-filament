<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceiptNote extends Model
{
    use HasFactory;

    protected $table = 'goods_receipt_notes';

    protected $fillable = [
        'grn_number',
        'supplier_id',
        'resource_id',
        'quantity_received',
        'unit_price',
        'total_value',
        'delivery_reference',
        'receipt_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'receipt_date' => 'date',
    ];

    /**
     * Boot method - Auto-generate GRN number and calculate total_value
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate GRN number if not provided
            if (!$model->grn_number) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $model->grn_number = "GRN-{$year}-" . str_pad($count, 5, '0', STR_PAD_LEFT);
            }

            // Auto-calculate total value
            $model->total_value = $model->quantity_received * $model->unit_price;

            // Auto-set created_by to current user if not set
            if (!$model->created_by && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }

    /**
     * Relationships
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'grn_id');
    }

    /**
     * Scopes
     */
    public function scopeForResource($query, int $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('receipt_date', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('receipt_date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('receipt_date')->limit($limit);
    }

    /**
     * Helper methods
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->grn_number} - {$this->supplier->name}";
    }

    public function getResourceDisplayAttribute(): string
    {
        return "{$this->resource->name} ({$this->quantity_received} {$this->resource->base_unit})";
    }
}
