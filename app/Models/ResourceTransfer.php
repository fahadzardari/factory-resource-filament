<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceTransfer extends Model
{
    protected $fillable = [
        'resource_id',
        'from_project_id',
        'to_project_id',
        'quantity',
        'transfer_type',
        'notes',
        'transferred_by',
        'transferred_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'transferred_at' => 'datetime',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function fromProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'from_project_id');
    }

    public function toProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'to_project_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
