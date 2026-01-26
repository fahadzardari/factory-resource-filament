<?php

namespace App\Filament\Resources\ResourceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'batches';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('batch_number')
                    ->label('Batch Number')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'BATCH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                    ->helperText('Unique identifier for this purchase batch'),
                    
                Forms\Components\DatePicker::make('purchase_date')
                    ->label('Purchase Date')
                    ->required()
                    ->default(now())
                    ->maxDate(now()),
                    
                Forms\Components\TextInput::make('quantity_purchased')
                    ->label('Quantity Purchased')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->suffix(fn () => $this->getOwnerRecord()->unit_type ?? 'units')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $set('quantity_remaining', $state);
                    }),
                    
                Forms\Components\TextInput::make('purchase_price')
                    ->label('Unit Purchase Price')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('$')
                    ->step(0.01)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $quantity = $get('quantity_purchased');
                        if ($quantity && $state) {
                            $set('total_cost', number_format($quantity * $state, 2));
                        }
                    }),
                    
                Forms\Components\Placeholder::make('total_cost')
                    ->label('Total Cost')
                    ->content(function (Forms\Get $get) {
                        $quantity = $get('quantity_purchased') ?? 0;
                        $price = $get('purchase_price') ?? 0;
                        return '$' . number_format($quantity * $price, 2);
                    }),
                    
                Forms\Components\TextInput::make('supplier')
                    ->label('Supplier')
                    ->maxLength(255)
                    ->placeholder('Supplier name'),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->placeholder('Any additional information about this purchase'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('batch_number')
            ->columns([
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Batch Number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Purchase Date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('quantity_purchased')
                    ->label('Qty Purchased')
                    ->numeric(2)
                    ->suffix(fn ($record) => ' ' . $record->resource->unit_type)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('quantity_remaining')
                    ->label('Qty Remaining')
                    ->numeric(2)
                    ->suffix(fn ($record) => ' ' . $record->resource->unit_type)
                    ->sortable()
                    ->color(fn ($record) => $record->quantity_remaining > 0 ? 'success' : 'danger'),
                    
                Tables\Columns\TextColumn::make('quantity_used')
                    ->label('Qty Used')
                    ->state(fn ($record) => $record->quantity_used)
                    ->numeric(2)
                    ->suffix(fn ($record) => ' ' . $record->resource->unit_type),
                    
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Unit Price')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Current Value')
                    ->state(fn ($record) => $record->total_value)
                    ->money('USD')
                    ->sortable()
                    ->description('Remaining qty × unit price'),
                    
                Tables\Columns\TextColumn::make('supplier')
                    ->label('Supplier')
                    ->searchable()
                    ->toggleable(),
            ])
            ->defaultSort('purchase_date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('has_stock')
                    ->label('Has Stock')
                    ->query(fn (Builder $query) => $query->where('quantity_remaining', '>', 0)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add New Purchase')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['quantity_remaining'] = $data['quantity_purchased'];
                        return $data;
                    })
                    ->after(function ($record) {
                        // Update the resource's total quantity
                        $resource = $record->resource;
                        $resource->available_quantity += $record->quantity_purchased;
                        $resource->total_quantity += $record->quantity_purchased;
                        $resource->save();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->disabled(fn ($record) => $record->quantity_used > 0)
                    ->tooltip(fn ($record) => $record->quantity_used > 0 ? 'Cannot edit batch that has been used' : null),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn ($record) => $record->quantity_used > 0)
                    ->tooltip(fn ($record) => $record->quantity_used > 0 ? 'Cannot delete batch that has been used' : null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No purchase batches yet')
            ->emptyStateDescription('Add your first purchase batch to track inventory with accurate pricing')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
