<?php

namespace App\Filament\Resources\UnitConversionResource\Pages;

use App\Filament\Resources\UnitConversionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUnitConversion extends CreateRecord
{
    protected static string $resource = UnitConversionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('← Back to Conversions')
                ->url(UnitConversionResource::getUrl('index'))
                ->button(),
        ];
    }

    public function getTitle(): string
    {
        return 'Add New Conversion';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Conversion Added';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
