<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Resource;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            ['name' => 'Building A Construction', 'code' => 'PROJ-2024-001', 'status' => 'active'],
            ['name' => 'Highway Bridge Renovation', 'code' => 'PROJ-2024-002', 'status' => 'active'],
            ['name' => 'Factory Equipment Installation', 'code' => 'PROJ-2024-003', 'status' => 'pending'],
            ['name' => 'Warehouse Expansion Phase 1', 'code' => 'PROJ-2024-004', 'status' => 'active'],
            ['name' => 'Office Complex Development', 'code' => 'PROJ-2024-005', 'status' => 'completed'],
            ['name' => 'Industrial Park Infrastructure', 'code' => 'PROJ-2024-006', 'status' => 'active'],
            ['name' => 'Shopping Mall Construction', 'code' => 'PROJ-2024-007', 'status' => 'pending'],
            ['name' => 'Residential Towers Project', 'code' => 'PROJ-2024-008', 'status' => 'active'],
            ['name' => 'Airport Terminal Upgrade', 'code' => 'PROJ-2024-009', 'status' => 'completed'],
            ['name' => 'Metro Station Development', 'code' => 'PROJ-2024-010', 'status' => 'active'],
        ];

        foreach ($projects as $index => $projectData) {
            $project = Project::create([
                'name' => $projectData['name'],
                'code' => $projectData['code'],
                'status' => $projectData['status'],
                'description' => 'Major construction project involving multiple phases and resource requirements.',
                'start_date' => now()->subDays(rand(30, 180)),
                'end_date' => $projectData['status'] === 'completed' ? now()->subDays(rand(1, 30)) : now()->addDays(rand(60, 365)),
            ]);

            // Allocate random resources to project
            if ($projectData['status'] !== 'completed') {
                $resources = Resource::inRandomOrder()->limit(rand(5, 15))->get();
                
                foreach ($resources as $resource) {
                    $allocatedQty = rand(10, 500);
                    $consumedQty = rand(0, (int)($allocatedQty * 0.7));
                    $availableQty = $allocatedQty - $consumedQty;
                    
                    $project->resources()->attach($resource->id, [
                        'quantity_allocated' => $allocatedQty,
                        'quantity_consumed' => $consumedQty,
                        'quantity_available' => $availableQty,
                        'notes' => 'Allocated for ' . $projectData['name'],
                    ]);
                    
                    // Update resource availability
                    $resource->update([
                        'available_quantity' => $resource->available_quantity - $availableQty,
                    ]);
                }
            }
        }
    }
}
