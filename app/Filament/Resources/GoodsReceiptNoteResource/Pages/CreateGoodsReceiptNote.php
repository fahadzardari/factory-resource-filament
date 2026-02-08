<?php

namespace App\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Filament\Resources\GoodsReceiptNoteResource;
use App\Models\GoodsReceiptNote;
use App\Services\InventoryTransactionService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateGoodsReceiptNote extends CreateRecord
{
    protected static string $resource = GoodsReceiptNoteResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Author of the GRN
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function afterSave(): void
    {
        // Get the created record
        $grn = $this->record;

        // Automatically record the goods receipt in inventory transactions
        try {
            $service = app(InventoryTransactionService::class);
            $transaction = $service->recordGoodsReceipt($grn, Auth::user());

            Notification::make()
                ->title('Goods Receipt Recorded')
                ->body("GRN {$grn->grn_number} - {$grn->quantity_received} {$grn->resource->base_unit} of {$grn->resource->name} added to hub stock")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Warning')
                ->body('GRN created but inventory transaction failed: ' . $e->getMessage())
                ->warning()
                ->send();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Goods Receipt Note Created';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
