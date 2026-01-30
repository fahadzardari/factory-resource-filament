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

class DailyConsumptionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected int $projectId;
    protected string $date;

    public function __construct(int $projectId, string $date)
    {
        $this->projectId = $projectId;
        $this->date = $date;
    }

    public function collection(): Collection
    {
        // Get all transactions for the day
        $dayTransactions = InventoryTransaction::query()
            ->where('project_id', $this->projectId)
            ->whereDate('transaction_date', $this->date)
            ->with(['resource'])
            ->orderBy('resource_id')
            ->orderBy('transaction_date')
            ->get();

        // Get unique resources from day's transactions
        $resourceIds = $dayTransactions->pluck('resource_id')->unique();
        
        // Calculate opening balance (all transactions before this date)
        $openingBalances = InventoryTransaction::query()
            ->where('project_id', $this->projectId)
            ->whereDate('transaction_date', '<', $this->date)
            ->selectRaw('resource_id, SUM(quantity) as opening_balance')
            ->groupBy('resource_id')
            ->pluck('opening_balance', 'resource_id');

        // Build result collection
        $result = collect();
        
        foreach ($resourceIds as $resourceId) {
            $resourceTransactions = $dayTransactions->where('resource_id', $resourceId);
            $resource = $resourceTransactions->first()->resource;
            
            $openingBalance = $openingBalances->get($resourceId, 0);
            $purchases = $resourceTransactions->where('transaction_type', 'PURCHASE')->sum('quantity');
            $allocations = $resourceTransactions->where('transaction_type', 'ALLOCATION_IN')->sum('quantity');
            $transfersIn = $resourceTransactions->where('transaction_type', 'TRANSFER_IN')->sum('quantity');
            $consumption = abs($resourceTransactions->where('transaction_type', 'CONSUMPTION')->sum('quantity'));
            $transfersOut = abs($resourceTransactions->where('transaction_type', 'TRANSFER_OUT')->sum('quantity'));
            $allocationsOut = abs($resourceTransactions->where('transaction_type', 'ALLOCATION_OUT')->sum('quantity'));
            
            $totalIn = $purchases + $allocations + $transfersIn;
            $totalOut = $consumption + $transfersOut + $allocationsOut;
            $closingBalance = $openingBalance + $totalIn - $totalOut;

            $result->push((object)[
                'resource_name' => $resource->name,
                'sku' => $resource->sku,
                'category' => $resource->category ?? 'N/A',
                'unit' => $resource->base_unit,
                'opening_balance' => $openingBalance,
                'purchases' => $purchases,
                'allocations_in' => $allocations,
                'transfers_in' => $transfersIn,
                'total_in' => $totalIn,
                'consumption' => $consumption,
                'transfers_out' => $transfersOut,
                'allocations_out' => $allocationsOut,
                'total_out' => $totalOut,
                'closing_balance' => $closingBalance,
            ]);
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            'Resource Name',
            'SKU',
            'Category',
            'Unit',
            'Opening Balance',
            'Purchases',
            'Allocations In',
            'Transfers In',
            'Total In',
            'Consumption',
            'Transfers Out',
            'Allocations Out',
            'Total Out',
            'Closing Balance',
        ];
    }

    public function map($row): array
    {
        return [
            $row->resource_name,
            $row->sku,
            $row->category,
            $row->unit,
            number_format($row->opening_balance, 2),
            number_format($row->purchases, 2),
            number_format($row->allocations_in, 2),
            number_format($row->transfers_in, 2),
            number_format($row->total_in, 2),
            number_format($row->consumption, 2),
            number_format($row->transfers_out, 2),
            number_format($row->allocations_out, 2),
            number_format($row->total_out, 2),
            number_format($row->closing_balance, 2),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        $project = Project::find($this->projectId);
        return substr($project->code . ' ' . $this->date, 0, 31);
    }
}
