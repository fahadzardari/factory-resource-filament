<?php

namespace App\Filament\Resources;

use App\Models\GoodsReceiptNote;
use App\Models\InventoryTransaction;
use App\Services\InventoryTransactionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoodsReceiptNoteResource extends Resource
{
    protected static ?string $model = GoodsReceiptNote::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Goods Receipts (GRN)';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'grn_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Receipt Information')
                    ->description('Record when goods physically arrive at warehouse')
                    ->schema([
                        Forms\Components\TextInput::make('grn_number')
                            ->label('GRN Number')
                            ->disabled()
                            ->dehydrated()
                            ->placeholder('Auto-generated on save')
                            ->helperText('Format: GRN-YYYY-00000'),

                        Forms\Components\DatePicker::make('receipt_date')
                            ->label('Receipt Date')
                            ->required()
                            ->default(now())
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Supplier & Resource')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Supplier Name')
                                    ->required()
                                    ->unique('suppliers', 'name'),
                                Forms\Components\TextInput::make('contact_person')
                                    ->label('Contact Person'),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel(),
                            ]),

                        Forms\Components\Select::make('resource_id')
                            ->label('Resource/Item')
                            ->relationship('resource', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Quantity & Pricing')
                    ->description('Received quantity can be in any unit - will be converted to base unit for storage')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity_received')
                                    ->label('Quantity Received')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->step(0.001)
                                    ->live()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $unitPrice = $get('unit_price');
                                        if ($state && $unitPrice) {
                                            $set('total_value', round($state * $unitPrice, 2));
                                        }
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Select::make('receipt_unit')
                                    ->label('Unit of Receipt')
                                    ->required()
                                    ->options(fn ($get) => self::getUnitOptionsFor($get('resource_id')))
                                    ->searchable()
                                    ->live()
                                    ->reactive()
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Placeholder::make('conversion_info')
                            ->content(function ($get) {
                                $quantity = $get('quantity_received');
                                $receiptUnit = $get('receipt_unit');
                                $resourceId = $get('resource_id');
                                
                                if (!$quantity || !$receiptUnit || !$resourceId) {
                                    return '📝 Enter quantity and unit to see conversion';
                                }

                                try {
                                    $resource = \App\Models\Resource::find($resourceId);
                                    if (!$resource) {
                                        return '⚠️ Resource not found';
                                    }

                                    $baseUnit = $resource->base_unit;
                                    $conversionFactor = self::getConversionFactor($receiptUnit, $baseUnit);
                                    $convertedQty = $quantity * $conversionFactor;

                                    if ($conversionFactor == 1) {
                                        return "✅ **No conversion needed** - Receiving in base unit ({$baseUnit})";
                                    } else {
                                        return "📊 **Conversion:** {$quantity} {$receiptUnit} = **" . number_format($convertedQty, 3) . " {$baseUnit}** (will be stored in database)";
                                    }
                                } catch (\Exception $e) {
                                    return '⚠️ Error calculating conversion: ' . $e->getMessage();
                                }
                            })
                            ->visible(fn ($get) => $get('quantity_received') && $get('receipt_unit') && $get('resource_id'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->helperText(fn ($get) => 'Price per ' . ($get('receipt_unit') ?? 'unit'))
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->live()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $quantity = $get('quantity_received');
                                if ($quantity && $state) {
                                    $set('total_value', round($quantity * $state, 2));
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('conversion_price_info')
                            ->content(function ($get) {
                                $quantity = $get('quantity_received');
                                $unitPrice = $get('unit_price');
                                $receiptUnit = $get('receipt_unit');
                                $resourceId = $get('resource_id');
                                
                                if (!$quantity || !$unitPrice || !$receiptUnit || !$resourceId) {
                                    return '';
                                }

                                try {
                                    $resource = \App\Models\Resource::find($resourceId);
                                    if (!$resource) {
                                        return '';
                                    }

                                    $baseUnit = $resource->base_unit;
                                    $conversionFactor = self::getConversionFactor($receiptUnit, $baseUnit);
                                    
                                    if ($conversionFactor == 1) {
                                        return "Price per base unit: AED {$unitPrice}";
                                    } else {
                                        $pricePerBaseUnit = $unitPrice / $conversionFactor;
                                        return "Price per {$receiptUnit}: AED {$unitPrice} → **Price per {$baseUnit}: AED " . number_format($pricePerBaseUnit, 2) . "**";
                                    }
                                } catch (\Exception $e) {
                                    return '';
                                }
                            })
                            ->visible(fn ($get) => $get('unit_price') && $get('receipt_unit') && $get('resource_id'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('total_value')
                            ->label('Total Value (Receipt Amount)')
                            ->disabled()
                            ->dehydrated()
                            ->numeric()
                            ->step(0.01)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Details')
                    ->schema([
                        Forms\Components\TextInput::make('delivery_reference')
                            ->label('Delivery Reference / Tracking Number')
                            ->placeholder('e.g., Shipment #, AWB, Invoice Qty, etc.')
                            ->maxLength(255)
                            ->columnSpan('full'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes / Remarks')
                            ->rows(3)
                            ->placeholder('e.g., "Damaged 5 units", "Missing items", etc.')
                            ->columnSpan('full'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('grn_number')
                    ->label('GRN #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn ($record) => route('filament.admin.resources.goods-receipt-notes.edit', $record)),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('resource.name')
                    ->label('Resource/Item')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('quantity_received')
                    ->label('Qty Received')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('gbp')
                    ->sortable()
                    ->alignment('right')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money('gbp')
                    ->sortable()
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('receipt_date')
                    ->label('Receipt Date')
                    ->date('d M, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivery_reference')
                    ->label('Delivery Ref')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('resource_id')
                    ->label('Resource')
                    ->relationship('resource', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\Filter::make('receipt_date')
                    ->form([
                        Forms\Components\DatePicker::make('receipt_from')
                            ->label('Receipt Date From'),
                        Forms\Components\DatePicker::make('receipt_until')
                            ->label('Receipt Date To'),
                    ])
                    ->query(function ($query, array $data): mixed {
                        return $query
                            ->when(
                                $data['receipt_from'],
                                fn ($q) => $q->whereDate('receipt_date', '>=', $data['receipt_from'])
                            )
                            ->when(
                                $data['receipt_until'],
                                fn ($q) => $q->whereDate('receipt_date', '<=', $data['receipt_until'])
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('receipt_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\GoodsReceiptNoteResource\Pages\ListGoodsReceiptNotes::route('/'),
            'create' => \App\Filament\Resources\GoodsReceiptNoteResource\Pages\CreateGoodsReceiptNote::route('/create'),
            'edit' => \App\Filament\Resources\GoodsReceiptNoteResource\Pages\EditGoodsReceiptNote::route('/{record}/edit'),
        ];
    }

    /**
     * Get available unit options for a specific resource based on its base unit
     */
    protected static function getUnitOptionsFor(?int $resourceId): array
    {
        if (!$resourceId) {
            return [];
        }

        try {
            $resource = \App\Models\Resource::find($resourceId);
            if (!$resource) {
                return [];
            }

            $baseUnit = $resource->base_unit;
            return self::getUnitConversionOptions($baseUnit);
        } catch (\Exception $e) {
            Log::error('Error getting unit options: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available unit conversion options based on base unit
     */
    protected static function getUnitConversionOptions(string $baseUnit): array
    {
        $conversionMap = [
            // Weight units
            'kg' => [
                'kg' => 'Kilograms (kg) - Base Unit',
                'g' => 'Grams (g)',
                'mg' => 'Milligrams (mg)',
                'ton' => 'Metric Tons (ton)',
                'lb' => 'Pounds (lb)',
                'oz' => 'Ounces (oz)',
            ],
            'g' => [
                'g' => 'Grams (g) - Base Unit',
                'kg' => 'Kilograms (kg)',
                'mg' => 'Milligrams (mg)',
                'ton' => 'Metric Tons (ton)',
                'lb' => 'Pounds (lb)',
                'oz' => 'Ounces (oz)',
            ],
            'ton' => [
                'ton' => 'Metric Tons (ton) - Base Unit',
                'kg' => 'Kilograms (kg)',
                'g' => 'Grams (g)',
                'lb' => 'Pounds (lb)',
            ],
            'lb' => [
                'lb' => 'Pounds (lb) - Base Unit',
                'kg' => 'Kilograms (kg)',
                'oz' => 'Ounces (oz)',
                'ton' => 'Metric Tons (ton)',
            ],
            'liter' => [
                'liter' => 'Liters (L) - Base Unit',
                'ml' => 'Milliliters (ml)',
                'gallon' => 'Gallons (gal)',
                'm3' => 'Cubic Meters (m³)',
            ],
            'liters' => [
                'liters' => 'Liters (L) - Base Unit',
                'ml' => 'Milliliters (ml)',
                'gallon' => 'Gallons (gal)',
                'm3' => 'Cubic Meters (m³)',
            ],
            'ml' => [
                'ml' => 'Milliliters (ml) - Base Unit',
                'liter' => 'Liters (L)',
                'liters' => 'Liters (L)',
                'gallon' => 'Gallons (gal)',
            ],
            'm3' => [
                'm3' => 'Cubic Meters (m³) - Base Unit',
                'liter' => 'Liters (L)',
                'liters' => 'Liters (L)',
                'gallon' => 'Gallons (gal)',
            ],
            'gallon' => [
                'gallon' => 'Gallons (gal) - Base Unit',
                'liter' => 'Liters (L)',
                'liters' => 'Liters (L)',
                'ml' => 'Milliliters (ml)',
            ],
            'm' => [
                'm' => 'Meters (m) - Base Unit',
                'cm' => 'Centimeters (cm)',
                'mm' => 'Millimeters (mm)',
                'km' => 'Kilometers (km)',
                'ft' => 'Feet (ft)',
                'inch' => 'Inches (in)',
            ],
            'cm' => [
                'cm' => 'Centimeters (cm) - Base Unit',
                'm' => 'Meters (m)',
                'mm' => 'Millimeters (mm)',
                'ft' => 'Feet (ft)',
                'inch' => 'Inches (in)',
            ],
            'mm' => [
                'mm' => 'Millimeters (mm) - Base Unit',
                'm' => 'Meters (m)',
                'cm' => 'Centimeters (cm)',
                'inch' => 'Inches (in)',
            ],
            'ft' => [
                'ft' => 'Feet (ft) - Base Unit',
                'm' => 'Meters (m)',
                'cm' => 'Centimeters (cm)',
                'inch' => 'Inches (in)',
            ],
            'inch' => [
                'inch' => 'Inches (in) - Base Unit',
                'm' => 'Meters (m)',
                'cm' => 'Centimeters (cm)',
                'ft' => 'Feet (ft)',
            ],
            'sqm' => [
                'sqm' => 'Square Meters (m²) - Base Unit',
                'sqft' => 'Square Feet (ft²)',
                'sqcm' => 'Square Centimeters (cm²)',
            ],
            'sqft' => [
                'sqft' => 'Square Feet (ft²) - Base Unit',
                'sqm' => 'Square Meters (m²)',
            ],
            'sqcm' => [
                'sqcm' => 'Square Centimeters (cm²) - Base Unit',
                'sqm' => 'Square Meters (m²)',
            ],
            'piece' => [
                'piece' => 'Pieces - Base Unit',
                'dozen' => 'Dozen (12 pieces)',
                'box' => 'Box',
                'carton' => 'Carton',
                'pallet' => 'Pallet',
                'bundle' => 'Bundle',
                'set' => 'Set',
                'pair' => 'Pair (2 pieces)',
            ],
            'pieces' => [
                'pieces' => 'Pieces - Base Unit',
                'dozen' => 'Dozen (12 pieces)',
                'box' => 'Box',
                'carton' => 'Carton',
                'pallet' => 'Pallet',
                'bundle' => 'Bundle',
                'set' => 'Set',
                'pair' => 'Pair (2 pieces)',
            ],
            'unit' => [
                'unit' => 'Units - Base Unit',
                'dozen' => 'Dozen (12 units)',
                'box' => 'Box',
                'carton' => 'Carton',
            ],
            'dozen' => [
                'dozen' => 'Dozen - Base Unit',
                'piece' => 'Pieces',
                'pieces' => 'Pieces',
            ],
            'box' => [
                'box' => 'Box - Base Unit',
                'carton' => 'Carton',
                'pallet' => 'Pallet',
            ],
            'carton' => [
                'carton' => 'Carton - Base Unit',
                'box' => 'Box',
                'pallet' => 'Pallet',
            ],
            'pallet' => [
                'pallet' => 'Pallet - Base Unit',
                'box' => 'Box',
                'carton' => 'Carton',
            ],
            'bag' => [
                'bag' => 'Bags - Base Unit',
                'ton' => 'Metric Tons',
                'kg' => 'Kilograms',
            ],
            'sack' => [
                'sack' => 'Sacks - Base Unit',
                'ton' => 'Metric Tons',
                'kg' => 'Kilograms',
            ],
            'roll' => [
                'roll' => 'Rolls - Base Unit',
                'dozen' => 'Dozen Rolls',
            ],
            'sheet' => [
                'sheet' => 'Sheets - Base Unit',
                'dozen' => 'Dozen Sheets',
                'bundle' => 'Bundle',
            ],
            'panel' => [
                'panel' => 'Panels - Base Unit',
                'dozen' => 'Dozen Panels',
                'bundle' => 'Bundle',
            ],
            'tile' => [
                'tile' => 'Tiles - Base Unit',
                'box' => 'Box',
                'carton' => 'Carton',
                'sqm' => 'Square Meters (coverage)',
            ],
            'bundle' => [
                'bundle' => 'Bundles - Base Unit',
                'piece' => 'Pieces',
                'pieces' => 'Pieces',
            ],
            'set' => [
                'set' => 'Sets - Base Unit',
                'piece' => 'Pieces',
                'pieces' => 'Pieces',
            ],
            'pair' => [
                'pair' => 'Pairs - Base Unit',
                'piece' => 'Pieces',
                'pieces' => 'Pieces',
            ],
        ];

        return $conversionMap[strtolower($baseUnit)] ?? [
            $baseUnit => $baseUnit . ' - Base Unit',
        ];
    }

    /**
     * Get conversion factor from one unit to another
     */
    protected static function getConversionFactor(string $fromUnit, string $toUnit): float
    {
        $fromUnit = strtolower($fromUnit);
        $toUnit = strtolower($toUnit);

        if ($fromUnit === $toUnit) {
            return 1.0;
        }

        $conversions = [
            'kg' => 1.0, 'g' => 0.001, 'mg' => 0.000001, 'ton' => 1000.0, 'lb' => 0.453592, 'oz' => 0.0283495,
            'liter' => 1.0, 'liters' => 1.0, 'ml' => 0.001, 'gallon' => 3.78541, 'm3' => 1000.0,
            'm' => 1.0, 'cm' => 0.01, 'mm' => 0.001, 'km' => 1000.0, 'ft' => 0.3048, 'inch' => 0.0254,
            'sqm' => 1.0, 'sqft' => 0.092903, 'sqcm' => 0.0001,
            'piece' => 1.0, 'pieces' => 1.0, 'unit' => 1.0, 'dozen' => 12.0, 'box' => 1.0, 'carton' => 1.0,
            'pallet' => 1.0, 'bag' => 1.0, 'sack' => 1.0, 'bundle' => 1.0, 'set' => 1.0, 'pair' => 2.0,
            'roll' => 1.0, 'sheet' => 1.0, 'panel' => 1.0, 'tile' => 1.0,
        ];

        $fromFactor = $conversions[$fromUnit] ?? 1.0;
        $toFactor = $conversions[$toUnit] ?? 1.0;

        return $fromFactor / $toFactor;
    }
}
