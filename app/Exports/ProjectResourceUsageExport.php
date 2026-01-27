<?php

namespace App\Exports;

use App\Models\Project;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectResourceUsageExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected ?array $projectIds = null
    ) {}

    public function collection(): Collection
    {
        $query = Project::with(['resources.batches', 'consumptions']);

        if ($this->projectIds) {
            $query->whereIn('id', $this->projectIds);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Project Code',
            'Project Name',
            'Status',
            'Start Date',
            'End Date',
            'Resources Count',
            'Total Allocated',
            'Total Consumed',
            'Total Available',
            'Consumption %',
            'Allocated Value ($)',
            'Consumed Value ($)',
            'Remaining Value ($)',
            'Duration (Days)',
            'Description',
        ];
    }

    public function map($project): array
    {
        $allocated = $project->resources->sum('pivot.quantity_allocated');
        $consumed = $project->resources->sum('pivot.quantity_consumed');
        $available = $project->resources->sum('pivot.quantity_available');

        $allocatedValue = 0;
        $consumedValue = 0;
        $remainingValue = 0;

        foreach ($project->resources as $resource) {
            $cost = $resource->weighted_average_price;
            $allocatedValue += ($resource->pivot->quantity_allocated * $cost);
            $consumedValue += ($resource->pivot->quantity_consumed * $cost);
            $remainingValue += ($resource->pivot->quantity_available * $cost);
        }

        $consumptionPercentage = $allocated > 0 ? ($consumed / $allocated) * 100 : 0;

        $duration = 'N/A';
        if ($project->start_date) {
            $endDate = $project->end_date ?? now();
            $duration = $project->start_date->diffInDays($endDate);
        }

        return [
            $project->code,
            $project->name,
            ucfirst($project->status),
            $project->start_date?->format('Y-m-d'),
            $project->end_date?->format('Y-m-d'),
            $project->resources->count(),
            number_format($allocated, 2),
            number_format($consumed, 2),
            number_format($available, 2),
            number_format($consumptionPercentage, 2).'%',
            number_format($allocatedValue, 2),
            number_format($consumedValue, 2),
            number_format($remainingValue, 2),
            $duration,
            $project->description,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Project Resource Usage';
    }
}
