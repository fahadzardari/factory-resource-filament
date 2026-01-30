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
        // When creating a consumption, validate project has the resource
        static::creating(function (ProjectResourceConsumption $consumption) {
            $project = $consumption->project;
            $resource = $consumption->resource;
            
            // Check project has sufficient quantity (from its own batches)
            $projectQuantity = $project->getResourceQuantity($resource);
            
            if ($projectQuantity < $consumption->quantity_consumed) {
                throw new InvalidArgumentException(
                    "Insufficient quantity in Project inventory. Available: {$projectQuantity}, Requested: {$consumption->quantity_consumed}"
                );
            }
            
            // Set opening/closing balances
            $consumption->opening_balance = $projectQuantity;
            $consumption->closing_balance = $projectQuantity - $consumption->quantity_consumed;
        });
        
        // After creating consumption, consume from PROJECT's own batches using FIFO
        static::created(function (ProjectResourceConsumption $consumption) {
            $project = $consumption->project;
            $resource = $consumption->resource;
            
            // Get PROJECT's batches for this resource (not Central Hub batches)
            $projectBatches = $resource->batches()
                ->forProject($project->id)
                ->orderBy('purchase_date')
                ->orderBy('id')
                ->get();
            
            $remainingToConsume = $consumption->quantity_consumed;
            
            foreach ($projectBatches as $batch) {
                if ($remainingToConsume <= 0) {
                    break;
                }

                // Convert batch quantity to base unit for comparison
                $batchAvailableInBaseUnit = $batch->quantity_remaining * $batch->conversion_factor;
                
                if ($batchAvailableInBaseUnit <= 0) {
                    continue;
                }

                // Calculate how much to consume from this batch (in base unit)
                $consumeFromBatch = min($remainingToConsume, $batchAvailableInBaseUnit);
                
                // Convert back to batch's unit for updating the batch
                $consumeInBatchUnit = $consumeFromBatch / $batch->conversion_factor;
                
                // Update batch quantity
                $batch->quantity_remaining -= $consumeInBatchUnit;
                $batch->save();
                
                $remainingToConsume -= $consumeFromBatch;
            }
            
            // Note: We don't sync resource.available_quantity here because
            // project batches are separate from central hub batches
            // Central hub quantity remains unchanged
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

