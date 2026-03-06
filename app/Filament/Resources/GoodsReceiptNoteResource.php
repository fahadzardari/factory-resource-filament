<?php

namespace App\Filament\Resources;

use App\Models\GoodsReceiptNote;
use App\Models\InventoryTransaction;
use App\Services\InventoryTransactionService;
use App\Services\UnitConversionService;
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

                Forms\Components\Section::make('Supplier & Allocation')
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

                        Forms\Components\Select::make('project_id')
                            ->label('Allocate Directly to Project (Optional)')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->hint('If selected, all items will be allocated directly to this project. Leave empty to add items to central warehouse.')
                            ->helperText('This allows you to receive items directly to a project instead of the hub'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Line Items')
                    ->description('Add items received in this delivery. Each item can be in a different unit - will be auto-converted to base unit. Press Ctrl+Enter to add items.')
                    ->schema([
                        Forms\Components\Repeater::make('lineItems')
                            ->relationship('lineItems')
                            ->addActionLabel('➕ Add Item (or press Ctrl+Enter)')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\Select::make('resource_id')
                                            ->label('Resource/Item')
                                            ->relationship('resource', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->columnSpan(1)
                                            ->extraAttributes([
                                                'data-auto-select-on-tab' => true,
                                            ])
                                            ->afterStateUpdated(function (callable $set) {
                                                // This ensures live validation runs
                                            }),

                                        Forms\Components\TextInput::make('quantity_received')
                                            ->label('Quantity')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0.001)
                                            ->step(0.001)
                                            ->live()
                                            ->reactive()
                                            ->extraAttributes([
                                                'data-focus-order' => '1',
                                            ])
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $unitPrice = $get('unit_price');
                                                if ($state && $unitPrice) {
                                                    $set('total_value', round($state * $unitPrice, 2));
                                                }
                                            })
                                            ->columnSpan(1),

                                        Forms\Components\Select::make('receipt_unit')
                                            ->label('Unit')
                                            ->required()
                                            ->options(fn ($get) => self::getUnitOptionsFor($get('resource_id')))
                                            ->searchable()
                                            ->live()
                                            ->reactive()
                                            ->extraAttributes([
                                                'data-focus-order' => '2',
                                            ])
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Price per Unit')
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
                                    ]),

                                Forms\Components\Placeholder::make('conversion_info')
                                    ->content(function ($get) {
                                        $quantity = $get('quantity_received');
                                        $receiptUnit = $get('receipt_unit');
                                        $resourceId = $get('resource_id');
                                        
                                        if (!$quantity || !$receiptUnit || !$resourceId) {
                                            return '📝 Enter quantity, unit, and resource to see conversion details';
                                        }

                                        try {
                                            $resource = \App\Models\Resource::find($resourceId);
                                            if (!$resource) {
                                                return '⚠️ Resource not found';
                                            }

                                            $baseUnit = $resource->base_unit;
                                            $baseUnitObj = \App\Models\Unit::where('code', $baseUnit)->first();
                                            
                                            // Get conversion factor (works bidirectionally now)
                                            $conversionFactor = self::getConversionFactor($receiptUnit, $baseUnit);
                                            
                                            if ($conversionFactor === null) {
                                                return "⚠️ No conversion found between {$receiptUnit} and {$baseUnit}";
                                            }

                                            $convertedQty = $quantity * $conversionFactor;

                                            if ($conversionFactor == 1) {
                                                return "✅ **No conversion needed** - {$receiptUnit} is the base unit";
                                            } else {
                                                $receiptUnitObj = \App\Models\Unit::where('code', $receiptUnit)->first();
                                                $receiptUnitName = $receiptUnitObj?->name ?? $receiptUnit;
                                                $baseUnitName = $baseUnitObj?->name ?? $baseUnit;
                                                
                                                return "📊 **Conversion Rule:** 1 {$receiptUnit} = {$conversionFactor} {$baseUnit}\n\n"
                                                    . "**Your Entry:** {$quantity} {$receiptUnit} = **" . number_format($convertedQty, 4) . " {$baseUnit}**";
                                            }
                                        } catch (\Exception $e) {
                                            return '⚠️ Conversion error: ' . $e->getMessage();
                                        }
                                    })
                                    ->visible(fn ($get) => $get('quantity_received') && $get('receipt_unit') && $get('resource_id'))
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('price_info')
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
                                    ->label('Total Value')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric()
                                    ->step(0.01)
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->minItems(1)
                            ->addActionLabel('➕ Add Item'),
                    ]),

                Forms\Components\Section::make('Additional Details')
                    ->schema([
                        Forms\Components\TextInput::make('delivery_reference')
                            ->label('Delivery Reference / Tracking Number')
                            ->placeholder('e.g., Shipment #, AWB, Invoice #, etc.')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes / Remarks')
                            ->rows(3)
                            ->placeholder('e.g., "Damaged 5 units", "Quality inspection needed", etc.'),
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
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lineItems')
                    ->label('Items')
                    ->formatStateUsing(function ($record) {
                        $items = $record->lineItems ?? [];
                        if ($items->isEmpty()) {
                            return '—';
                        }
                        $count = $items->count();
                        $resources = $items->map(fn ($item) => $item->resource?->name ?? 'Unknown')->join(', ', ' & ');
                        return "{$count} item" . ($count !== 1 ? 's' : '') . ": {$resources}";
                    })
                    ->limit(40),

                Tables\Columns\TextColumn::make('lineItems_total_value')
                    ->label('Total Value')
                    ->formatStateUsing(function ($record) {
                        $total = $record->lineItems?->sum('total_value') ?? 0;
                        return 'AED ' . number_format($total, 2);
                    })
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->color('success')
                    ->badge()
                    ->default('Central Hub')
                    ->toggleable(isToggledHiddenByDefault: false),

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

                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Allocated Project')
                    ->relationship('project', 'name')
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
                Tables\Actions\ViewAction::make(),
            ])
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->bulkActions([])
            ->defaultSort('receipt_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\GoodsReceiptNoteResource\Pages\ListGoodsReceiptNotes::route('/'),
            'create' => \App\Filament\Resources\GoodsReceiptNoteResource\Pages\CreateGoodsReceiptNote::route('/create'),
            'view' => \App\Filament\Resources\GoodsReceiptNoteResource\Pages\ViewGoodsReceiptNote::route('/{record}'),
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
            $service = new UnitConversionService();
            return $service->getConversionOptions($baseUnit);
        } catch (\Exception $e) {
            Log::error('Error getting unit options: ' . $e->getMessage());
            return [];
        }
    }



    /**
     * Get conversion factor from one unit to another using the database-driven service
     */
    protected static function getConversionFactor(string $fromUnit, string $toUnit): float
    {
        $service = new UnitConversionService();
        $factor = $service->getConversionFactor($fromUnit, $toUnit);
        return $factor ?? 1.0;
    }
}
