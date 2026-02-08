<?php

namespace App\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Filament\Resources\GoodsReceiptNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceiptNotes extends ListRecords
{
    protected static string $resource = GoodsReceiptNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

