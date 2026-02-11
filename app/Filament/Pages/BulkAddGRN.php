<?php

namespace App\Filament\Pages;

use App\Models\GoodsReceiptNote;
use App\Models\Resource;
use App\Models\Supplier;
use App\Services\InventoryTransactionService;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkAddGRN extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = '📦 Bulk Add GRN Records';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Bulk Operations';
    protected static string $view = 'filament.pages.bulk-add-grn';
    protected static ?string $title = 'Bulk Add Goods Receipt Notes';

    public array $grns = [];

    public function mount(): void
    {
        // Initialize form with 5 empty rows
        $this->form->fill([
            'grns' => array_fill(0, 5, []),
        ]);
    }

    protected function getFormModel(): ?string
    {
        return null;
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Bulk Goods Receipt Entry')
                ->description('Add multiple GRN records at once. Empty rows will be automatically skipped.')
                ->icon('heroicon-o-arrow-down-tray')
                ->schema([
                    Forms\Components\Repeater::make('grns')
                        ->schema([
                            Forms\Components\Select::make('supplier_id')
                                ->label('Supplier')
                                ->options(Supplier::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->nullable()
                                ->columnSpan(3),
                            
                            Forms\Components\Select::make('resource_id')
                                ->label('Resource/Item')
                                ->options(Resource::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->nullable()
                                ->live()
                                ->columnSpan(3),
                            
                            Forms\Components\TextInput::make('quantity_received')
                                ->label('Qty')
                                ->numeric()
                                ->minValue(0.001)
                                ->step(0.001)
                                ->nullable()
                                ->live()
                                ->columnSpan(1),
                            
                            Forms\Components\Select::make('receipt_unit')
                                ->label('Unit')
                                ->options(fn ($get) => $this->getUnitOptionsForResource($get('resource_id')))
                                ->searchable()
                                ->nullable()
                                ->live()
                                ->columnSpan(2),
                            
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Price')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('AED')
                                ->nullable()
                                ->columnSpan(1),
                            
                            Forms\Components\DatePicker::make('receipt_date')
                                ->label('Date')
                                ->default(now())
                                ->maxDate(now())
                                ->nullable()
                                ->columnSpan(2),
                            
                            Forms\Components\TextInput::make('delivery_reference')
                                ->label('Delivery Reference')
                                ->placeholder('e.g., SHIP-2026-0001')
                                ->nullable()
                                ->columnSpanFull(),
                            
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->placeholder('Optional remarks about the receipt')
                                ->nullable()
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(12)
                        ->defaultItems(5)
                        ->minItems(1)
                        ->addActionLabel('➕ Add another GRN')
                        ->collapsible()
                        ->cloneable()
                        ->reorderable(false)
                        ->columnSpanFull(),
                ]),
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $grns = $data['grns'] ?? [];
        
        // Filter out incomplete rows - only if all 4 required fields are present
        $grns = array_filter($grns, function ($row) {
            return !empty($row['supplier_id']) 
                && !empty($row['resource_id']) 
                && !empty($row['quantity_received']) 
                && !empty($row['unit_price']);
        });
        
        if (empty($grns)) {
            Notification::make()
                ->danger()
                ->title('❌ No Valid GRNs')
                ->body('Please fill in at least one complete row (Supplier, Resource, Quantity, Unit Price).')
                ->send();
            return;
        }
        
        try {
            $count = 0;
            $service = app(InventoryTransactionService::class);
            
            DB::transaction(function () use ($grns, &$count, $service) {
                foreach ($grns as $grn) {
                    $quantity = (float) $grn['quantity_received'];
                    $unitPrice = (float) $grn['unit_price'];
                    $resourceId = (int) $grn['resource_id'];
                    $receiptUnit = $grn['receipt_unit'] ?? null;
                    
                    // Handle unit conversion if receipt_unit is provided
                    if ($resourceId && $receiptUnit) {
                        $resource = Resource::find($resourceId);
                        if ($resource) {
                            $baseUnit = $resource->base_unit;
                            // Only convert if units are different
                            if (strtolower($receiptUnit) !== strtolower($baseUnit)) {
                                $conversionFactor = $this->getConversionFactor($receiptUnit, $baseUnit);
                                $quantity = round($quantity * $conversionFactor, 3);
                                $unitPrice = round($unitPrice / $conversionFactor, 2);
                            }
                        }
                    }
                    
                    $receiptDate = $grn['receipt_date'] 
                        ? Carbon::parse($grn['receipt_date'])->format('Y-m-d')
                        : now()->format('Y-m-d');
                    
                    // Create the GRN
                    $grnRecord = GoodsReceiptNote::create([
                        'supplier_id' => (int) $grn['supplier_id'],
                        'resource_id' => $resourceId,
                        'quantity_received' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_value' => round($quantity * $unitPrice, 2),
                        'receipt_date' => $receiptDate,
                        'delivery_reference' => $grn['delivery_reference'] ?? null,
                        'notes' => $grn['notes'] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                    
                    // Record inventory transaction
                    try {
                        $service->recordGoodsReceipt($grnRecord, Auth::user());
                    } catch (\Exception $e) {
                        Log::warning('Failed to record inventory transaction for GRN: ' . $e->getMessage());
                        // Continue even if inventory transaction fails
                    }
                    
                    $count++;
                }
            });
            
            Notification::make()
                ->success()
                ->title("✅ Success! {$count} Goods Receipt" . ($count > 1 ? 's' : '') . " Created")
                ->body("All GRN records have been created and inventory has been updated automatically.")
                ->duration(5)
                ->send();
            
            // Reset form
            $this->form->fill([
                'grns' => array_fill(0, 5, []),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Bulk GRN creation failed: ' . $e->getMessage());
            Notification::make()
                ->danger()
                ->title('❌ Bulk Create Failed')
                ->body('Error: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Get unit options for a specific resource
     */
    private function getUnitOptionsForResource(?int $resourceId): array
    {
        if (!$resourceId) {
            return [];
        }

        try {
            $resource = Resource::find($resourceId);
            if (!$resource) {
                return [];
            }

            $baseUnit = $resource->base_unit;
            return $this->getUnitConversionOptions($baseUnit);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get available unit conversion options based on base unit
     */
    private function getUnitConversionOptions(string $baseUnit): array
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
            'mg' => [
                'mg' => 'Milligrams (mg) - Base Unit',
                'g' => 'Grams (g)',
                'kg' => 'Kilograms (kg)',
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
                'g' => 'Grams (g)',
                'oz' => 'Ounces (oz)',
            ],
            'oz' => [
                'oz' => 'Ounces (oz) - Base Unit',
                'lb' => 'Pounds (lb)',
                'g' => 'Grams (g)',
                'kg' => 'Kilograms (kg)',
            ],
            'liter' => [
                'liter' => 'Liters (L) - Base Unit',
                'liters' => 'Liters (L)',
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
                'ft' => 'Feet (ft)',
                'm' => 'Meters (m)',
                'cm' => 'Centimeters (cm)',
            ],
            'km' => [
                'km' => 'Kilometers (km) - Base Unit',
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
                'sqcm' => 'Square Centimeters (cm²)',
            ],
            'sqcm' => [
                'sqcm' => 'Square Centimeters (cm²) - Base Unit',
                'sqm' => 'Square Meters (m²)',
                'sqft' => 'Square Feet (ft²)',
            ],
            'piece' => [
                'piece' => 'Pieces - Base Unit',
                'pieces' => 'Pieces',
                'unit' => 'Units',
                'dozen' => 'Dozens',
                'box' => 'Boxes',
                'carton' => 'Cartons',
            ],
            'pieces' => [
                'pieces' => 'Pieces - Base Unit',
                'piece' => 'Pieces',
                'unit' => 'Units',
                'dozen' => 'Dozens',
                'box' => 'Boxes',
                'carton' => 'Cartons',
            ],
            'unit' => [
                'unit' => 'Units - Base Unit',
                'piece' => 'Pieces',
                'pieces' => 'Pieces',
                'dozen' => 'Dozens',
                'box' => 'Boxes',
            ],
            'dozen' => [
                'dozen' => 'Dozens - Base Unit',
                'unit' => 'Units',
                'piece' => 'Pieces',
                'pieces' => 'Pieces',
            ],
            'box' => [
                'box' => 'Boxes - Base Unit',
                'piece' => 'Pieces',
                'pieces' => 'Pieces',
                'carton' => 'Cartons',
                'pallet' => 'Pallets',
            ],
            'carton' => [
                'carton' => 'Cartons - Base Unit',
                'box' => 'Boxes',
                'piece' => 'Pieces',
                'pallet' => 'Pallets',
            ],
            'pallet' => [
                'pallet' => 'Pallets - Base Unit',
                'carton' => 'Cartons',
                'box' => 'Boxes',
            ],
            'bag' => [
                'bag' => 'Bags - Base Unit',
                'piece' => 'Pieces',
                'sack' => 'Sacks',
            ],
            'sack' => [
                'sack' => 'Sacks - Base Unit',
                'bag' => 'Bags',
                'piece' => 'Pieces',
            ],
            'bundle' => [
                'bundle' => 'Bundles - Base Unit',
                'piece' => 'Pieces',
                'set' => 'Sets',
            ],
            'set' => [
                'set' => 'Sets - Base Unit',
                'bundle' => 'Bundles',
                'piece' => 'Pieces',
            ],
            'pair' => [
                'pair' => 'Pairs - Base Unit',
                'piece' => 'Pieces',
            ],
            'roll' => [
                'roll' => 'Rolls - Base Unit',
                'piece' => 'Pieces',
                'sheet' => 'Sheets',
            ],
            'sheet' => [
                'sheet' => 'Sheets - Base Unit',
                'roll' => 'Rolls',
                'piece' => 'Pieces',
            ],
            'panel' => [
                'panel' => 'Panels - Base Unit',
                'piece' => 'Pieces',
                'sheet' => 'Sheets',
            ],
            'tile' => [
                'tile' => 'Tiles - Base Unit',
                'piece' => 'Pieces',
                'box' => 'Boxes',
            ],
        ];

        return $conversionMap[strtolower($baseUnit)] ?? [
            $baseUnit => $baseUnit . ' - Base Unit',
        ];
    }

    /**
     * Get conversion factor from one unit to another
     */
    private function getConversionFactor(string $fromUnit, string $toUnit): float
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
