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

class ProjectImportTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Return example rows to help users understand the format
        return collect([
            [
                'name' => 'Factory Building A',
                'code' => 'FAC-A-2026',
                'location' => 'Industrial Zone, Dubai',
                'start_date' => '2026-01-15',
                'end_date' => '2026-06-30',
                'status' => 'active',
                'description' => 'Main production facility construction',
            ],
            [
                'name' => 'Warehouse Extension',
                'code' => 'WH-EXT-2026',
                'location' => 'Abu Dhabi Logistics Park',
                'start_date' => '2026-02-01',
                'end_date' => '2026-08-31',
                'status' => 'pending',
                'description' => 'Extending storage capacity by 500 sqm',
            ],
            [
                'name' => 'Office Renovation',
                'code' => 'OFF-REN-2026',
                'location' => 'Sharjah Head Office',
                'start_date' => '2026-03-10',
                'end_date' => '2026-05-20',
                'status' => 'active',
                'description' => 'Complete renovation of 3rd floor offices',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'code',
            'location',
            'start_date',
            'end_date',
            'status',
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
                    'startColor' => ['rgb' => '059669'], // Green color
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            2 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1FAE5'], // Light green
                ],
            ],
            3 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1FAE5'], // Light green
                ],
            ],
            4 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1FAE5'], // Light green
                ],
            ],
        ];
    }
}
