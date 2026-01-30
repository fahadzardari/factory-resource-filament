<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\Project;
use App\Models\InventoryTransaction;
use Illuminate\Support\Collection;

class ReportingService
{
    public function __construct(
        private StockCalculator $stockCalculator
    ) {}

    /**
     * Generate comprehensive daily report for all resources
     *
     * @param string $date
     * @param int|null $projectId
     * @return Collection
     */
    public function generateDailyReportForAllResources(string $date, ?int $projectId = null): Collection
    {
        $resources = Resource::all();

        return $resources->map(function ($resource) use ($date, $projectId) {
            return $this->stockCalculator->getDailyReport($resource, $date, $projectId);
        })->filter(function ($report) {
            // Only include resources that have had any activity
            return $report['opening_balance'] > 0 
                || $report['total_in'] > 0 
                || $report['total_out'] > 0;
        });
    }

    /**
     * Generate summary report for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function generatePeriodSummary(string $startDate, string $endDate, ?int $projectId = null): array
    {
        $query = InventoryTransaction::whereBetween('transaction_date', [$startDate, $endDate]);

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        $transactions = $query->with(['resource', 'project', 'createdBy'])->get();

        // Group by transaction type
        $purchases = $transactions->where('transaction_type', InventoryTransaction::TYPE_PURCHASE);
        $consumptions = $transactions->where('transaction_type', InventoryTransaction::TYPE_CONSUMPTION);
        $allocationsOut = $transactions->where('transaction_type', InventoryTransaction::TYPE_ALLOCATION_OUT);
        $allocationsIn = $transactions->where('transaction_type', InventoryTransaction::TYPE_ALLOCATION_IN);
        $transfersOut = $transactions->where('transaction_type', InventoryTransaction::TYPE_TRANSFER_OUT);
        $transfersIn = $transactions->where('transaction_type', InventoryTransaction::TYPE_TRANSFER_IN);

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'project_id' => $projectId,
            'summary' => [
                'total_purchases' => $purchases->sum('quantity'),
                'total_purchases_value' => $purchases->sum('total_value'),
                'total_consumptions' => abs($consumptions->sum('quantity')),
                'total_consumptions_value' => abs($consumptions->sum('total_value')),
                'total_allocations_out' => abs($allocationsOut->sum('quantity')),
                'total_allocations_in' => $allocationsIn->sum('quantity'),
                'total_transfers_out' => abs($transfersOut->sum('quantity')),
                'total_transfers_in' => $transfersIn->sum('quantity'),
            ],
            'transactions' => $transactions,
        ];
    }

    /**
     * Generate inventory valuation report
     *
     * @param string $date
     * @param int|null $projectId
     * @return array
     */
    public function generateInventoryValuationReport(string $date, ?int $projectId = null): array
    {
        $resources = Resource::all();

        $valuation = $resources->map(function ($resource) use ($date, $projectId) {
            $closing = $this->stockCalculator->getClosingBalance($resource, $date, $projectId);
            
            if ($closing <= 0) {
                return null;
            }

            // Calculate weighted average price
            $query = InventoryTransaction::where('resource_id', $resource->id)
                ->where('transaction_date', '<=', $date);

            if ($projectId === null) {
                $query->whereNull('project_id');
            } else {
                $query->where('project_id', $projectId);
            }

            $totalValue = (float) $query->sum('total_value');
            $avgPrice = $closing > 0 ? $totalValue / $closing : 0;

            return [
                'resource' => $resource,
                'quantity' => $closing,
                'average_price' => $avgPrice,
                'total_value' => $totalValue,
            ];
        })->filter()->values();

        return [
            'date' => $date,
            'project_id' => $projectId,
            'items' => $valuation,
            'total_value' => $valuation->sum('total_value'),
            'total_items' => $valuation->count(),
        ];
    }

    /**
     * Generate low stock alert report
     *
     * @param float $threshold (minimum quantity)
     * @param int|null $projectId
     * @return Collection
     */
    public function generateLowStockReport(float $threshold = 10, ?int $projectId = null): Collection
    {
        $resources = Resource::all();

        return $resources->map(function ($resource) use ($threshold, $projectId) {
            $query = InventoryTransaction::where('resource_id', $resource->id);

            if ($projectId === null) {
                $query->whereNull('project_id');
            } else {
                $query->where('project_id', $projectId);
            }

            $currentStock = (float) $query->sum('quantity');

            if ($currentStock <= $threshold && $currentStock >= 0) {
                return [
                    'resource' => $resource,
                    'current_stock' => $currentStock,
                    'threshold' => $threshold,
                    'status' => $currentStock == 0 ? 'OUT_OF_STOCK' : 'LOW_STOCK',
                ];
            }

            return null;
        })->filter()->values();
    }

    /**
     * Generate project consumption report
     *
     * @param Project $project
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function generateProjectConsumptionReport(Project $project, string $startDate, string $endDate): array
    {
        $consumptions = InventoryTransaction::where('project_id', $project->id)
            ->where('transaction_type', InventoryTransaction::TYPE_CONSUMPTION)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('resource')
            ->get();

        $byResource = $consumptions->groupBy('resource_id')->map(function ($transactions, $resourceId) {
            $resource = $transactions->first()->resource;
            return [
                'resource' => $resource,
                'total_consumed' => abs($transactions->sum('quantity')),
                'total_value' => abs($transactions->sum('total_value')),
                'consumption_count' => $transactions->count(),
            ];
        })->values();

        return [
            'project' => $project,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'by_resource' => $byResource,
            'total_consumed_value' => abs($consumptions->sum('total_value')),
            'total_transactions' => $consumptions->count(),
        ];
    }

    /**
     * Get resource movement audit trail
     *
     * @param Resource $resource
     * @param int $days (number of days to look back)
     * @return Collection
     */
    public function getResourceAuditTrail(Resource $resource, int $days = 30): Collection
    {
        $startDate = now()->subDays($days)->format('Y-m-d');

        return InventoryTransaction::where('resource_id', $resource->id)
            ->where('transaction_date', '>=', $startDate)
            ->with(['project', 'createdBy'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
