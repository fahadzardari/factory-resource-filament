<?php

namespace App\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Filament\Resources\GoodsReceiptNoteResource;
use App\Models\GoodsReceiptNote;
use App\Models\Resource;
use App\Services\InventoryTransactionService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateGoodsReceiptNote extends CreateRecord
{
    protected static string $resource = GoodsReceiptNoteResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    /**
     * CRITICAL: This method ensures transactions are created AFTER everything is saved.
     * Uses DB::afterCommit() to wait for the database transaction to complete.
     */
    protected function afterCreate(): void
    {
        $recordId = $this->record->id;

        // Use DB::afterCommit() to ensure this runs AFTER the entire database transaction completes
        // This is crucial because Filament saves repeater items in the same transaction
        DB::afterCommit(function () use ($recordId) {
            // Small delay to ensure database has fully committed
            usleep(100000); // 100ms delay
            
            // Reload the GRN from database with all relationships
            $grn = GoodsReceiptNote::with(['lineItems.resource', 'project', 'supplier'])
                ->find($recordId);

            if (!$grn) {
                Log::error("CreateGRN: Could not find GRN ID {$recordId} after creation");
                return;
            }

            $lineItemCount = $grn->lineItems->count();

            // Check if line items were saved
            if ($lineItemCount === 0) {
                Log::warning("GRN {$grn->grn_number}: No line items found after creation");
                
                Notification::make()
                    ->warning()
                    ->title('⚠️ No Items Added')
                    ->body('GRN created but no line items were saved.')
                    ->send();
                return;
            }

            // Check if transactions already exist (prevent duplicates)
            $existingCount = $grn->inventoryTransactions()->count();
            if ($existingCount > 0) {
                Log::info("GRN {$grn->grn_number}: {$existingCount} transactions already exist");
                return;
            }

            // Create inventory transactions
            try {
                $service = app(InventoryTransactionService::class);
                $user = Auth::user();
                
                if (!$user) {
                    Log::error("GRN {$grn->grn_number}: No authenticated user found");
                    return;
                }

                $transactions = $service->recordGoodsReceipt($grn, $user);
                $transactionCount = count($transactions);
                $totalValue = $grn->lineItems->sum('total_value');

                // Determine destination
                $destination = $grn->project_id && $grn->project
                    ? $grn->project->name
                    : "Central Warehouse";

                Log::info(
                    "GRN {$grn->grn_number}: ✅ Successfully created {$transactionCount} inventory transactions",
                    [
                        'grn_id' => $grn->id,
                        'grn_number' => $grn->grn_number,
                        'line_items' => $lineItemCount,
                        'transactions' => $transactionCount,
                        'total_value' => $totalValue,
                        'project_id' => $grn->project_id,
                        'destination' => $destination,
                    ]
                );

                Notification::make()
                    ->success()
                    ->title('✅ GRN Created & Resources Allocated!')
                    ->body("{$lineItemCount} item(s) → {$destination} | Total: AED " . number_format($totalValue, 2) . " | {$transactionCount} transactions created")
                    ->duration(8000)
                    ->send();

            } catch (\Exception $e) {
                Log::error(
                    "GRN {$grn->grn_number}: ❌ FAILED to create inventory transactions",
                    [
                        'grn_id' => $grn->id,
                        'grn_number' => $grn->grn_number,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
                
                Notification::make()
                    ->danger()
                    ->title('❌ ALLOCATION FAILED')
                    ->body('GRN created but automatic resource allocation FAILED. Error: ' . $e->getMessage())
                    ->persistent()
                    ->send();
            }
        });
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        // Suppress default notification - we show custom one with allocation details
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
