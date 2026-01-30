<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category',
        'base_unit',
        'description',
    ];

    /**
     * Relationships
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get hub stock (current balance in central warehouse)
     */
    public function getHubStockAttribute(): float
    {
        return $this->transactions()
            ->whereNull('project_id')
            ->sum('quantity');
    }

    /**
     * Get total stock across all locations (hub + all projects)
     */
    public function getTotalStockAttribute(): float
    {
        return $this->transactions()->sum('quantity');
    }

    /**
     * Get stock at a specific project
     */
    public function getProjectStock(int $projectId): float
    {
        return $this->transactions()
            ->where('project_id', $projectId)
            ->sum('quantity');
    }

    /**
     * Get weighted average price in hub
     */
    public function getWeightedAveragePriceAttribute(): float
    {
        $hubTransactions = $this->transactions()
            ->whereNull('project_id')
            ->get();

        $totalQuantity = $hubTransactions->sum('quantity');
        
        if ($totalQuantity <= 0) {
            return 0;
        }

        $totalValue = $hubTransactions->sum('total_value');
        
        return $totalValue / $totalQuantity;
    }

    /**
     * Get total value in hub
     */
    public function getHubValueAttribute(): float
    {
        return $this->transactions()
            ->whereNull('project_id')
            ->sum('total_value');
    }
}
