<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ProjectResourceConsumption extends Model
{
    protected $fillable = [
        'project_id',
        'resource_id',
        'consumption_date',
        'opening_balance',
        'quantity_consumed',
        'closing_balance',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'consumption_date' => 'date',
        'opening_balance' => 'decimal:2',
        'quantity_consumed' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // When creating a consumption, validate and update project resource pivot
        static::creating(function (ProjectResourceConsumption $consumption) {
            $project = $consumption->project;
            $resource = $consumption->resource;
            
            // Get current project allocation
            $pivot = $project->resources()->where('resource_id', $resource->id)->first();
            
            if (!$pivot) {
                throw new InvalidArgumentException("Resource not allocated to this project");
            }
            
            if ($pivot->pivot->quantity_available < $consumption->quantity_consumed) {
                throw new InvalidArgumentException(
                    "Insufficient quantity at project. Available: {$pivot->pivot->quantity_available}, Requested: {$consumption->quantity_consumed}"
                );
            }
            
            // Set opening/closing balances
            $consumption->opening_balance = $pivot->pivot->quantity_available;
            $consumption->closing_balance = $pivot->pivot->quantity_available - $consumption->quantity_consumed;
        });
        
        // After creating consumption, consume from batches (FIFO) and update project pivot
        static::created(function (ProjectResourceConsumption $consumption) {
            $project = $consumption->project;
            $resource = $consumption->resource;
            
            // Consume from warehouse batches using FIFO
            // This updates batch quantities and syncs resource.available_quantity
            $resource->consumeQuantityFifo($consumption->quantity_consumed);
            
            $pivot = $project->resources()->where('resource_id', $resource->id)->first();
            
            $project->resources()->updateExistingPivot($resource->id, [
                'quantity_consumed' => $pivot->pivot->quantity_consumed + $consumption->quantity_consumed,
                'quantity_available' => $pivot->pivot->quantity_available - $consumption->quantity_consumed,
            ]);
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

