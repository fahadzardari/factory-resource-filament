<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class)
            ->withPivot(['quantity_allocated', 'quantity_consumed', 'quantity_available', 'notes'])
            ->withTimestamps();
    }
    
    public function batches(): HasMany
    {
        return $this->hasMany(ResourceBatch::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(ProjectResourceConsumption::class);
    }

    // Get today's consumption records
    public function todayConsumptions(): HasMany
    {
        return $this->hasMany(ProjectResourceConsumption::class)
            ->whereDate('consumption_date', today());
    }
    
    /**
     * Get total inventory quantity for a specific resource in this project
     * Returns quantity in the resource's base unit
     */
    public function getResourceQuantity(Resource $resource): float
    {
        return $this->batches()
            ->where('resource_id', $resource->id)
            ->get()
            ->sum(fn($batch) => $batch->quantity_remaining * $batch->conversion_factor);
    }
    
    /**
     * Check if project has sufficient quantity of a resource
     */
    public function hasResourceQuantity(Resource $resource, float $quantity): bool
    {
        return $this->getResourceQuantity($resource) >= $quantity;
    }
}
