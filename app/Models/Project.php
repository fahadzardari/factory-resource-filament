<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get stock of a specific resource at this project
     */
    public function getResourceStock(int $resourceId): float
    {
        return $this->transactions()
            ->where('resource_id', $resourceId)
            ->sum('quantity');
    }

    /**
     * Get all resources with stock at this project
     */
    public function getInventorySummary()
    {
        return $this->transactions()
            ->selectRaw('resource_id, SUM(quantity) as total_quantity, SUM(total_value) as total_value')
            ->groupBy('resource_id')
            ->having('total_quantity', '>', 0)
            ->with('resource')
            ->get();
    }

    /**
     * Get total inventory value at this project
     */
    public function getTotalInventoryValueAttribute(): float
    {
        return $this->transactions()->sum('total_value');
    }
}
