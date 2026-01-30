<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Models\Project;
use App\Services\InventoryTransactionService;
use App\Exports\ResourceTransactionsExport;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Maatwebsite\Excel\Facades\Excel;

class ViewResource extends ViewRecord
{
    protected static string $resource = ResourceResource::class;

    public $filterDateFrom;
    public $filterDateTo;

    public function mount($record): void
    {
        parent::mount($record);
        $this->filterDateFrom = now()->subDays(30)->format('Y-m-d');
        $this->filterDateTo = now()->format('Y-m-d');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Resource Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                        
                        Infolists\Components\TextEntry::make('sku')
                            ->label('SKU')
                            ->badge()
                            ->color('primary'),
                        
                        Infolists\Components\TextEntry::make('category')
                            ->badge()
                            ->color('info'),
                        
                        Infolists\Components\TextEntry::make('base_unit')
                            ->label('Unit of Measurement'),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Inventory Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('hub_stock')
                            ->label('Hub Stock')
                            ->formatStateUsing(fn ($state, $record) => number_format($state, 2) . ' ' . $record->base_unit)
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('success'),
                        
                        Infolists\Components\TextEntry::make('hub_value')
                            ->label('Total Hub Value')
                            ->money('AED')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('warning'),
                        
                        Infolists\Components\TextEntry::make('total_stock')
                            ->label('Total Stock (All Locations)')
                            ->formatStateUsing(fn ($state, $record) => number_format($state, 2) . ' ' . $record->base_unit)
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Recent Transactions')
                    ->description('Last 50 transactions for this resource')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('inventoryTransactions')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('transaction_date')
                                    ->label('Date')
                                    ->date()
                                    ->columnSpan(1),
                                
                                Infolists\Components\TextEntry::make('transaction_type')
                                    ->label('Type')
                                    ->badge()
                                    ->columnSpan(1)
                                    ->color(fn (string $state): string => match ($state) {
                                        'PURCHASE' => 'success',
                                        'ALLOCATION_OUT' => 'warning',
                                        'ALLOCATION_IN' => 'info',
                                        'CONSUMPTION' => 'danger',
                                        'TRANSFER_OUT' => 'gray',
                                        'TRANSFER_IN' => 'primary',
                                        default => 'secondary',
                                    }),
                                
                                Infolists\Components\TextEntry::make('project.name')
                                    ->label('Project')
                                    ->default('Hub')
                                    ->columnSpan(2),
                                
                                Infolists\Components\TextEntry::make('quantity')
                                    ->formatStateUsing(fn ($state, $record) => number_format($state, 2))
                                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                                    ->columnSpan(1),
                                
                                Infolists\Components\TextEntry::make('unit_price')
                                    ->money('AED')
                                    ->columnSpan(1),
                                
                                Infolists\Components\TextEntry::make('total_value')
                                    ->money('AED')
                                    ->weight('bold')
                                    ->columnSpan(1),
                            ])
                            ->columns(7)
                            ->state(fn ($record) => $record->inventoryTransactions()->latest('transaction_date')->limit(50)->get()),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('purchase')
                ->label('Purchase')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->modalHeading('🛒 Record New Purchase')
                ->modalDescription('Add newly purchased materials to the Central Hub inventory.')
                ->modalIcon('heroicon-o-shopping-cart')
                ->form([
                    Forms\Components\Placeholder::make('info')
                        ->content('📦 Record materials you have purchased and received. This will increase the Hub inventory. Make sure to enter accurate quantities and prices for proper tracking.')
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
                                ->helperText('Select the unit you purchased in')
                                ->options(function () {
                                    $baseUnit = $this->record->base_unit;
                                    return $this->getUnitConversionOptions($baseUnit);
                                })
                                ->default(fn () => $this->record->base_unit)
                                ->required()
                                ->reactive()
                                ->searchable()
                                ->columnSpan(1),
                        ]),
                    
                    Forms\Components\Placeholder::make('conversion_info')
                        ->content(function ($get) {
                            $quantity = $get('quantity') ?? 0;
                            $purchaseUnit = $get('purchase_unit') ?? $this->record->base_unit;
                            $baseUnit = $this->record->base_unit;
                            
                            if ($quantity && $purchaseUnit) {
                                $conversion = $this->getConversionFactor($purchaseUnit, $baseUnit);
                                $convertedQty = $quantity * $conversion;
                                
                                if ($conversion != 1) {
                                    return "📊 **Conversion:** {$quantity} {$purchaseUnit} = " . number_format($convertedQty, 2) . " {$baseUnit} (will be stored in inventory)";
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
                        ->helperText(fn ($get) => 'Cost for one ' . ($get('purchase_unit') ?? $this->record->base_unit))
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('AED'),
                    
                    Forms\Components\DatePicker::make('transaction_date')
                        ->label('Purchase Date')
                        ->helperText('When did you receive this delivery?')
                        ->required()
                        ->default(today())
                        ->maxDate(today()),
                    
                    Forms\Components\TextInput::make('supplier')
                        ->label('Supplier Name (Optional)')
                        ->helperText('Who did you buy this from?')
                        ->placeholder('Example: ABC Building Materials LLC')
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('invoice_number')
                        ->label('Invoice/Receipt Number (Optional)')
                        ->helperText('Reference number from the supplier')
                        ->placeholder('Example: INV-2026-001234')
                        ->maxLength(255),
                    
                    Forms\Components\Textarea::make('notes')
                        ->label('Additional Notes (Optional)')
                        ->helperText('Any other details about this purchase')
                        ->placeholder('Example: Good quality materials, delivered on time')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $service = app(InventoryTransactionService::class);
                    
                    try {
                        // Convert quantity to base unit
                        $purchaseUnit = $data['purchase_unit'] ?? $this->record->base_unit;
                        $conversionFactor = $this->getConversionFactor($purchaseUnit, $this->record->base_unit);
                        $quantityInBaseUnit = $data['quantity'] * $conversionFactor;
                        
                        // Calculate price per base unit
                        $pricePerBaseUnit = $data['unit_price'] / $conversionFactor;
                        
                        $service->recordPurchase(
                            $this->record,
                            $quantityInBaseUnit,
                            $pricePerBaseUnit,
                            \Carbon\Carbon::parse($data['transaction_date'])->format('Y-m-d'),
                            $data['supplier'] ?? null,
                            $data['invoice_number'] ?? null,
                            ($data['notes'] ?? '') . ($conversionFactor != 1 ? "\nPurchased as: {$data['quantity']} {$purchaseUnit} (converted to {$quantityInBaseUnit} {$this->record->base_unit})" : '')
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('✅ Purchase Recorded Successfully!')
                            ->body("Added {$data['quantity']} {$purchaseUnit} (" . number_format($quantityInBaseUnit, 2) . " {$this->record->base_unit}) of {$this->record->name} to Central Hub inventory.")
                            ->send();
                        
                        $this->refreshFormData(['infolist']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('❌ Purchase Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make('allocate')
                ->label('Allocate to Project')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('warning')
                ->modalHeading('🚚 Allocate to Project')
                ->modalDescription('Send materials from Central Hub to a project site.')
                ->modalIcon('heroicon-o-truck')
                ->form([
                    Forms\Components\Placeholder::make('info')
                        ->content('📦 Move materials from the Central Hub to a project site. This will reduce Hub inventory and increase the project\'s inventory.')
                        ->columnSpanFull(),
                    
                    Forms\Components\Select::make('project_id')
                        ->label('Select Project')
                        ->helperText('Which project needs these materials?')
                        ->options(function () {
                            return Project::where('status', 'active')
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} ({$p->code})"]);
                        })
                        ->required()
                        ->searchable(),
                    
                    Forms\Components\TextInput::make('quantity')
                        ->label('Quantity to Allocate')
                        ->helperText(fn () => "⚠️ Available at Hub: {$this->record->hub_stock} {$this->record->base_unit} - Don't exceed this!")
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->suffix(fn () => $this->record->base_unit),
                    
                    Forms\Components\DatePicker::make('transaction_date')
                        ->label('Allocation Date')
                        ->helperText('When are these materials being sent?')
                        ->required()
                        ->default(today())
                        ->maxDate(today()),
                    
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes (Optional)')
                        ->helperText('Add any remarks about this allocation')
                        ->placeholder('Example: Materials for foundation work phase 2')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $service = app(InventoryTransactionService::class);
                    
                    try {
                        $service->recordAllocation(
                            $this->record,
                            Project::find($data['project_id']),
                            $data['quantity'],
                            \Carbon\Carbon::parse($data['transaction_date'])->format('Y-m-d'),
                            $data['notes'] ?? null
                        );
                        
                        $project = Project::find($data['project_id']);
                        
                        Notification::make()
                            ->success()
                            ->title('✅ Allocation Successful!')
                            ->body("Allocated {$data['quantity']} {$this->record->base_unit} of {$this->record->name} to {$project->name}.")
                            ->send();
                        
                        $this->refreshFormData(['infolist']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('❌ Allocation Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make('export')
                ->label('Export Transactions')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->modalHeading('📊 Export Transaction History')
                ->modalDescription('Download a complete history of all transactions for this resource.')
                ->form([
                    Forms\Components\Placeholder::make('info')
                        ->content('📋 Export all movements of this material (purchases, allocations, transfers, consumption) within your selected date range. Perfect for auditing and cost analysis.')
                        ->columnSpanFull(),
                    
                    Forms\Components\DatePicker::make('date_from')
                        ->label('From Date (Optional)')
                        ->helperText('Leave empty to include transactions from the beginning')
                        ->default(now()->subDays(30))
                        ->maxDate(today()),
                    
                    Forms\Components\DatePicker::make('date_to')
                        ->label('To Date (Optional)')
                        ->helperText('Leave empty to include all transactions up to today')
                        ->default(today())
                        ->maxDate(today()),
                ])
                ->action(function (array $data) {
                    $filename = 'resource_' . $this->record->sku . '_transactions_' . now()->format('Y-m-d') . '.xlsx';
                    
                    return Excel::download(
                        new ResourceTransactionsExport(
                            $this->record->id,
                            $data['date_from'] ?? null,
                            $data['date_to'] ?? null
                        ),
                        $filename
                    );
                }),
            
            Actions\EditAction::make(),
        ];
    }

    /**
     * Get available unit conversion options based on base unit
     */
    protected function getUnitConversionOptions(string $baseUnit): array
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
    protected function getConversionFactor(string $fromUnit, string $toUnit): float
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
            'box' => 1.0, // Default, can be customized per resource
            'carton' => 1.0, // Default, can be customized per resource
            'pallet' => 1.0, // Default, can be customized per resource
            'bag' => 1.0, // Default, typically 50kg for cement
            'sack' => 1.0, // Default, typically 50kg
            'bundle' => 1.0, // Default, can be customized per resource
            'set' => 1.0, // Default, can be customized per resource
            'pair' => 2.0, // 2 pieces
            'roll' => 1.0, // Single unit
            'sheet' => 1.0, // Single unit
            'panel' => 1.0, // Single unit
            'tile' => 1.0 // Single unit
        ];
        
        // Get conversion factors
        $fromFactor = $conversions[$fromUnit] ?? 1.0;
        $toFactor = $conversions[$toUnit] ?? 1.0;
        
        // Calculate conversion: from -> base unit -> to unit
        return $fromFactor / $toFactor;
    }
}

