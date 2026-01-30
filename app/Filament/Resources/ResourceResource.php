<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResourceResource\Pages;
use App\Filament\Resources\ResourceResource\RelationManagers;
use App\Models\Resource as ResourceModel;
use App\Models\Project;
use App\Services\InventoryTransactionService;
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
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

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
                            ->helperText('A descriptive name for the resource (e.g., "Portland Cement")'),
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
                        Forms\Components\Select::make('base_unit')
                            ->label('Base Unit')
                            ->required()
                            ->searchable()
                            ->options([
                                // Weight Units
                                'kg' => 'Kilograms (kg) - Most common for construction materials',
                                'g' => 'Grams (g)',
                                'mg' => 'Milligrams (mg)',
                                'ton' => 'Metric Tons (ton)',
                                'lb' => 'Pounds (lb)',
                                'oz' => 'Ounces (oz)',
                                
                                // Volume Units
                                'liter' => 'Liters (L) - For liquids like paint, oil',
                                'liters' => 'Liters (L) - Alternative spelling',
                                'ml' => 'Milliliters (ml)',
                                'gallon' => 'Gallons (gal)',
                                'm3' => 'Cubic Meters (m³) - For large volumes',
                                
                                // Length Units
                                'm' => 'Meters (m) - For cables, pipes, rods',
                                'cm' => 'Centimeters (cm)',
                                'mm' => 'Millimeters (mm)',
                                'km' => 'Kilometers (km)',
                                'ft' => 'Feet (ft)',
                                'inch' => 'Inches (in)',
                                
                                // Area Units
                                'sqm' => 'Square Meters (m²) - For tiles, flooring',
                                'sqft' => 'Square Feet (ft²)',
                                'sqcm' => 'Square Centimeters (cm²)',
                                
                                // Count/Piece Units
                                'piece' => 'Pieces - For countable items',
                                'pieces' => 'Pieces - Alternative spelling',
                                'unit' => 'Units - Generic count',
                                'dozen' => 'Dozen (12 items)',
                                'box' => 'Box - Container unit',
                                'carton' => 'Carton - Container unit',
                                'pallet' => 'Pallet - Large container',
                                
                                // Construction Specific
                                'bag' => 'Bags - For cement, sand',
                                'sack' => 'Sacks - Similar to bags',
                                
                                // Other Common Units
                                'roll' => 'Rolls - For materials on rolls',
                                'sheet' => 'Sheets - For flat materials',
                                'panel' => 'Panels - For wall/ceiling panels',
                                'tile' => 'Tiles - For floor/wall tiles',
                                'bundle' => 'Bundles - For grouped items',
                                'set' => 'Sets - For grouped items',
                                'pair' => 'Pairs - For matched items',
                            ])
                            ->helperText('Select the primary unit for this resource. You can purchase in other units - they will be converted automatically.')
                            ->placeholder('Search for a unit...')
                            ->native(false),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->helperText('Add notes about specifications, usage, or handling instructions'),
                    ])
                    ->columns(2),
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
                    ->weight(FontWeight::Bold)
                    ->width('150px')
                    ->limit(20),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn ($record) => $record->description ? Str::limit($record->description, 50) : null)
                    ->width('250px'),
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
                    ->sortable()
                    ->width('180px'),
                Tables\Columns\TextColumn::make('base_unit')
                    ->label('Unit')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->width('100px'),
                Tables\Columns\TextColumn::make('hub_stock')
                    ->label('Hub Stock')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => 'At central hub')
                    ->width('150px'),
                Tables\Columns\TextColumn::make('weighted_avg_price')
                    ->label('Avg Price')
                    ->money('USD')
                    ->description('Weighted avg')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('hub_value')
                    ->label('Hub Value')
                    ->money('USD')
                    ->weight(FontWeight::Bold)
                    ->color('success'),
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
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('transactions', function ($q) {
                            // This is a placeholder - would need proper calculation
                        })
                    ),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDoesntHave('transactions')
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('purchase')
                    ->label('Purchase')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->modalHeading('🛒 Record New Purchase')
                    ->modalDescription('Add newly purchased materials to the Central Hub inventory.')
                    ->form([
                        Forms\Components\Placeholder::make('info')
                            ->content('📦 Record materials you have purchased and received. This will increase the Hub inventory.')
                            ->columnSpanFull(),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity Purchased')
                                    ->helperText('How much did you buy?')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01)
                                    ->reactive()
                                    ->columnSpan(1),
                                
                                Forms\Components\Select::make('purchase_unit')
                                    ->label('Purchase Unit')
                                    ->helperText('Unit you purchased in')
                                    ->options(function ($record) {
                                        return static::getUnitConversionOptions($record->base_unit);
                                    })
                                    ->default(fn ($record) => $record->base_unit)
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->columnSpan(1),
                            ]),
                        
                        Forms\Components\Placeholder::make('conversion_info')
                            ->content(function ($get, $record) {
                                $quantity = $get('quantity') ?? 0;
                                $purchaseUnit = $get('purchase_unit') ?? $record->base_unit;
                                $baseUnit = $record->base_unit;
                                
                                if ($quantity && $purchaseUnit) {
                                    $conversion = static::getConversionFactor($purchaseUnit, $baseUnit);
                                    $convertedQty = $quantity * $conversion;
                                    
                                    if ($conversion != 1) {
                                        return "📊 **Conversion:** {$quantity} {$purchaseUnit} = " . number_format($convertedQty, 2) . " {$baseUnit} (stored in inventory)";
                                    } else {
                                        return "✅ No conversion needed - purchasing in base unit";
                                    }
                                }
                                return '';
                            })
                            ->visible(fn ($get) => $get('quantity') && $get('purchase_unit'))
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('unit_price')
                            ->label('Price per Unit')
                            ->helperText(fn ($get, $record) => 'Cost for one ' . ($get('purchase_unit') ?? $record->base_unit))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('AED'),
                        
                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('Purchase Date')
                            ->helperText('When did you receive this?')
                            ->required()
                            ->default(today())
                            ->maxDate(today()),
                        
                        Forms\Components\TextInput::make('supplier')
                            ->label('Supplier Name (Optional)')
                            ->placeholder('ABC Building Materials LLC')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice # (Optional)')
                            ->placeholder('INV-2026-001234')
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes (Optional)')
                            ->placeholder('Any additional details')
                            ->rows(3),
                    ])
                    ->action(function (ResourceModel $record, array $data) {
                        $service = app(InventoryTransactionService::class);
                        
                        try {
                            // Convert quantity to base unit
                            $purchaseUnit = $data['purchase_unit'] ?? $record->base_unit;
                            $conversionFactor = static::getConversionFactor($purchaseUnit, $record->base_unit);
                            $quantityInBaseUnit = $data['quantity'] * $conversionFactor;
                            
                            // Calculate price per base unit
                            $pricePerBaseUnit = $data['unit_price'] / $conversionFactor;
                            
                            // Add conversion info to notes if converted
                            $notes = $data['notes'] ?? '';
                            if ($conversionFactor != 1) {
                                $notes .= ($notes ? "\n" : "") . "Purchased as: {$data['quantity']} {$purchaseUnit} (converted to " . number_format($quantityInBaseUnit, 2) . " {$record->base_unit})";
                            }
                            
                            $service->recordPurchase(
                                $record,
                                $quantityInBaseUnit,
                                $pricePerBaseUnit,
                                \Carbon\Carbon::parse($data['transaction_date'])->format('Y-m-d'),
                                $data['supplier'] ?? null,
                                $data['invoice_number'] ?? null,
                                $notes ?: null
                            );
                            
                            Notification::make()
                                ->success()
                                ->title('✅ Purchase Recorded!')
                                ->body("Added {$data['quantity']} {$purchaseUnit}" . ($conversionFactor != 1 ? " (" . number_format($quantityInBaseUnit, 2) . " {$record->base_unit})" : "") . " of {$record->name} to Hub inventory.")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('❌ Purchase Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('allocate')
                    ->label('Allocate')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('project_id')
                            ->label('Allocate to Project')
                            ->required()
                            ->options(Project::where('status', 'Active')->pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Select the project site to allocate inventory to'),
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->label('Quantity')
                            ->suffix(fn ($record) => $record->base_unit)
                            ->helperText(fn ($record) => "Available at hub: {$record->hub_stock} {$record->base_unit}"),
                        Forms\Components\DatePicker::make('transaction_date')
                            ->required()
                            ->default(now())
                            ->label('Allocation Date')
                            ->maxDate(now()),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->rows(3)
                            ->helperText('Optional notes about this allocation'),
                    ])
                    ->action(function (ResourceModel $record, array $data) {
                        $service = app(InventoryTransactionService::class);
                        
                        try {
                            $metadata = [];
                            if (!empty($data['notes'])) $metadata['notes'] = $data['notes'];
                            
                            $service->recordAllocation(
                                $record,
                                Project::find($data['project_id']),
                                $data['quantity'],
                                \Carbon\Carbon::parse($data['transaction_date'])->format('Y-m-d'),
                                !empty($metadata) ? json_encode($metadata) : null
                            );
                            
                            $project = Project::find($data['project_id']);
                            
                            Notification::make()
                                ->success()
                                ->title('Allocation Successful')
                                ->body("Allocated {$data['quantity']} {$record->base_unit} of {$record->name} to {$project->name}.")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Allocation Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('This will delete the resource and ALL associated transactions. This action cannot be undone.'),
                ]),
            ])
            ->emptyStateHeading('No resources yet')
            ->emptyStateDescription('Start by adding resources to your central inventory.')
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
            //
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

    /**
     * Get available unit conversion options based on base unit
     */
    protected static function getUnitConversionOptions(string $baseUnit): array
    {
        // Define conversion options for different base units
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
            
            // Volume units
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
            
            // Length units
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
            
            // Area units
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
            
            // Count/Piece units
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
            
            // Construction-specific
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
            
            // Other common units
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
        
        // Return options for the base unit, or default to just the base unit
        return $conversionMap[strtolower($baseUnit)] ?? [
            $baseUnit => $baseUnit . ' - Base Unit',
        ];
    }

    /**
     * Get conversion factor from purchase unit to base unit
     */
    protected static function getConversionFactor(string $fromUnit, string $toUnit): float
    {
        // Normalize to lowercase
        $fromUnit = strtolower($fromUnit);
        $toUnit = strtolower($toUnit);
        
        // If same unit, no conversion needed
        if ($fromUnit === $toUnit) {
            return 1.0;
        }
        
        // Define conversion factors to base units
        $conversions = [
            // Weight conversions (to kg)
            'kg' => 1.0,
            'g' => 0.001,
            'mg' => 0.000001,
            'ton' => 1000.0,
            'lb' => 0.453592,
            'oz' => 0.0283495,
            
            // Volume conversions (to liter)
            'liter' => 1.0,
            'liters' => 1.0,
            'ml' => 0.001,
            'gallon' => 3.78541,
            'm3' => 1000.0,
            
            // Length conversions (to m)
            'm' => 1.0,
            'cm' => 0.01,
            'mm' => 0.001,
            'km' => 1000.0,
            'ft' => 0.3048,
            'inch' => 0.0254,
            
            // Area conversions (to sqm)
            'sqm' => 1.0,
            'sqft' => 0.092903,
            'sqcm' => 0.0001,
            
            // Count conversions (to piece/unit)
            'piece' => 1.0,
            'pieces' => 1.0,
            'unit' => 1.0,
            'dozen' => 12.0,
            'box' => 1.0,
            'carton' => 1.0,
            'pallet' => 1.0,
            'bag' => 1.0,
            'sack' => 1.0,
            'bundle' => 1.0,
            'set' => 1.0,
            'pair' => 2.0,
            'roll' => 1.0,
            'sheet' => 1.0,
            'panel' => 1.0,
            'tile' => 1.0,
        ];
        
        // Get conversion factors
        $fromFactor = $conversions[$fromUnit] ?? 1.0;
        $toFactor = $conversions[$toUnit] ?? 1.0;
        
        // Calculate conversion: from -> base unit -> to unit
        return $fromFactor / $toFactor;
    }
}
