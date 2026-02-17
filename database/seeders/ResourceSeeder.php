<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Imports resources from the cleaned CSV file
     */
    public function run(): void
    {
        $this->command->info('📥 Starting resource import from CSV...');
        
        // First, ensure units exist (UnitSeeder should have run)
        $unitCount = Unit::count();
        if ($unitCount === 0) {
            $this->command->error('❌ No units found. Please run UnitSeeder first: php artisan db:seed --class=UnitSeeder');
            return;
        }
        
        $this->command->info("✓ Found {$unitCount} units in database");
        
        // Path to the cleaned CSV file
        $csvFile = base_path('List of Items - CLEANED.csv');
        
        // Check if file exists
        if (!file_exists($csvFile)) {
            $this->command->error('❌ CSV file not found at: ' . $csvFile);
            return;
        }
        
        try {
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $unitsUsed = [];
            
            // Open and read the CSV file
            if (($handle = fopen($csvFile, 'r')) !== false) {
                // Skip header row
                $header = fgetcsv($handle);
                
                // Track row number for error reporting
                $rowNumber = 2;
                
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;
                    
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        $skipped++;
                        continue;
                    }
                    
                    // Map CSV columns to array
                    $baseUnit = trim($row[3] ?? 'piece');
                    
                    // Normalize unit code to match database (lowercase)
                    $baseUnit = strtolower($baseUnit);
                    
                    // Validate unit exists in database
                    $unitExists = Unit::where('code', $baseUnit)->exists();
                    if (!$unitExists) {
                        // Try to find a similar unit or default to 'piece'
                        $this->command->warn("⚠️  Row {$rowNumber}: Unit '{$baseUnit}' not found, defaulting to 'piece'");
                        $baseUnit = 'piece';
                    }
                    
                    // Track units being used
                    if (!in_array($baseUnit, $unitsUsed)) {
                        $unitsUsed[] = $baseUnit;
                    }
                    
                    $data = [
                        'name' => trim($row[0] ?? ''),
                        'sku' => trim($row[1] ?? ''),
                        'category' => trim($row[2] ?? 'Others'),
                        'base_unit' => $baseUnit,
                        'description' => trim($row[4] ?? ''),
                    ];
                    
                    // Validate required fields
                    if (empty($data['name']) || empty($data['sku'])) {
                        $this->command->warn("⚠️  Row {$rowNumber}: Missing name or SKU, skipping...");
                        $skipped++;
                        continue;
                    }
                    
                    try {
                        // Use firstOrCreate to handle duplicates gracefully
                        $result = Resource::firstOrCreate(
                            ['sku' => $data['sku']],
                            $data
                        );
                        
                        if ($result->wasRecentlyCreated) {
                            $created++;
                        } else {
                            $updated++;
                        }
                    } catch (\Exception $e) {
                        $this->command->error("❌ Row {$rowNumber}: Failed to create/update resource - " . $e->getMessage());
                        $skipped++;
                    }
                }
                
                fclose($handle);
            }
            
            // Display summary
            $this->command->info('');
            $this->command->info('═══════════════════════════════════════════');
            $this->command->info('✅ RESOURCE IMPORT COMPLETE');
            $this->command->info('═══════════════════════════════════════════');
            $this->command->line('📊 Summary:');
            $this->command->info("  ✓ Created:  {$created} new resources");
            $this->command->info("  ↻ Updated:  {$updated} existing resources");
            $this->command->info("  ⊘ Skipped:  {$skipped} invalid rows");
            $this->command->line('');
            $this->command->info('📐 Units Assigned:');
            sort($unitsUsed);
            foreach ($unitsUsed as $unit) {
                $unitRecord = Unit::where('code', $unit)->first();
                $count = Resource::where('base_unit', $unit)->count();
                $label = $unitRecord ? $unitRecord->name . ' (' . $unitRecord->unit_type . ')' : $unit;
                $this->command->info("   • {$label}: {$count} resources");
            }
            $this->command->line('');
            $this->command->info('Total resources in database: ' . Resource::count());
            $this->command->info('═══════════════════════════════════════════');
            
        } catch (\Exception $e) {
            $this->command->error('❌ Error reading CSV file: ' . $e->getMessage());
        }
    }
}
