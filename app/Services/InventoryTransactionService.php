<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Resource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryTransactionService
{
    /**
     * Record a purchase of stock into the Central Hub
     *
     * @param Resource $resource
     * @param float $quantity (in base unit)
     * @param float $unit_price (price per base unit)
     * @param string $transaction_date
     * @param string|null $supplier
     * @param string|null $invoice_number
     * @param string|null $notes
     * @param User|null $user
     * @return InventoryTransaction
     */
    public function recordPurchase(
        Resource $resource,
        float $quantity,
        float $unitPrice,
        string $transactionDate,
        ?string $supplier = null,
        ?string $invoiceNumber = null,
        ?string $notes = null,
        ?User $user = null
    ): InventoryTransaction {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Purchase quantity must be positive.');
        }

        if ($unitPrice < 0) {
            throw new InvalidArgumentException('Unit price cannot be negative.');
        }

        return InventoryTransaction::create([
            'resource_id' => $resource->id,
            'project_id' => null, // Hub
            'transaction_type' => InventoryTransaction::TYPE_PURCHASE,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_value' => $quantity * $unitPrice,
            'transaction_date' => $transactionDate,
            'supplier' => $supplier,
            'invoice_number' => $invoiceNumber,
            'notes' => $notes,
            'created_by' => $user?->id ?? auth()->id(),
        ]);
    }

    /**
     * Allocate stock from Central Hub to a Project
     * Creates two transactions: ALLOCATION_OUT from hub, ALLOCATION_IN to project
     *
     * @param Resource $resource
     * @param Project $project
     * @param float $quantity (in base unit)
     * @param string $transaction_date
     * @param string|null $notes
     * @param User|null $user
     * @return array [outTransaction, inTransaction]
     */
    public function recordAllocation(
        Resource $resource,
        Project $project,
        float $quantity,
        string $transactionDate,
        ?string $notes = null,
        ?User $user = null
    ): array {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Allocation quantity must be positive.');
        }

        // Check hub has sufficient stock
        $hubStock = $this->getCurrentStock($resource, null);
        if ($hubStock < $quantity) {
            throw new InvalidArgumentException(
                "Insufficient stock in Central Hub. Available: {$hubStock}, Requested: {$quantity}"
            );
        }

        // Get weighted average price from hub
        $weightedAvgPrice = $this->getWeightedAveragePrice($resource, null);

        // Create both transactions atomically
        return DB::transaction(function () use (
            $resource,
            $project,
            $quantity,
            $transactionDate,
            $weightedAvgPrice,
            $notes,
            $user
        ) {
            // OUT from Hub (negative quantity)
            $outTransaction = InventoryTransaction::create([
                'resource_id' => $resource->id,
                'project_id' => null,
                'transaction_type' => InventoryTransaction::TYPE_ALLOCATION_OUT,
                'quantity' => -$quantity,
                'unit_price' => $weightedAvgPrice,
                'total_value' => -($quantity * $weightedAvgPrice),
                'transaction_date' => $transactionDate,
                'notes' => $notes,
                'created_by' => $user?->id ?? auth()->id(),
            ]);

            // IN to Project (positive quantity)
            $inTransaction = InventoryTransaction::create([
                'resource_id' => $resource->id,
                'project_id' => $project->id,
                'transaction_type' => InventoryTransaction::TYPE_ALLOCATION_IN,
                'quantity' => $quantity,
                'unit_price' => $weightedAvgPrice,
                'total_value' => $quantity * $weightedAvgPrice,
                'transaction_date' => $transactionDate,
                'notes' => $notes,
                'created_by' => $user?->id ?? auth()->id(),
            ]);

            return [$outTransaction, $inTransaction];
        });
    }

    /**
     * Record consumption of stock at a project
     * Stock is "destroyed" - it leaves the system permanently
     *
     * @param Resource $resource
     * @param Project $project
     * @param float $quantity (in base unit)
     * @param string $transaction_date
     * @param string|null $reference_type
     * @param int|null $reference_id
     * @param string|null $notes
     * @param User|null $user
     * @return InventoryTransaction
     */
    public function recordConsumption(
        Resource $resource,
        Project $project,
        float $quantity,
        string $transactionDate,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?User $user = null
    ): InventoryTransaction {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Consumption quantity must be positive.');
        }

        // Check project has sufficient stock
        $projectStock = $this->getCurrentStock($resource, $project->id);
        if ($projectStock < $quantity) {
            throw new InvalidArgumentException(
                "Insufficient stock at project. Available: {$projectStock}, Requested: {$quantity}"
            );
        }

        // Get weighted average price from project
        $weightedAvgPrice = $this->getWeightedAveragePrice($resource, $project->id);

        return InventoryTransaction::create([
            'resource_id' => $resource->id,
            'project_id' => $project->id,
            'transaction_type' => InventoryTransaction::TYPE_CONSUMPTION,
            'quantity' => -$quantity, // Negative (leaving system)
            'unit_price' => $weightedAvgPrice,
            'total_value' => -($quantity * $weightedAvgPrice),
            'transaction_date' => $transactionDate,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $user?->id ?? auth()->id(),
        ]);
    }

    /**
     * Transfer stock from one project to another
     * Creates two transactions: TRANSFER_OUT and TRANSFER_IN
     *
     * @param Resource $resource
     * @param Project $fromProject
     * @param Project $toProject
     * @param float $quantity (in base unit)
     * @param string $transaction_date
     * @param string|null $notes
     * @param User|null $user
     * @return array [outTransaction, inTransaction]
     */
    public function recordTransfer(
        Resource $resource,
        Project $fromProject,
        Project $toProject,
        float $quantity,
        string $transactionDate,
        ?string $notes = null,
        ?User $user = null
    ): array {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Transfer quantity must be positive.');
        }

        if ($fromProject->id === $toProject->id) {
            throw new InvalidArgumentException('Cannot transfer to the same project.');
        }

        // Check source project has sufficient stock
        $sourceStock = $this->getCurrentStock($resource, $fromProject->id);
        if ($sourceStock < $quantity) {
            throw new InvalidArgumentException(
                "Insufficient stock at source project. Available: {$sourceStock}, Requested: {$quantity}"
            );
        }

        // Get weighted average price from source project
        $weightedAvgPrice = $this->getWeightedAveragePrice($resource, $fromProject->id);

        // Create both transactions atomically
        return DB::transaction(function () use (
            $resource,
            $fromProject,
            $toProject,
            $quantity,
            $transactionDate,
            $weightedAvgPrice,
            $notes,
            $user
        ) {
            // OUT from source project
            $outTransaction = InventoryTransaction::create([
                'resource_id' => $resource->id,
                'project_id' => $fromProject->id,
                'transaction_type' => InventoryTransaction::TYPE_TRANSFER_OUT,
                'quantity' => -$quantity,
                'unit_price' => $weightedAvgPrice,
                'total_value' => -($quantity * $weightedAvgPrice),
                'transaction_date' => $transactionDate,
                'notes' => $notes,
                'created_by' => $user?->id ?? auth()->id(),
            ]);

            // IN to destination project
            $inTransaction = InventoryTransaction::create([
                'resource_id' => $resource->id,
                'project_id' => $toProject->id,
                'transaction_type' => InventoryTransaction::TYPE_TRANSFER_IN,
                'quantity' => $quantity,
                'unit_price' => $weightedAvgPrice,
                'total_value' => $quantity * $weightedAvgPrice,
                'transaction_date' => $transactionDate,
                'notes' => $notes,
                'created_by' => $user?->id ?? auth()->id(),
            ]);

            return [$outTransaction, $inTransaction];
        });
    }

    /**
     * Get current stock at a location
     *
     * @param Resource $resource
     * @param int|null $projectId (null = Hub, int = Project)
     * @return float
     */
    public function getCurrentStock(Resource $resource, ?int $projectId = null): float
    {
        $query = InventoryTransaction::where('resource_id', $resource->id);

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Get weighted average price at a location
     *
     * @param Resource $resource
     * @param int|null $projectId (null = Hub, int = Project)
     * @return float
     */
    public function getWeightedAveragePrice(Resource $resource, ?int $projectId = null): float
    {
        $query = InventoryTransaction::where('resource_id', $resource->id);

        if ($projectId === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $projectId);
        }

        $totalQuantity = (float) $query->sum('quantity');
        
        if ($totalQuantity <= 0) {
            return 0;
        }

        $totalValue = (float) $query->sum('total_value');
        
        return $totalValue / $totalQuantity;
    }
}
