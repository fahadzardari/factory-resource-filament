<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates all standard units with their conversion factors
     */
    public function run(): void
    {
        $this->command->info('📥 Starting unit seeding...');

        // Clear existing data (for fresh seeds)
        // Disable foreign keys temporarily to allow truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        UnitConversion::truncate();
        Unit::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ============================================
        // WEIGHT UNITS
        // ============================================
        $kg = Unit::create([
            'name' => 'Kilograms',
            'code' => 'kg',
            'unit_type' => 'weight',
            'is_base_unit' => true,
            'description' => 'Base unit for weight',
        ]);

        $g = Unit::create([
            'name' => 'Grams',
            'code' => 'g',
            'unit_type' => 'weight',
            'is_base_unit' => false,
            'description' => '1 kg = 1000 g',
        ]);

        $mg = Unit::create([
            'name' => 'Milligrams',
            'code' => 'mg',
            'unit_type' => 'weight',
            'is_base_unit' => false,
            'description' => '1 kg = 1,000,000 mg',
        ]);

        $ton = Unit::create([
            'name' => 'Metric Tons',
            'code' => 'ton',
            'unit_type' => 'weight',
            'is_base_unit' => false,
            'description' => '1 ton = 1000 kg',
        ]);

        $lb = Unit::create([
            'name' => 'Pounds',
            'code' => 'lb',
            'unit_type' => 'weight',
            'is_base_unit' => false,
            'description' => '1 kg = 2.20462 lb',
        ]);

        $oz = Unit::create([
            'name' => 'Ounces',
            'code' => 'oz',
            'unit_type' => 'weight',
            'is_base_unit' => false,
            'description' => '1 kg = 35.274 oz',
        ]);

        // Weight conversions (from kg)
        UnitConversion::create(['from_unit_id' => $kg->id, 'to_unit_id' => $g->id, 'conversion_factor' => 1000]);
        UnitConversion::create(['from_unit_id' => $kg->id, 'to_unit_id' => $mg->id, 'conversion_factor' => 1000000]);
        UnitConversion::create(['from_unit_id' => $kg->id, 'to_unit_id' => $ton->id, 'conversion_factor' => 0.001]);
        UnitConversion::create(['from_unit_id' => $kg->id, 'to_unit_id' => $lb->id, 'conversion_factor' => 2.20462]);
        UnitConversion::create(['from_unit_id' => $kg->id, 'to_unit_id' => $oz->id, 'conversion_factor' => 35.274]);

        // Reverse conversions
        UnitConversion::create(['from_unit_id' => $g->id, 'to_unit_id' => $kg->id, 'conversion_factor' => 0.001]);
        UnitConversion::create(['from_unit_id' => $mg->id, 'to_unit_id' => $kg->id, 'conversion_factor' => 0.000001]);
        UnitConversion::create(['from_unit_id' => $ton->id, 'to_unit_id' => $kg->id, 'conversion_factor' => 1000]);
        UnitConversion::create(['from_unit_id' => $lb->id, 'to_unit_id' => $kg->id, 'conversion_factor' => 0.453592]);
        UnitConversion::create(['from_unit_id' => $oz->id, 'to_unit_id' => $kg->id, 'conversion_factor' => 0.0283495]);

        // ============================================
        // LENGTH UNITS
        // ============================================
        $m = Unit::create([
            'name' => 'Meters',
            'code' => 'm',
            'unit_type' => 'length',
            'is_base_unit' => true,
            'description' => 'Base unit for length',
        ]);

        $cm = Unit::create([
            'name' => 'Centimeters',
            'code' => 'cm',
            'unit_type' => 'length',
            'is_base_unit' => false,
        ]);

        $mm = Unit::create([
            'name' => 'Millimeters',
            'code' => 'mm',
            'unit_type' => 'length',
            'is_base_unit' => false,
        ]);

        $km = Unit::create([
            'name' => 'Kilometers',
            'code' => 'km',
            'unit_type' => 'length',
            'is_base_unit' => false,
        ]);

        $ft = Unit::create([
            'name' => 'Feet',
            'code' => 'ft',
            'unit_type' => 'length',
            'is_base_unit' => false,
        ]);

        $inch = Unit::create([
            'name' => 'Inches',
            'code' => 'inch',
            'unit_type' => 'length',
            'is_base_unit' => false,
        ]);

        // Length conversions
        UnitConversion::create(['from_unit_id' => $m->id, 'to_unit_id' => $cm->id, 'conversion_factor' => 100]);
        UnitConversion::create(['from_unit_id' => $m->id, 'to_unit_id' => $mm->id, 'conversion_factor' => 1000]);
        UnitConversion::create(['from_unit_id' => $m->id, 'to_unit_id' => $km->id, 'conversion_factor' => 0.001]);
        UnitConversion::create(['from_unit_id' => $m->id, 'to_unit_id' => $ft->id, 'conversion_factor' => 3.28084]);
        UnitConversion::create(['from_unit_id' => $m->id, 'to_unit_id' => $inch->id, 'conversion_factor' => 39.3701]);

        // Reverse
        UnitConversion::create(['from_unit_id' => $cm->id, 'to_unit_id' => $m->id, 'conversion_factor' => 0.01]);
        UnitConversion::create(['from_unit_id' => $mm->id, 'to_unit_id' => $m->id, 'conversion_factor' => 0.001]);
        UnitConversion::create(['from_unit_id' => $km->id, 'to_unit_id' => $m->id, 'conversion_factor' => 1000]);
        UnitConversion::create(['from_unit_id' => $ft->id, 'to_unit_id' => $m->id, 'conversion_factor' => 0.3048]);
        UnitConversion::create(['from_unit_id' => $inch->id, 'to_unit_id' => $m->id, 'conversion_factor' => 0.0254]);

        // ============================================
        // VOLUME UNITS
        // ============================================
        $liter = Unit::create([
            'name' => 'Liters',
            'code' => 'liter',
            'unit_type' => 'volume',
            'is_base_unit' => true,
            'description' => 'Base unit for volume',
        ]);

        $ml = Unit::create([
            'name' => 'Milliliters',
            'code' => 'ml',
            'unit_type' => 'volume',
            'is_base_unit' => false,
        ]);

        $gallon = Unit::create([
            'name' => 'Gallons',
            'code' => 'gallon',
            'unit_type' => 'volume',
            'is_base_unit' => false,
        ]);

        $m3 = Unit::create([
            'name' => 'Cubic Meters',
            'code' => 'm3',
            'unit_type' => 'volume',
            'is_base_unit' => false,
        ]);

        // Volume conversions
        UnitConversion::create(['from_unit_id' => $liter->id, 'to_unit_id' => $ml->id, 'conversion_factor' => 1000]);
        UnitConversion::create(['from_unit_id' => $liter->id, 'to_unit_id' => $gallon->id, 'conversion_factor' => 0.264172]);
        UnitConversion::create(['from_unit_id' => $liter->id, 'to_unit_id' => $m3->id, 'conversion_factor' => 0.001]);

        // Reverse
        UnitConversion::create(['from_unit_id' => $ml->id, 'to_unit_id' => $liter->id, 'conversion_factor' => 0.001]);
        UnitConversion::create(['from_unit_id' => $gallon->id, 'to_unit_id' => $liter->id, 'conversion_factor' => 3.78541]);
        UnitConversion::create(['from_unit_id' => $m3->id, 'to_unit_id' => $liter->id, 'conversion_factor' => 1000]);

        // ============================================
        // AREA UNITS
        // ============================================
        $sqm = Unit::create([
            'name' => 'Square Meters',
            'code' => 'sqm',
            'unit_type' => 'area',
            'is_base_unit' => true,
            'description' => 'Base unit for area',
        ]);

        $sqft = Unit::create([
            'name' => 'Square Feet',
            'code' => 'sqft',
            'unit_type' => 'area',
            'is_base_unit' => false,
        ]);

        $sqcm = Unit::create([
            'name' => 'Square Centimeters',
            'code' => 'sqcm',
            'unit_type' => 'area',
            'is_base_unit' => false,
        ]);

        // Area conversions
        UnitConversion::create(['from_unit_id' => $sqm->id, 'to_unit_id' => $sqft->id, 'conversion_factor' => 10.7639]);
        UnitConversion::create(['from_unit_id' => $sqm->id, 'to_unit_id' => $sqcm->id, 'conversion_factor' => 10000]);
        UnitConversion::create(['from_unit_id' => $sqft->id, 'to_unit_id' => $sqm->id, 'conversion_factor' => 0.092903]);
        UnitConversion::create(['from_unit_id' => $sqcm->id, 'to_unit_id' => $sqm->id, 'conversion_factor' => 0.0001]);

        // ============================================
        // COUNT/QUANTITY UNITS
        // ============================================
        $piece = Unit::create([
            'name' => 'Pieces',
            'code' => 'piece',
            'unit_type' => 'count',
            'is_base_unit' => true,
            'description' => 'Individual pieces',
        ]);

        $dozen = Unit::create([
            'name' => 'Dozen',
            'code' => 'dozen',
            'unit_type' => 'count',
            'is_base_unit' => false,
            'description' => '12 pieces',
        ]);

        $box = Unit::create([
            'name' => 'Box',
            'code' => 'box',
            'unit_type' => 'count',
            'is_base_unit' => false,
        ]);

        $carton = Unit::create([
            'name' => 'Carton',
            'code' => 'carton',
            'unit_type' => 'count',
            'is_base_unit' => false,
        ]);

        $pallet = Unit::create([
            'name' => 'Pallet',
            'code' => 'pallet',
            'unit_type' => 'count',
            'is_base_unit' => false,
        ]);

        $bundle = Unit::create([
            'name' => 'Bundle',
            'code' => 'bundle',
            'unit_type' => 'count',
            'is_base_unit' => false,
        ]);

        $set = Unit::create([
            'name' => 'Set',
            'code' => 'set',
            'unit_type' => 'count',
            'is_base_unit' => false,
        ]);

        $pair = Unit::create([
            'name' => 'Pair',
            'code' => 'pair',
            'unit_type' => 'count',
            'is_base_unit' => false,
            'description' => '2 pieces',
        ]);

        // Additional count units that appear in CSV data
        $unit = Unit::create([
            'name' => 'Unit',
            'code' => 'unit',
            'unit_type' => 'count',
            'is_base_unit' => true,
            'description' => 'Generic single unit',
        ]);

        $bag = Unit::create([
            'name' => 'Bag',
            'code' => 'bag',
            'unit_type' => 'count',
            'is_base_unit' => false,
            'description' => 'A single bag (qty varies by item)',
        ]);

        $roll = Unit::create([
            'name' => 'Roll',
            'code' => 'roll',
            'unit_type' => 'count',
            'is_base_unit' => false,
            'description' => 'A single roll (qty varies by item)',
        ]);

        $sheet = Unit::create([
            'name' => 'Sheet',
            'code' => 'sheet',
            'unit_type' => 'count',
            'is_base_unit' => false,
            'description' => 'A single sheet (qty varies by item)',
        ]);

        $sack = Unit::create([
            'name' => 'Sack',
            'code' => 'sack',
            'unit_type' => 'count',
            'is_base_unit' => false,
            'description' => 'A single sack',
        ]);

        $panel = Unit::create([
            'name' => 'Panel',
            'code' => 'panel',
            'unit_type' => 'count',
            'is_base_unit' => false,
            'description' => 'A single panel',
        ]);

        $tile = Unit::create([
            'name' => 'Tile',
            'code' => 'tile',
            'unit_type' => 'count',
            'is_base_unit' => false,
            'description' => 'A single tile',
        ]);

        // Count conversions (basic)
        UnitConversion::create(['from_unit_id' => $piece->id, 'to_unit_id' => $dozen->id, 'conversion_factor' => 0.083333]);
        UnitConversion::create(['from_unit_id' => $piece->id, 'to_unit_id' => $pair->id, 'conversion_factor' => 0.5]);
        UnitConversion::create(['from_unit_id' => $dozen->id, 'to_unit_id' => $piece->id, 'conversion_factor' => 12]);
        UnitConversion::create(['from_unit_id' => $pair->id, 'to_unit_id' => $piece->id, 'conversion_factor' => 2]);

        // ============================================
        // SUMMARY
        // ============================================
        $totalUnits = Unit::count();
        $totalConversions = UnitConversion::count();

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('✅ UNIT SEEDING COMPLETE');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info("📊 Created: {$totalUnits} units");
        $this->command->info("🔄 Created: {$totalConversions} conversion factors");
        $this->command->info('═══════════════════════════════════════════');
        
        $this->command->line('');
        $this->command->line('Unit Categories:');
        $this->command->info('  • Weight:  kg, g, mg, ton, lb, oz (6 units)');
        $this->command->info('  • Length:  m, cm, mm, km, ft, inch (6 units)');
        $this->command->info('  • Volume:  liter, ml, gallon, m³ (4 units)');
        $this->command->info('  • Area:    sqm, sqft, sqcm (3 units)');
        $this->command->info('  • Count:   piece, dozen, box, carton, pallet, bundle, set, pair, unit, bag, roll, sheet, sack, panel, tile (15 units)');
        $this->command->line('');
    }
}
