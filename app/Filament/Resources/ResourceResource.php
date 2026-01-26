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
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->options([
                                'Raw Materials' => 'Raw Materials',
                                'Tools' => 'Tools',
                                'Equipment' => 'Equipment',
                                'Consumables' => 'Consumables',
                                'Others' => 'Others',
                            ])
                            ->searchable()
                            ->native(false),
                        Forms\Components\Select::make('unit_type')
                            ->label('Unit Type')
                            ->options([
                                'kg' => 'Kilograms (kg)',
                                'g' => 'Grams (g)',
                                'ton' => 'Tons',
                                'liter' => 'Liters',
                                'ml' => 'Milliliters',
                                'meter' => 'Meters',
                                'cm' => 'Centimeters',
                                'piece' => 'Pieces',
                                'box' => 'Boxes',
                                'carton' => 'Cartons',
                            ])
                            ->required()
                            ->searchable()
                            ->native(false),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Pricing & Quantity')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Purchase Price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step(0.01),
                        Forms\Components\TextInput::make('total_quantity')
                            ->label('Total Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0),
                        Forms\Components\TextInput::make('available_quantity')
                            ->label('Available Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Free stock not allocated to any project'),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_type')
                    ->label('Unit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Total Qty')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Available Qty')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PriceHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResources::route('/'),
            'create' => Pages\CreateResource::route('/create'),
            'edit' => Pages\EditResource::route('/{record}/edit'),
        ];
    }
}
