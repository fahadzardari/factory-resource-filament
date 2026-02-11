<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Imports resources from the cleaned CSV file
     */
    public function run(): void
    {
        $this->command->info('📥 Starting resource import from CSV...');
        
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
                    $data = [
                        'name' => trim($row[0] ?? ''),
                        'sku' => trim($row[1] ?? ''),
                        'category' => trim($row[2] ?? 'Others'),
                        'base_unit' => trim($row[3] ?? 'piece'),
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
            $this->command->info('Total resources in database: ' . Resource::count());
            $this->command->info('═══════════════════════════════════════════');
            
        } catch (\Exception $e) {
            $this->command->error('❌ Error reading CSV file: ' . $e->getMessage());
        }
    }
}
