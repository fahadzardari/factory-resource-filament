<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResourceResource\Pages;
use App\Filament\Resources\ResourceResource\RelationManagers;
use App\Models\Resource as ResourceModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ResourceResource extends Resource
{
    protected static ?string $model = ResourceModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationLabel = 'Resources';
    
    protected static ?string $modelLabel = 'Resource';
    
    protected static ?string $navigationGroup = 'Inventory Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Core resource details. The SKU must be unique across all resources.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive name for the resource (e.g., "Steel Rebar 12mm")'),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Stock Keeping Unit - unique identifier for tracking'),
                        Forms\Components\Select::make('category')
                            ->options([
                                'Raw Materials' => 'Raw Materials',
                                'Tools' => 'Tools',
                                'Equipment' => 'Equipment',
                                'Consumables' => 'Consumables',
                                'Others' => 'Others',
                            ])
                            ->searchable()
                            ->native(false)
                            ->helperText('Group resources by type for easier management'),
                        Forms\Components\Select::make('unit_type')
                            ->label('Default Unit Type')
                            ->options([
                                'kg' => 'Kilograms (kg)',
                                'g' => 'Grams (g)',
                                'ton' => 'Metric Tons',
                                'lb' => 'Pounds (lb)',
                                'liter' => 'Liters',
                                'ml' => 'Milliliters',
                                'gallon' => 'Gallons',
                                'meter' => 'Meters',
                                'cm' => 'Centimeters',
                                'ft' => 'Feet',
                                'cubic_ft' => 'Cubic Feet',
                                'cubic_m' => 'Cubic Meters',
                                'sq_m' => 'Square Meters',
                                'sq_ft' => 'Square Feet',
                                'piece' => 'Pieces',
                                'box' => 'Boxes',
                                'carton' => 'Cartons',
                                'bundle' => 'Bundles',
                                'roll' => 'Rolls',
                                'sheet' => 'Sheets',
                                'pair' => 'Pairs',
                            ])
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->helperText('Default unit for this resource. Batches can have different units.'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Pricing & Stock Information')
                    ->description('💡 Actual quantities are calculated from purchase batches. Edit batches to manage stock.')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Base Purchase Price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step(0.01)
                            ->helperText('Default price per unit. Each batch can have different pricing.'),
                        Forms\Components\Placeholder::make('total_quantity_display')
                            ->label('Total Quantity')
                            ->content(fn (?ResourceModel $record): string => 
                                $record ? number_format($record->available_quantity, 2) . ' ' . $record->unit_type : '0.00'
                            )
                            ->helperText('Sum of remaining quantities across all batches'),
                        Forms\Components\Placeholder::make('total_value_display')
                            ->label('Total Inventory Value')
                            ->content(fn (?ResourceModel $record): string => 
                                $record ? '$' . number_format($record->total_value, 2) : '$0.00'
                            )
                            ->helperText('Calculated from batch quantities × their purchase prices'),
                        Forms\Components\Hidden::make('total_quantity')->default(0),
                        Forms\Components\Hidden::make('available_quantity')->default(0),
                    ])
                    ->columns(3)
                    ->visible(fn ($operation) => $operation === 'edit'),
                    
                Forms\Components\Section::make('Pricing')
                    ->description('Set the base purchase price for this resource.')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Base Purchase Price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step(0.01)
                            ->helperText('Default price per unit. Each batch can have different pricing.'),
                    ])
                    ->visible(fn ($operation) => $operation === 'create'),
                    
                Forms\Components\Section::make('Additional Information')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->helperText('Add notes about specifications, usage, or handling instructions'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('SKU copied!')
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn ($record) => $record->description ? \Str::limit($record->description, 50) : null),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Raw Materials' => 'info',
                        'Tools' => 'warning',
                        'Equipment' => 'primary',
                        'Consumables' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_type')
                    ->label('Unit')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Available Qty')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => 'From ' . $record->batches()->count() . ' batch(es)'),
                Tables\Columns\TextColumn::make('weighted_average_price')
                    ->label('Avg Price')
                    ->money('USD')
                    ->sortable()
                    ->description('Weighted avg')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money('USD')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('success'),
                Tables\Columns\TextColumn::make('batches_count')
                    ->label('Batches')
                    ->counts('batches')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Raw Materials' => 'Raw Materials',
                        'Tools' => 'Tools',
                        'Equipment' => 'Equipment',
                        'Consumables' => 'Consumables',
                        'Others' => 'Others',
                    ]),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (< 100)')
                    ->query(fn (Builder $query): Builder => $query->where('available_quantity', '<', 100)),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->where('available_quantity', '<=', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('This will delete the resource and ALL associated batches. This action cannot be undone.'),
                ]),
            ])
            ->emptyStateHeading('No resources yet')
            ->emptyStateDescription('Start by adding resources to your central inventory. Each resource can have multiple purchase batches.')
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add First Resource')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BatchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResources::route('/'),
            'create' => Pages\CreateResource::route('/create'),
            'view' => Pages\ViewResource::route('/{record}'),
            'edit' => Pages\EditResource::route('/{record}/edit'),
        ];
    }
}
