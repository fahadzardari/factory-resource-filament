<?php

namespace App\Exports;

use App\Models\ProjectResourceConsumption;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyConsumptionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected ?int $projectId = null,
        protected ?array $filters = []
    ) {}

    public function collection(): Collection
    {
        $query = ProjectResourceConsumption::with(['project', 'resource.batches', 'recordedBy']);

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        if (! empty($this->filters['from_date'])) {
            $query->whereDate('consumption_date', '>=', $this->filters['from_date']);
        }

        if (! empty($this->filters['to_date'])) {
            $query->whereDate('consumption_date', '<=', $this->filters['to_date']);
        }

        if (! empty($this->filters['resource_ids'])) {
            $query->whereIn('resource_id', $this->filters['resource_ids']);
        }

        return $query->orderBy('consumption_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Project Code',
            'Project Name',
            'Date',
            'Day of Week',
            'Resource Name',
            'Resource SKU',
            'Category',
            'Unit Type',
            'Opening Balance',
            'Consumed',
            'Closing Balance',
            'Consumption %',
            'Unit Cost ($)',
            'Daily Value ($)',
            'Recorded By',
            'Notes',
        ];
    }

    public function map($consumption): array
    {
        $cost = $consumption->resource?->weighted_average_price ?? 0;
        $dailyValue = $consumption->quantity_consumed * $cost;
        $consumptionPercentage = $consumption->opening_balance > 0
            ? ($consumption->quantity_consumed / $consumption->opening_balance) * 100
            : 0;

        return [
            $consumption->project->code,
            $consumption->project->name,
            $consumption->consumption_date?->format('Y-m-d'),
            $consumption->consumption_date?->format('l'),
            $consumption->resource->name,
            $consumption->resource->sku,
            $consumption->resource->category,
            $consumption->resource->unit_type,
            number_format($consumption->opening_balance, 2),
            number_format($consumption->quantity_consumed, 2),
            number_format($consumption->closing_balance, 2),
            number_format($consumptionPercentage, 2).'%',
            number_format($cost, 2),
            number_format($dailyValue, 2),
            $consumption->recordedBy?->name ?? 'N/A',
            $consumption->notes,
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
                    'startColor' => ['rgb' => 'DC2626'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Daily Consumption History';
    }
}
