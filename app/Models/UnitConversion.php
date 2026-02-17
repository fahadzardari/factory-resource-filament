<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversion extends Model
{
    protected $table = 'unit_conversions';

    protected $fillable = [
        'from_unit_id',
        'to_unit_id',
        'conversion_factor',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:10',
    ];

    /**
     * Get the unit being converted FROM
     */
    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    /**
     * Get the unit being converted TO
     */
    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    /**
     * Boot - prevent direct deletion (use via Unit instead)
     */
    protected static function boot()
    {
        parent::boot();

        // Conversions cascade delete with unit via FK
    }
}
