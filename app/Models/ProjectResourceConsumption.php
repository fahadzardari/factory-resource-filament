<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

