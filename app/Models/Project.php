<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
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
}
