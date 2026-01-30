<?php

namespace App\Exports;

use App\Models\InventoryTransaction;
use App\Models\Resource;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResourceTransactionsExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected int $resourceId;
    protected ?string $dateFrom;
    protected ?string $dateTo;

    public function __construct(int $resourceId, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->resourceId = $resourceId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function query()
    {
        $query = InventoryTransaction::query()
            ->where('resource_id', $this->resourceId)
            ->with(['project', 'createdBy', 'resource'])
            ->orderBy('transaction_date', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('transaction_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('transaction_date', '<=', $this->dateTo);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Type',
            'Project',
            'Quantity',
            'Unit Price',
            'Total Value',
            'Metadata',
            'Created By',
            'Created At',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->transaction_date,
            $transaction->transaction_type,
            $transaction->project?->name ?? 'Hub',
            $transaction->quantity,
            $transaction->unit_price,
            $transaction->total_value,
            $transaction->metadata,
            $transaction->createdBy?->name ?? 'System',
            $transaction->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function title(): string
    {
        $resource = Resource::find($this->resourceId);
        return substr($resource->sku . ' Transactions', 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
