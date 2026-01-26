<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResourceTransferResource\Pages;
use App\Models\ResourceTransfer;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ResourceTransferResource extends Resource
{
    protected static ?string $model = ResourceTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    
    protected static ?string $navigationLabel = 'Transfer History';
    
    protected static ?string $navigationGroup = 'Inventory Management';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resource.name')
                    ->label('Resource')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('resource.sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transfer_type')
                    ->label('Transfer Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'warehouse_to_project' => 'Warehouse → Project',
                        'project_to_project' => 'Project → Project',
                        'project_to_warehouse' => 'Project → Warehouse',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'warehouse_to_project' => 'info',
                        'project_to_project' => 'warning',
                        'project_to_warehouse' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('fromProject.name')
                    ->label('From')
                    ->default('Warehouse')
                    ->sortable(),
                Tables\Columns\TextColumn::make('toProject.name')
                    ->label('To')
                    ->default('Warehouse')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('transferredBy.name')
                    ->label('Transferred By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transferred_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->default(fn ($record) => $record->created_at),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transfer_type')
                    ->options([
                        'warehouse_to_project' => 'Warehouse → Project',
                        'project_to_project' => 'Project → Project',
                        'project_to_warehouse' => 'Project → Warehouse',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('transferred_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResourceTransfers::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
}
