<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Resource;
use App\Models\Project;
use Carbon\Carbon;

class StockCalculator
{
    /**
     * Get opening balance (stock at start of day)
     *
     * @param Resource $resource
     * @param string $date
     * @param int|null $projectId
     * @return float
     */
    public function getOpeningBalance(Resource $resource, string $date, ?int $projectId = null): float
    {
        $query = InventoryTransaction::where('resource_id', $resource->id)
            ->whereDate('transaction_date', '<', $date);

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Get closing balance (stock at end of day)
     *
     * @param Resource $resource
     * @param string $date
     * @param int|null $projectId
     * @return float
     */
    public function getClosingBalance(Resource $resource, string $date, ?int $projectId = null): float
    {
        $query = InventoryTransaction::where('resource_id', $resource->id)
            ->whereDate('transaction_date', '<=', $date);

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Get total IN movements for a specific date
     *
     * @param Resource $resource
     * @param string $date
     * @param int|null $projectId
     * @return float
     */
    public function getTotalIn(Resource $resource, string $date, ?int $projectId = null): float
    {
        $query = InventoryTransaction::where('resource_id', $resource->id)
            ->whereDate('transaction_date', $date)
            ->where('quantity', '>', 0); // Positive = incoming

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Get total OUT movements for a specific date
     *
     * @param Resource $resource
     * @param string $date
     * @param int|null $projectId
     * @return float
     */
    public function getTotalOut(Resource $resource, string $date, ?int $projectId = null): float
    {
        $query = InventoryTransaction::where('resource_id', $resource->id)
            ->whereDate('transaction_date', $date)
            ->where('quantity', '<', 0); // Negative = outgoing

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        return abs((float) $query->sum('quantity')); // Return positive number
    }

    /**
     * Get daily report data for a specific date
     *
     * @param Resource $resource
     * @param string $date
     * @param int|null $projectId
     * @return array
     */
    public function getDailyReport(Resource $resource, string $date, ?int $projectId = null): array
    {
        $opening = $this->getOpeningBalance($resource, $date, $projectId);
        $totalIn = $this->getTotalIn($resource, $date, $projectId);
        $totalOut = $this->getTotalOut($resource, $date, $projectId);
        $closing = $opening + $totalIn - $totalOut;

        // Get transactions for the day
        $query = InventoryTransaction::where('resource_id', $resource->id)
            ->whereDate('transaction_date', $date);

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        $transactions = $query->orderBy('created_at')->get();

        return [
            'date' => $date,
            'resource' => $resource,
            'project_id' => $projectId,
            'opening_balance' => $opening,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'closing_balance' => $closing,
            'transactions' => $transactions,
        ];
    }

    /**
     * Get inventory valuation at a specific date
     *
     * @param Resource|null $resource (null = all resources)
     * @param string $date
     * @param int|null $projectId
     * @return array
     */
    public function getInventoryValuation(?Resource $resource, string $date, ?int $projectId = null): array
    {
        $query = InventoryTransaction::where('transaction_date', '<=', $date);

        if ($resource) {
            $query->where('resource_id', $resource->id);
        }

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        $results = $query->selectRaw('
                resource_id, 
                SUM(quantity) as total_quantity, 
                SUM(total_value) as total_value
            ')
            ->groupBy('resource_id')
            ->having('total_quantity', '>', 0)
            ->with('resource')
            ->get();

        $totalValue = $results->sum('total_value');
        $totalQuantity = $results->sum('total_quantity');

        return [
            'date' => $date,
            'items' => $results,
            'total_value' => $totalValue,
            'total_quantity' => $totalQuantity,
        ];
    }

    /**
     * Get stock movements between two dates
     *
     * @param Resource $resource
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMovementHistory(
        Resource $resource,
        string $startDate,
        string $endDate,
        ?int $projectId = null
    ) {
        $query = InventoryTransaction::where('resource_id', $resource->id)
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        return $query->orderBy('transaction_date')
            ->orderBy('created_at')
            ->with(['createdBy', 'project'])
            ->get();
    }

    /**
     * Get stock level at a specific point in time (for time-travel queries)
     *
     * @param Resource $resource
     * @param string $datetime
     * @param int|null $projectId
     * @return float
     */
    public function getStockAtDateTime(Resource $resource, string $datetime, ?int $projectId = null): float
    {
        $query = InventoryTransaction::where('resource_id', $resource->id)
            ->where('created_at', '<=', $datetime);

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Get current stock of a resource at the Hub (Central warehouse)
     *
     * @param int $resourceId
     * @return float
     */
    public static function getHubStock(int $resourceId): float
    {
        return (float) InventoryTransaction::where('resource_id', $resourceId)
            ->whereNull('project_id')
            ->sum('quantity');
    }

    /**
     * Get all resources with their current stock at a specific project
     *
     * @param int $projectId
     * @return array
     */
    public static function getProjectResourceStocks(int $projectId): array
    {
        $transactions = InventoryTransaction::where('project_id', $projectId)
            ->with('resource')
            ->get()
            ->groupBy('resource_id');

        $stocks = [];
        foreach ($transactions as $resourceId => $resourceTransactions) {
            $quantity = $resourceTransactions->sum('quantity');
            
            // Only include resources with positive stock
            if ($quantity > 0) {
                $resource = $resourceTransactions->first()->resource;
                $stocks[] = [
                    'resource_id' => $resourceId,
                    'resource_name' => $resource->name,
                    'sku' => $resource->sku,
                    'quantity' => $quantity,
                    'unit' => $resource->base_unit,
                ];
            }
        }

        return $stocks;
    }
}
