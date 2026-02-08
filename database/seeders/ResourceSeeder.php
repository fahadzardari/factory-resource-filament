<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resources = [
            // Building Materials
            [
                'name' => 'Portland Cement',
                'sku' => 'MAT-CEMENT-001',
                'category' => 'Raw Materials',
                'base_unit' => 'kg',
                'description' => 'High-quality Portland cement for construction',
            ],
            [
                'name' => 'Steel Reinforcement Bars (16mm)',
                'sku' => 'MAT-STEEL-016',
                'category' => 'Raw Materials',
                'base_unit' => 'kg',
                'description' => 'Deformed steel rebar 16mm diameter',
            ],
            [
                'name' => 'Steel Reinforcement Bars (12mm)',
                'sku' => 'MAT-STEEL-012',
                'category' => 'Raw Materials',
                'base_unit' => 'kg',
                'description' => 'Deformed steel rebar 12mm diameter',
            ],
            [
                'name' => 'White Sand',
                'sku' => 'MAT-SAND-WHITE',
                'category' => 'Raw Materials',
                'base_unit' => 'ton',
                'description' => 'White sand for construction and finishing',
            ],
            [
                'name' => 'Red Bricks (Standard)',
                'sku' => 'MAT-BRICK-RED',
                'category' => 'Raw Materials',
                'base_unit' => 'piece',
                'description' => 'Standard red clay bricks 230x110x76mm',
            ],
            [
                'name' => 'Ceramic Floor Tiles (60x60)',
                'sku' => 'MAT-TILE-CERAMIC',
                'category' => 'Raw Materials',
                'base_unit' => 'piece',
                'description' => 'Ceramic floor tiles 60x60cm premium quality',
            ],
            [
                'name' => 'Gypsum Board (12.5mm)',
                'sku' => 'MAT-GYPSUM-125',
                'category' => 'Raw Materials',
                'base_unit' => 'piece',
                'description' => 'Drywall gypsum boards 12.5mm thickness, 2.4x1.2m',
            ],
            [
                'name' => 'Plywood Sheet (Marine 12mm)',
                'sku' => 'MAT-PLYWOOD-12',
                'category' => 'Raw Materials',
                'base_unit' => 'piece',
                'description' => 'Marine grade plywood 12mm, 2.44x1.22m',
            ],
            
            // Paints & Coatings
            [
                'name' => 'Emulsion Paint (White)',
                'sku' => 'PAINT-EMUL-WHITE',
                'category' => 'Consumables',
                'base_unit' => 'liter',
                'description' => 'Interior emulsion paint white 20L',
            ],
            [
                'name' => 'Oil Paint (Brown)',
                'sku' => 'PAINT-OIL-BROWN',
                'category' => 'Consumables',
                'base_unit' => 'liter',
                'description' => 'Premium oil paint brown shade 20L',
            ],
            [
                'name' => 'Primer Coating',
                'sku' => 'PAINT-PRIMER',
                'category' => 'Consumables',
                'base_unit' => 'liter',
                'description' => 'Metal primer coating 20L drum',
            ],
            
            // Tools & Equipment
            [
                'name' => 'Cement Mixer (Machine)',
                'sku' => 'TOOL-MIXER-CEMENT',
                'category' => 'Equipment',
                'base_unit' => 'piece',
                'description' => 'Portable electric cement mixer 200L',
            ],
            [
                'name' => 'Scaffolding Pipes (1 inch)',
                'sku' => 'TOOL-SCAFFOLD-1IN',
                'category' => 'Equipment',
                'base_unit' => 'meter',
                'description' => 'Steel scaffolding pipes 1 inch diameter',
            ],
            [
                'name' => 'Safety Helmet',
                'sku' => 'TOOL-HELMET-SAFETY',
                'category' => 'Tools',
                'base_unit' => 'piece',
                'description' => 'Construction safety helmet',
            ],
            [
                'name' => 'Safety Harness',
                'sku' => 'TOOL-HARNESS-SAFETY',
                'category' => 'Tools',
                'base_unit' => 'piece',
                'description' => 'Full body safety harness',
            ],
            [
                'name' => 'Work Gloves (Leather)',
                'sku' => 'TOOL-GLOVES-LEATHER',
                'category' => 'Consumables',
                'base_unit' => 'piece',
                'description' => 'Protective leather work gloves (pair)',
            ],
            
            // Electrical Materials
            [
                'name' => 'electrical Wire (2.5mm)',
                'sku' => 'ELEC-WIRE-2.5',
                'category' => 'Raw Materials',
                'base_unit' => 'meter',
                'description' => 'Copper electrical wire 2.5mm² insulated',
            ],
            [
                'name' => 'Circuit Breaker (MCB 16A)',
                'sku' => 'ELEC-BREAKER-16A',
                'category' => 'Equipment',
                'base_unit' => 'piece',
                'description' => 'Miniature circuit breaker 16A single pole',
            ],
            [
                'name' => 'LED Light Bulb (10W)',
                'sku' => 'ELEC-LED-10W',
                'category' => 'Consumables',
                'base_unit' => 'piece',
                'description' => 'LED bulb 10W cool white',
            ],
        ];

        foreach ($resources as $resource) {
            Resource::firstOrCreate(
                ['sku' => $resource['sku']],
                $resource
            );
        }

        $this->command->info('✅ ' . count($resources) . ' resources seeded successfully!');
    }
}
