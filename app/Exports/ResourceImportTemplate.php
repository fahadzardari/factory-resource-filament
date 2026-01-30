<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class ResourceImportTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Return example rows to help users understand the format
        return collect([
            [
                'name' => 'Portland Cement',
                'sku' => 'CEM-001',
                'category' => 'Building Materials',
                'base_unit' => 'kg',
                'current_price' => 25.50,
                'description' => 'High-grade Portland cement for construction',
            ],
            [
                'name' => 'Steel Reinforcement Bars',
                'sku' => 'STL-001',
                'category' => 'Building Materials',
                'base_unit' => 'kg',
                'current_price' => 12.75,
                'description' => '12mm diameter steel bars',
            ],
            [
                'name' => 'Red Paint',
                'sku' => 'PNT-RED-001',
                'category' => 'Finishing Materials',
                'base_unit' => 'liters',
                'current_price' => 45.00,
                'description' => 'Premium red exterior paint',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'sku',
            'category',
            'base_unit',
            'current_price',
            'description',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Indigo color
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            2 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E7FF'], // Light indigo
                ],
            ],
            3 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E7FF'], // Light indigo
                ],
            ],
            4 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E7FF'], // Light indigo
                ],
            ],
        ];
    }
}
