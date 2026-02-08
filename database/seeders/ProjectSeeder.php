<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'name' => 'Downtown Office Complex',
                'code' => 'PRJ-DOC-2026-001',
                'description' => 'High-rise office building project in downtown area',
                'status' => 'active',
                'start_date' => now()->subMonths(3),
                'end_date' => now()->addMonths(9),
            ],
            [
                'name' => 'Residential Villa Community',
                'code' => 'PRJ-RVC-2026-002',
                'description' => 'Development of luxury residential villa community',
                'status' => 'active',
                'start_date' => now()->subMonths(2),
                'end_date' => now()->addMonths(12),
            ],
            [
                'name' => 'Shopping Mall Renovation',
                'code' => 'PRJ-SMR-2026-003',
                'description' => 'Complete renovation of existing shopping mall',
                'status' => 'active',
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(6),
            ],
            [
                'name' => 'Hospital Building Extension',
                'code' => 'PRJ-HBE-2026-004',
                'description' => 'Extension of hospital with new medical facilities',
                'status' => 'active',
                'start_date' => now()->subWeeks(2),
                'end_date' => now()->addMonths(8),
            ],
            [
                'name' => 'Warehouse & Logistics Hub',
                'code' => 'PRJ-WLH-2026-005',
                'description' => 'Construction of large warehouse and logistics center',
                'status' => 'active',
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(5),
            ],
            [
                'name' => 'University Campus Building',
                'code' => 'PRJ-UCB-2026-006',
                'description' => 'Construction of new academic building for university',
                'status' => 'pending',
                'start_date' => now()->addMonths(2),
                'end_date' => now()->addMonths(14),
            ],
            [
                'name' => 'Infrastructure - Road Project',
                'code' => 'PRJ-IRP-2026-007',
                'description' => 'Major road infrastructure development',
                'status' => 'active',
                'start_date' => now()->subMonths(4),
                'end_date' => now()->addMonths(7),
            ],
            [
                'name' => 'Data Center Facility',
                'code' => 'PRJ-DCF-2026-008',
                'description' => 'State-of-the-art data center facility construction',
                'status' => 'active',
                'start_date' => now()->subWeeks(3),
                'end_date' => now()->addMonths(10),
            ],
        ];

        foreach ($projects as $project) {
            Project::firstOrCreate(
                ['code' => $project['code']],
                $project
            );
        }

        $this->command->info('✅ ' . count($projects) . ' projects seeded successfully!');
    }
}
