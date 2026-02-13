<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\GoodsReceiptNote;
use App\Models\Resource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
            'created_by' => $user?->id ?? Auth::id(),
        ]);
    }

    /**
     * Record Goods Received from GRN
     * Creates GOODS_RECEIPT or ALLOCATION_IN transaction for each line item
     * If GRN has project_id, items are allocated directly to project
     * Otherwise, items go to Hub
     *
     * @param GoodsReceiptNote $grn
     * @param User|null $user
     * @return array Array of created InventoryTransaction records
     */
    public function recordGoodsReceipt(
        GoodsReceiptNote $grn,
        ?User $user = null
    ): array {
        // Validate GRN has required fields and line items
        if (!$grn->supplier_id || !$grn->lineItems || $grn->lineItems->isEmpty()) {
            throw new InvalidArgumentException('GRN must have supplier and at least one line item.');
        }

        $transactions = [];

        // Process each line item
        foreach ($grn->lineItems as $lineItem) {
            // Validate line item
            if (!$lineItem->resource_id || $lineItem->quantity_received <= 0) {
                throw new InvalidArgumentException('Each line item must have a resource and positive quantity.');
            }

            // Convert receipt quantity to base unit
            $baseQuantity = $lineItem->getBaseQuantity();
            if ($baseQuantity <= 0) {
                throw new InvalidArgumentException("Invalid unit conversion for resource {$lineItem->resource_id}");
            }

            // Calculate base unit price (price per base unit)
            $conversionFactor = $lineItem->getConversionFactor($lineItem->receipt_unit, $lineItem->resource->base_unit);
            $baseUnitPrice = $lineItem->unit_price / $conversionFactor;
            $totalValue = $baseQuantity * $baseUnitPrice;

            // IMPORTANT: Always create GOODS_RECEIPT to Central Hub first
            // This ensures items are in the system before allocation
            $goodsReceiptTransaction = InventoryTransaction::create([
                'resource_id' => $lineItem->resource_id,
                'project_id' => null, // Always to Hub
                'transaction_type' => InventoryTransaction::TYPE_GOODS_RECEIPT,
                'quantity' => $baseQuantity,
                'unit_price' => $baseUnitPrice,
                'total_value' => $totalValue,
                'transaction_date' => $grn->receipt_date,
                'supplier' => $grn->supplier->name,
                'grn_id' => $grn->id,
                'notes' => ($grn->notes ? $grn->notes . ' | ' : '') . "Receipt: {$lineItem->quantity_received}{$lineItem->receipt_unit}",
                'created_by' => $user?->id ?? Auth::id(),
            ]);

            $transactions[] = $goodsReceiptTransaction;

            // If GRN has project allocation, create allocation pair
            if ($grn->project_id) {
                try {
                    // ALLOCATION_IN to project
                    $allocationInTransaction = InventoryTransaction::create([
                        'resource_id' => $lineItem->resource_id,
                        'project_id' => $grn->project_id,
                        'transaction_type' => InventoryTransaction::TYPE_ALLOCATION_IN,
                        'quantity' => $baseQuantity,
                        'unit_price' => $baseUnitPrice,
                        'total_value' => $totalValue,
                        'transaction_date' => $grn->receipt_date,
                        'grn_id' => $grn->id,
                        'notes' => "Direct allocation from GRN {$grn->grn_number} to project",
                        'created_by' => $user?->id ?? Auth::id(),
                    ]);

                    $transactions[] = $allocationInTransaction;

                    // ALLOCATION_OUT from Hub (removes from hub inventory)
                    $allocationOutTransaction = InventoryTransaction::create([
                        'resource_id' => $lineItem->resource_id,
                        'project_id' => null, // From Hub
                        'transaction_type' => InventoryTransaction::TYPE_ALLOCATION_OUT,
                        'quantity' => -$baseQuantity,
                        'unit_price' => $baseUnitPrice,
                        'total_value' => -$totalValue,
                        'transaction_date' => $grn->receipt_date,
                        'notes' => "Allocation OUT from Hub to {$grn->project->name} for GRN {$grn->grn_number}",
                        'created_by' => $user?->id ?? Auth::id(),
                    ]);

                    $transactions[] = $allocationOutTransaction;
                } catch (\Exception $e) {
                    throw new InvalidArgumentException(
                        "Failed to create allocation for {$lineItem->resource->name}: " . $e->getMessage()
                    );
                }
            }
        }

        return $transactions;
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
                'created_by' => $user?->id ?? Auth::id(),
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
                'created_by' => $user?->id ?? Auth::id(),
            ]);

            return [$outTransaction, $inTransaction];
        });
    }

    /**
     * Record consumption of stock at a project or directly from hub
     * Stock is "destroyed" - it leaves the system permanently
     *
     * @param Resource $resource
     * @param float $quantity (in base unit)
     * @param string $transaction_date
     * @param Project|null $project (null = direct consumption from hub, not project-specific)
     * @param string|null $reason (e.g., "Maintenance", "Testing", "Scrap", custom text)
     * @param string|null $notes
     * @param User|null $user
     * @return InventoryTransaction
     */
    public function recordConsumption(
        Resource $resource,
        float $quantity,
        string $transactionDate,
        ?Project $project = null,
        ?string $reason = null,
        ?string $notes = null,
        ?User $user = null
    ): InventoryTransaction {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Consumption quantity must be positive.');
        }

        // Check sufficient stock at location
        $projectId = $project?->id;
        $currentStock = $this->getCurrentStock($resource, $projectId);
        if ($currentStock < $quantity) {
            $location = $projectId ? "project" : "hub";
            throw new InvalidArgumentException(
                "Insufficient stock at {$location}. Available: {$currentStock}, Requested: {$quantity}"
            );
        }

        // Get weighted average price from location
        $weightedAvgPrice = $this->getWeightedAveragePrice($resource, $projectId);

        // Determine transaction type
        $transactionType = $projectId 
            ? InventoryTransaction::TYPE_CONSUMPTION
            : InventoryTransaction::TYPE_DIRECT_CONSUMPTION;

        // Build consumption notes
        if ($reason && !$notes) {
            $notes = "Reason: {$reason}";
        } elseif ($reason && $notes) {
            $notes = "Reason: {$reason} | {$notes}";
        }

        return InventoryTransaction::create([
            'resource_id' => $resource->id,
            'project_id' => $projectId, // null for direct, or project id
            'transaction_type' => $transactionType,
            'quantity' => -$quantity, // Negative (leaving system)
            'unit_price' => $weightedAvgPrice,
            'total_value' => -($quantity * $weightedAvgPrice),
            'transaction_date' => $transactionDate,
            'consumption_reason' => $reason,
            'notes' => $notes,
            'created_by' => $user?->id ?? Auth::id(),
        ]);
    }

    /**
     * Transfer stock from one project to another, or back to Hub
     * Creates two transactions: TRANSFER_OUT and TRANSFER_IN
     *
     * @param Resource $resource
     * @param Project $fromProject
     * @param Project|null $toProject (null means transfer back to Hub)
     * @param float $quantity (in base unit)
     * @param string $transaction_date
     * @param string|null $notes
     * @param User|null $user
     * @return array [outTransaction, inTransaction]
     */
    public function recordTransfer(
        Resource $resource,
        Project $fromProject,
        ?Project $toProject,
        float $quantity,
        string $transactionDate,
        ?string $notes = null,
        ?User $user = null
    ): array {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Transfer quantity must be positive.');
        }

        if ($toProject && $fromProject->id === $toProject->id) {
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
                'created_by' => $user?->id ?? Auth::id(),
            ]);

            // IN to destination project (or Hub if toProject is null)
            $inTransaction = InventoryTransaction::create([
                'resource_id' => $resource->id,
                'project_id' => $toProject?->id, // null means Hub
                'transaction_type' => InventoryTransaction::TYPE_TRANSFER_IN,
                'quantity' => $quantity,
                'unit_price' => $weightedAvgPrice,
                'total_value' => $quantity * $weightedAvgPrice,
                'transaction_date' => $transactionDate,
                'notes' => $notes,
                'created_by' => $user?->id ?? Auth::id(),
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
