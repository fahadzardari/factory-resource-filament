<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [
        'name',
        'code',
        'unit_type',
        'is_base_unit',
        'description',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
    ];

    /**
     * Get all conversion factors FROM this unit to other units
     * Example: kg → g, kg → lb
     */
    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'from_unit_id');
    }

    /**
     * Get all conversion factors TO this unit from other units
     * Example: g → kg, lb → kg
     */
    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'to_unit_id');
    }

    /**
     * Get all available sub-units for this base unit
     * Used for displaying conversion options in forms
     */
    public function getAvailableConversions()
    {
        return $this->conversionsFrom()
            ->with('toUnit')
            ->get()
            ->pluck('toUnit')
            ->push($this) // Include self
            ->keyBy('code')
            ->map(function ($unit) {
                return "{$unit->name} ({$unit->code})";
            });
    }

    /**
     * Scope: Get all units of a specific type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('unit_type', $type);
    }

    /**
     * Scope: Get only base units
     */
    public function scopeBaseUnits($query)
    {
        return $query->where('is_base_unit', true);
    }
}
