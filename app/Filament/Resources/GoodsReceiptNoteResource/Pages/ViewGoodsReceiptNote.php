<?php

namespace App\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Filament\Resources\GoodsReceiptNoteResource;
use Filament\Resources\Pages\ViewRecord;

class ViewGoodsReceiptNote extends ViewRecord
{
    protected static string $resource = GoodsReceiptNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
