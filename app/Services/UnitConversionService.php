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
     * SUPPORTS BIDIRECTIONAL: If A→B exists, B can also convert to A
     *
     * @param int|string $unitIdOrCode Unit ID or code (e.g., 'kg', 1, etc.)
     * @return array Format: ['kg' => 'Kilograms (kg)', 'g' => 'Grams (g)', ...]
     */
    public function getConversionOptions($unitIdOrCode): array
    {
        // Get the unit (by ID or code) - case-insensitive for codes
        if (is_int($unitIdOrCode) || is_numeric($unitIdOrCode)) {
            $unit = Unit::find($unitIdOrCode);
        } else {
            // Case-insensitive code lookup
            $unit = Unit::whereRaw('LOWER(code) = ?', [strtolower(trim($unitIdOrCode))])->first();
        }

        if (!$unit) {
            return [];
        }

        // Start with this unit itself
        $options = [
            $unit->code => "{$unit->name} ({$unit->code}) - Base Unit",
        ];

        // Add all units this can convert TO (direct: from_unit_id = this unit)
        $directConversions = $unit->conversionsFrom()
            ->with('toUnit')
            ->get();

        foreach ($directConversions as $conversion) {
            $toUnit = $conversion->toUnit;
            $options[$toUnit->code] = "{$toUnit->name} ({$toUnit->code})";
        }

        // Also add units that can convert TO this unit (reverse: to_unit_id = this unit)
        // This enables bidirectional conversion without requiring manual reciprocal entry
        $reverseConversions = $unit->conversionsTo()
            ->with('fromUnit')
            ->get();

        foreach ($reverseConversions as $conversion) {
            $fromUnit = $conversion->fromUnit;
            // Only add if not already in options (to avoid duplicates)
            if (!isset($options[$fromUnit->code])) {
                $options[$fromUnit->code] = "{$fromUnit->name} ({$fromUnit->code})";
            }
        }

        return $options;
    }

    /**
     * Get conversion factor between two units
     * SUPPORTS BIDIRECTIONAL: If A→B with factor X exists, B→A will use 1/X
     * No need to manually create reciprocal conversions
     *
     * @param string $fromUnitCode e.g., 'kg'
     * @param string $toUnitCode e.g., 'g'
     * @return float|null Conversion factor or null if no conversion exists
     */
    public function getConversionFactor(string $fromUnitCode, string $toUnitCode): ?float
    {
        // Normalize codes to lowercase for case-insensitive matching
        $fromUnitCode = strtolower(trim($fromUnitCode));
        $toUnitCode = strtolower(trim($toUnitCode));
        
        // Same unit = factor of 1
        if ($fromUnitCode === $toUnitCode) {
            return 1.0;
        }

        // Look up conversion in database (case-insensitive)
        $fromUnit = Unit::whereRaw('LOWER(code) = ?', [$fromUnitCode])->first();
        $toUnit = Unit::whereRaw('LOWER(code) = ?', [$toUnitCode])->first();

        if (!$fromUnit || !$toUnit) {
            return null;
        }

        // Find DIRECT conversion record (from_unit → to_unit)
        $conversion = UnitConversion::where('from_unit_id', $fromUnit->id)
            ->where('to_unit_id', $toUnit->id)
            ->first();

        if ($conversion) {
            return (float) $conversion->conversion_factor;
        }

        // BIDIRECTIONAL: Check for REVERSE conversion (to_unit → from_unit)
        // If it exists, use the reciprocal (1/factor)
        $reverseConversion = UnitConversion::where('from_unit_id', $toUnit->id)
            ->where('to_unit_id', $fromUnit->id)
            ->first();

        if ($reverseConversion) {
            $reverseFactor = (float) $reverseConversion->conversion_factor;
            // Avoid division by zero
            if ($reverseFactor != 0) {
                return 1.0 / $reverseFactor;
            }
        }

        return null;
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
        $unit = Unit::whereRaw('LOWER(code) = ?', [strtolower(trim($unitCode))])->first();

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
        $unit1 = Unit::whereRaw('LOWER(code) = ?', [strtolower(trim($unitCode1))])->first();
        $unit2 = Unit::whereRaw('LOWER(code) = ?', [strtolower(trim($unitCode2))])->first();

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
