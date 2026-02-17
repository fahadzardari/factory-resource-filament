<?php

namespace App\Filament\Resources\UnitResource\Pages;

use App\Filament\Resources\UnitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnits extends ListRecords
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ New Unit')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return '📐 Unit Management';
    }

    public function getHeading(): string
    {
        return '📏 Unit Management - Create Units & Conversions';
    }
}
