<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\EditRecord;

class EditResource extends EditRecord
{
    protected static string $resource = ResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Inventory Summary')
                    ->description('Real-time inventory valuation based on purchase batches')
                    ->schema([
                        TextEntry::make('available_quantity')
                            ->label('Available Quantity')
                            ->suffix(fn ($record) => ' ' . $record->unit_type)
                            ->size('lg')
                            ->weight('bold')
                            ->color('success'),
                            
                        TextEntry::make('total_value')
                            ->label('Total Inventory Value')
                            ->money('USD')
                            ->size('lg')
                            ->weight('bold')
                            ->color('primary')
                            ->helperText('Sum of all batch values (quantity × purchase price)'),
                            
                        TextEntry::make('weighted_average_price')
                            ->label('Weighted Average Price')
                            ->money('USD')
                            ->helperText('Average cost per unit across all batches'),
                            
                        TextEntry::make('batches_count')
                            ->label('Number of Batches')
                            ->state(fn ($record) => $record->batches->count())
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(4)
                    ->collapsible(),
            ]);
    }
}
