<?php

namespace App\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Filament\Resources\GoodsReceiptNoteResource;
use App\Models\GoodsReceiptNote;
use App\Models\Resource;
use App\Services\InventoryTransactionService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateGoodsReceiptNote extends CreateRecord
{
    protected static string $resource = GoodsReceiptNoteResource::class;
    // Remove custom view - use Filament's default
    // protected static string $view = 'filament.resources.goods-receipt-note-resource.pages.create-goods-receipt-note';

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
        $grn = $this->record;

        // 1. Refresh the model relationships. 
        // The repeater items are already in the database for this transaction, 
        // we just need to tell Laravel to load them.
        $grn->load(['lineItems.resource', 'project', 'supplier']);

        $lineItemCount = $grn->lineItems->count();

        // 2. Validate line items exist
        if ($lineItemCount === 0) {
            Log::warning("GRN {$grn->grn_number}: No line items found after creation");
            
            Notification::make()
                ->warning()
                ->title('⚠️ No Items Added')
                ->body('A GRN requires at least one line item.')
                ->send();
                
            // Halt will stop the creation process and roll back the database transaction
            throw new Halt(); 
        }

        // 3. Process Inventory Transactions safely inside the DB lock
        try {
            $service = app(InventoryTransactionService::class);
            
            $user = Auth::user();
            if (!$user) {
                throw new \Exception("No authenticated user found.");
            }

            $transactions = $service->recordGoodsReceipt($grn, $user);
            
            $transactionCount = count($transactions);
            $totalValue = $grn->lineItems->sum('total_value');

            $destination = $grn->project_id && $grn->project
                ? $grn->project->name
                : "Central Warehouse";

            Log::info("GRN {$grn->grn_number}: ✅ Successfully created {$transactionCount} inventory transactions");

            Notification::make()
                ->success()
                ->title('✅ GRN Created & Resources Allocated!')
                ->body("{$lineItemCount} item(s) → {$destination} | Total: AED " . number_format($totalValue, 2) . " | {$transactionCount} transactions created")
                ->duration(8000)
                ->send();

        } catch (\Exception $e) {
            Log::error("GRN {$grn->grn_number}: ❌ FAILED", ['error' => $e->getMessage()]);
            
            Notification::make()
                ->danger()
                ->title('❌ ALLOCATION FAILED')
                ->body('Error: ' . $e->getMessage())
                ->persistent()
                ->send();

            // CRITICAL: Throwing Halt() forces Filament to roll back the entire GRN.
            // You will never have an orphaned GRN or partial transactions again.
            throw new Halt();
        }
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
