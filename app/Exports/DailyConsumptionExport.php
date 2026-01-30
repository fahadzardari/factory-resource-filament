<?php

namespace App\Exports;

use App\Models\InventoryTransaction;
use App\Models\Project;
use App\Services\StockCalculator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

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
        // Get all resources that have EVER been at this project (have opening balance or day transactions)
        $allProjectTransactions = InventoryTransaction::query()
            ->where('project_id', $this->projectId)
            ->whereDate('transaction_date', '<=', $this->date)
            ->with(['resource'])
            ->get();

        // Get unique resources
        $resourceIds = $allProjectTransactions->pluck('resource_id')->unique();
        
        // Get transactions for this specific day
        $dayTransactions = InventoryTransaction::query()
            ->where('project_id', $this->projectId)
            ->whereDate('transaction_date', $this->date)
            ->with(['resource'])
            ->get();
        
        // Calculate opening balance (all transactions before this date)
        $openingBalances = InventoryTransaction::query()
            ->where('project_id', $this->projectId)
            ->whereDate('transaction_date', '<', $this->date)
            ->selectRaw('resource_id, SUM(quantity) as opening_balance')
            ->groupBy('resource_id')
            ->pluck('opening_balance', 'resource_id');

        // Build result collection
        $result = collect();
        
        // Summary totals
        $summaryOpeningBalance = 0;
        $summaryPurchases = 0;
        $summaryAllocationsIn = 0;
        $summaryTransfersIn = 0;
        $summaryTotalIn = 0;
        $summaryConsumption = 0;
        $summaryTransfersOut = 0;
        $summaryAllocationsOut = 0;
        $summaryTotalOut = 0;
        $summaryClosingBalance = 0;
        
        foreach ($resourceIds as $resourceId) {
            $resourceTransactions = $dayTransactions->where('resource_id', $resourceId);
            $resource = $allProjectTransactions->where('resource_id', $resourceId)->first()->resource;
            
            $openingBalance = $openingBalances->get($resourceId, 0);
            
            // Only include resources that have stock at start of day or activity during day
            if ($openingBalance == 0 && $resourceTransactions->isEmpty()) {
                continue;
            }
            
            $purchases = $resourceTransactions->where('transaction_type', 'PURCHASE')->sum('quantity');
            $allocations = $resourceTransactions->where('transaction_type', 'ALLOCATION_IN')->sum('quantity');
            $transfersIn = $resourceTransactions->where('transaction_type', 'TRANSFER_IN')->sum('quantity');
            $consumption = abs($resourceTransactions->where('transaction_type', 'CONSUMPTION')->sum('quantity'));
            $transfersOut = abs($resourceTransactions->where('transaction_type', 'TRANSFER_OUT')->sum('quantity'));
            $allocationsOut = abs($resourceTransactions->where('transaction_type', 'ALLOCATION_OUT')->sum('quantity'));
            
            $totalIn = $purchases + $allocations + $transfersIn;
            $totalOut = $consumption + $transfersOut + $allocationsOut;
            $closingBalance = $openingBalance + $totalIn - $totalOut;

            // Add to summary
            $summaryOpeningBalance += $openingBalance;
            $summaryPurchases += $purchases;
            $summaryAllocationsIn += $allocations;
            $summaryTransfersIn += $transfersIn;
            $summaryTotalIn += $totalIn;
            $summaryConsumption += $consumption;
            $summaryTransfersOut += $transfersOut;
            $summaryAllocationsOut += $allocationsOut;
            $summaryTotalOut += $totalOut;
            $summaryClosingBalance += $closingBalance;

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
                'is_summary' => false,
            ]);
        }

        // Add summary row
        if ($result->isNotEmpty()) {
            $result->push((object)[
                'resource_name' => 'TOTAL SUMMARY',
                'sku' => '',
                'category' => '',
                'unit' => '',
                'opening_balance' => $summaryOpeningBalance,
                'purchases' => $summaryPurchases,
                'allocations_in' => $summaryAllocationsIn,
                'transfers_in' => $summaryTransfersIn,
                'total_in' => $summaryTotalIn,
                'consumption' => $summaryConsumption,
                'transfers_out' => $summaryTransfersOut,
                'allocations_out' => $summaryAllocationsOut,
                'total_out' => $summaryTotalOut,
                'closing_balance' => $summaryClosingBalance,
                'is_summary' => true,
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
        $lastRow = $sheet->getHighestRow();
        
        return [
            // Header row bold
            1 => ['font' => ['bold' => true]],
            // Summary row bold with background
            $lastRow => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F5E9']
                ]
            ],
        ];
    }

    public function title(): string
    {
        $project = Project::find($this->projectId);
        return substr($project->code . ' ' . $this->date, 0, 31);
    }
}
