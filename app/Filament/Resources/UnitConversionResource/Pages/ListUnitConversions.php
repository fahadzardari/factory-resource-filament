<?php

namespace App\Filament\Resources\UnitConversionResource\Pages;

use App\Filament\Resources\UnitConversionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnitConversions extends ListRecords
{
    protected static string $resource = UnitConversionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Add Conversion')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return '🔄 Unit Conversions';
    }

    public function getHeading(): string
    {
        return 'Define How Units Convert';
    }
}
