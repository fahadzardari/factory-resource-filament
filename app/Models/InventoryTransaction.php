<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class InventoryTransaction extends Model
{
    // Transaction Types (Enum)
    const TYPE_PURCHASE = 'PURCHASE'; // Old system (hidden from UI)
    const TYPE_GOODS_RECEIPT = 'GOODS_RECEIPT'; // GRN received
    const TYPE_ALLOCATION_OUT = 'ALLOCATION_OUT';
    const TYPE_ALLOCATION_IN = 'ALLOCATION_IN';
    const TYPE_CONSUMPTION = 'CONSUMPTION'; // Project consumption
    const TYPE_DIRECT_CONSUMPTION = 'DIRECT_CONSUMPTION'; // Hub consumption
    const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';
    const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    const TYPE_ADJUSTMENT = 'ADJUSTMENT';

    protected $fillable = [
        'resource_id',
        'project_id',
        'transaction_type',
        'quantity',
        'unit_price',
        'total_value',
        'transaction_date',
        'reference_type',
        'reference_id',
        'notes',
        'supplier',
        'invoice_number',
        'grn_id',
        'consumption_reason',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Boot method - enforce immutability
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent updates to transactions (ledger is append-only)
        static::updating(function ($model) {
            throw new InvalidArgumentException(
                'Inventory transactions cannot be modified. The ledger is immutable. Create a new ADJUSTMENT transaction instead.'
            );
        });

        // Prevent deletion (ledger is permanent)
        static::deleting(function ($model) {
            throw new InvalidArgumentException(
                'Inventory transactions cannot be deleted. The ledger is permanent.'
            );
        });

        // Validate before creating
        static::creating(function ($model) {
            // Ensure total_value is calculated correctly
            $model->total_value = $model->quantity * $model->unit_price;
        });
    }

    /**
     * Relationships
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'grn_id');
    }

    /**
     * Scopes
     */
    public function scopeForHub($query)
    {
        return $query->whereNull('project_id');
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForResource($query, int $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }

    public function scopePurchases($query)
    {
        return $query->where('transaction_type', self::TYPE_PURCHASE);
    }

    public function scopeConsumptions($query)
    {
        return $query->where('transaction_type', self::TYPE_CONSUMPTION);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('transaction_date', $date);
    }

    public function scopeBeforeDate($query, $date)
    {
        return $query->where('transaction_date', '<', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Helper methods
     */
    public function isHubTransaction(): bool
    {
        return $this->project_id === null;
    }

    public function isProjectTransaction(): bool
    {
        return $this->project_id !== null;
    }

    public function isIncoming(): bool
    {
        return $this->quantity > 0;
    }

    public function isOutgoing(): bool
    {
        return $this->quantity < 0;
    }

    /**
     * Get all valid transaction types
     */
    public static function getTransactionTypes(): array
    {
        return [
            self::TYPE_PURCHASE,
            self::TYPE_GOODS_RECEIPT,
            self::TYPE_ALLOCATION_OUT,
            self::TYPE_ALLOCATION_IN,
            self::TYPE_CONSUMPTION,
            self::TYPE_DIRECT_CONSUMPTION,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_TRANSFER_IN,
            self::TYPE_ADJUSTMENT,
        ];
    }
}
