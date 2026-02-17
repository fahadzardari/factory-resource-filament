<?php

namespace App\Filament\Resources\UnitResource\Pages;

use App\Filament\Resources\UnitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('back')
                ->label('← Back')
                ->url(UnitResource::getUrl('index'))
                ->button(),
        ];
    }

    public function getTitle(): string
    {
        return "Edit Unit: {$this->record->name} ({$this->record->code})";
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return '✅ Unit Updated Successfully';
    }
}
