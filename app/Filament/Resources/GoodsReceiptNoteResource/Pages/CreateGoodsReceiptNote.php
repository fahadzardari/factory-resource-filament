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

class CreateGoodsReceiptNote extends CreateRecord
{
    protected static string $resource = GoodsReceiptNoteResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Author of the GRN
        $data['created_by'] = Auth::id();

        // Handle unit conversion if receipt_unit is different from base unit
        if (!empty($data['resource_id']) && !empty($data['receipt_unit'])) {
            $resource = Resource::find($data['resource_id']);
            if ($resource) {
                $baseUnit = $resource->base_unit;
                $receiptUnit = $data['receipt_unit'];

                // Only convert if units are different
                if (strtolower($receiptUnit) !== strtolower($baseUnit)) {
                    $conversionFactor = $this->getConversionFactor($receiptUnit, $baseUnit);
                    
                    // Convert quantity to base unit
                    if (!empty($data['quantity_received'])) {
                        $data['quantity_received'] = round($data['quantity_received'] * $conversionFactor, 3);
                    }

                    // Convert unit price to price per base unit
                    if (!empty($data['unit_price'])) {
                        $data['unit_price'] = round($data['unit_price'] / $conversionFactor, 2);
                    }
                }
            }
        }

        // Recalculate total value with converted quantities
        if (!empty($data['quantity_received']) && !empty($data['unit_price'])) {
            $data['total_value'] = round($data['quantity_received'] * $data['unit_price'], 2);
        }

        // Remove receipt_unit as it's not stored in database (only used for conversion)
        unset($data['receipt_unit']);

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
                ->title('✅ Goods Receipt Recorded')
                ->body("GRN {$grn->grn_number} - {$grn->quantity_received} {$grn->resource->base_unit} of {$grn->resource->name} added to hub stock")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('⚠️ Warning')
                ->body('GRN created but inventory transaction failed: ' . $e->getMessage())
                ->warning()
                ->send();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Goods Receipt Note Created';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Get conversion factor from one unit to another
     */
    private function getConversionFactor(string $fromUnit, string $toUnit): float
    {
        $fromUnit = strtolower($fromUnit);
        $toUnit = strtolower($toUnit);

        if ($fromUnit === $toUnit) {
            return 1.0;
        }

        $conversions = [
            'kg' => 1.0, 'g' => 0.001, 'mg' => 0.000001, 'ton' => 1000.0, 'lb' => 0.453592, 'oz' => 0.0283495,
            'liter' => 1.0, 'liters' => 1.0, 'ml' => 0.001, 'gallon' => 3.78541, 'm3' => 1000.0,
            'm' => 1.0, 'cm' => 0.01, 'mm' => 0.001, 'km' => 1000.0, 'ft' => 0.3048, 'inch' => 0.0254,
            'sqm' => 1.0, 'sqft' => 0.092903, 'sqcm' => 0.0001,
            'piece' => 1.0, 'pieces' => 1.0, 'unit' => 1.0, 'dozen' => 12.0, 'box' => 1.0, 'carton' => 1.0,
            'pallet' => 1.0, 'bag' => 1.0, 'sack' => 1.0, 'bundle' => 1.0, 'set' => 1.0, 'pair' => 2.0,
            'roll' => 1.0, 'sheet' => 1.0, 'panel' => 1.0, 'tile' => 1.0,
        ];

        $fromFactor = $conversions[$fromUnit] ?? 1.0;
        $toFactor = $conversions[$toUnit] ?? 1.0;

        return $fromFactor / $toFactor;
    }
}
