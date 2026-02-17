<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class UnitConversionService
{
    /**
     * Get all units by type
     * Used for filtering units by category (weight, length, volume, etc.)
     *
     * @param string $unitType e.g., 'weight', 'length', 'volume'
     * @return Collection Units of the specified type
     */
    public function getUnitsByType(string $unitType): Collection
    {
        return Unit::byType($unitType)->get();
    }

    /**
     * Get all available conversion options for a specific unit
     * Returns an array of unit codes → display names for form dropdowns
     *
     * @param int|string $unitIdOrCode Unit ID or code (e.g., 'kg', 1, etc.)
     * @return array Format: ['kg' => 'Kilograms (kg)', 'g' => 'Grams (g)', ...]
     */
    public function getConversionOptions($unitIdOrCode): array
    {
        // Get the unit (by ID or code)
        if (is_int($unitIdOrCode) || is_numeric($unitIdOrCode)) {
            $unit = Unit::find($unitIdOrCode);
        } else {
            $unit = Unit::where('code', $unitIdOrCode)->first();
        }

        if (!$unit) {
            return [];
        }

        // Start with this unit itself
        $options = [
            $unit->code => "{$unit->name} ({$unit->code}) - Base Unit",
        ];

        // Add all units this can convert to (via conversionsFrom)
        $conversions = $unit->conversionsFrom()
            ->with('toUnit')
            ->get();

        foreach ($conversions as $conversion) {
            $toUnit = $conversion->toUnit;
            $options[$toUnit->code] = "{$toUnit->name} ({$toUnit->code})";
        }

        return $options;
    }

    /**
     * Get conversion factor between two units
     * If no direct conversion exists, returns null
     *
     * @param string $fromUnitCode e.g., 'kg'
     * @param string $toUnitCode e.g., 'g'
     * @return float|null Conversion factor or null if no conversion exists
     */
    public function getConversionFactor(string $fromUnitCode, string $toUnitCode): ?float
    {
        // Same unit = factor of 1
        if ($fromUnitCode === $toUnitCode) {
            return 1.0;
        }

        // Look up conversion in database
        $fromUnit = Unit::where('code', $fromUnitCode)->first();
        $toUnit = Unit::where('code', $toUnitCode)->first();

        if (!$fromUnit || !$toUnit) {
            return null;
        }

        // Find conversion record
        $conversion = UnitConversion::where('from_unit_id', $fromUnit->id)
            ->where('to_unit_id', $toUnit->id)
            ->first();

        return $conversion ? (float) $conversion->conversion_factor : null;
    }

    /**
     * Convert a quantity from one unit to another
     * Example: convertQuantity(1, 'kg', 'g') returns 1000
     *
     * @param float $quantity The amount to convert
     * @param string $fromUnitCode Source unit code (e.g., 'kg')
     * @param string $toUnitCode Target unit code (e.g., 'g')
     * @return float Converted quantity
     * @throws InvalidArgumentException If conversion not possible
     */
    public function convertQuantity(float $quantity, string $fromUnitCode, string $toUnitCode): float
    {
        // Get conversion factor
        $factor = $this->getConversionFactor($fromUnitCode, $toUnitCode);

        if ($factor === null) {
            throw new InvalidArgumentException(
                "Cannot convert from {$fromUnitCode} to {$toUnitCode}. No conversion defined."
            );
        }

        return $quantity * $factor;
    }

    /**
     * Get all units of the same type (category) for bulk operations
     * Used for finding all compatible units for a resource
     *
     * @param string $unitCode Unit code to get type for
     * @return Collection All units of that type
     */
    public function getUnitTypesForUnit(string $unitCode): Collection
    {
        $unit = Unit::where('code', $unitCode)->first();

        if (!$unit) {
            return collect();
        }

        return Unit::byType($unit->unit_type)->get();
    }

    /**
     * Check if two units are convertible (same type)
     *
     * @param string $unitCode1
     * @param string $unitCode2
     * @return bool
     */
    public function areConvertible(string $unitCode1, string $unitCode2): bool
    {
        $unit1 = Unit::where('code', $unitCode1)->first();
        $unit2 = Unit::where('code', $unitCode2)->first();

        if (!$unit1 || !$unit2) {
            return false;
        }

        return $unit1->unit_type === $unit2->unit_type;
    }

    /**
     * Get all unit types available in the system
     * Used for creating new units (select a type)
     *
     * @return Collection Distinct unit types
     */
    public function getAllUnitTypes(): Collection
    {
        return Unit::distinct('unit_type')->pluck('unit_type');
    }

    /**
     * Get base unit for a specific unit type
     * Every type should have exactly one base unit
     *
     * @param string $unitType e.g., 'weight'
     * @return Unit|null The base unit for that type
     */
    public function getBaseUnitForType(string $unitType): ?Unit
    {
        return Unit::where('unit_type', $unitType)
            ->where('is_base_unit', true)
            ->first();
    }
}
