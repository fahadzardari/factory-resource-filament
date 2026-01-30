<?php

namespace App\Exports;

use App\Models\InventoryTransaction;
use App\Models\Project;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProjectResourceUsageExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected int $projectId;
    protected ?string $dateFrom;
    protected ?string $dateTo;

    public function __construct(int $projectId, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->projectId = $projectId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection(): Collection
    {
        $query = InventoryTransaction::query()
            ->where('project_id', $this->projectId)
            ->with(['resource', 'createdBy'])
            ->orderBy('transaction_date', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('transaction_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('transaction_date', '<=', $this->dateTo);
        }

        $transactions = $query->get();
        
        // Add summary row
        if ($transactions->isNotEmpty()) {
            $totalQuantity = $transactions->sum('quantity');
            $totalValue = $transactions->sum('total_value');
            
            $transactions->push((object)[
                'transaction_date' => '',
                'transaction_type' => 'SUMMARY',
                'resource' => (object)['name' => 'TOTAL', 'sku' => '', 'category' => '', 'base_unit' => ''],
                'quantity' => $totalQuantity,
                'unit_price' => '',
                'total_value' => $totalValue,
                'metadata' => '',
                'createdBy' => null,
                'created_at' => null,
                'is_summary' => true,
            ]);
        }
        
        return $transactions;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Type',
            'Resource Name',
            'Resource SKU',
            'Category',
            'Quantity',
            'Unit',
            'Unit Price (AED)',
            'Total Value (AED)',
            'Metadata',
            'Recorded By',
            'Time',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->transaction_date ?: '',
            $transaction->transaction_type ?? '',
            $transaction->resource->name ?? '',
            $transaction->resource->sku ?? '',
            $transaction->resource->category ?? '',
            number_format($transaction->quantity, 2),
            $transaction->resource->base_unit ?? '',
            $transaction->unit_price ? number_format($transaction->unit_price, 2) : '',
            number_format($transaction->total_value, 2),
            $transaction->metadata ?? '',
            $transaction->createdBy?->name ?? '',
            $transaction->created_at ? $transaction->created_at->format('H:i:s') : '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        
        return [
            1 => ['font' => ['bold' => true]],
            $lastRow => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD']
                ]
            ],
        ];
    }

    public function title(): string
    {
        $project = Project::find($this->projectId);
        return substr($project->code . ' Usage', 0, 31);
    }
}
