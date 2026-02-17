<?php

namespace App\Filament\Resources\UnitConversionResource\Pages;

use App\Filament\Resources\UnitConversionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnitConversion extends EditRecord
{
    protected static string $resource = UnitConversionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('back')
                ->label('← Back')
                ->url(UnitConversionResource::getUrl('index'))
                ->button(),
        ];
    }

    public function getTitle(): string
    {
        $from = $this->record->fromUnit->code ?? '?';
        $to = $this->record->toUnit->code ?? '?';
        return "Edit: {$from} → {$to}";
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return '✅ Conversion Updated';
    }
}
