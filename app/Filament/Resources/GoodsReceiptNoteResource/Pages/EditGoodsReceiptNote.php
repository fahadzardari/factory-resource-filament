<?php

namespace App\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Filament\Resources\GoodsReceiptNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditGoodsReceiptNote extends EditRecord
{
    protected static string $resource = GoodsReceiptNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Goods Receipt Note')
                ->modalDescription('This will also remove the associated inventory transaction. This action cannot be undone.'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Goods Receipt Note Updated';
    }
}
