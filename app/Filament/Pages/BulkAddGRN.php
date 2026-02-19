<?php

namespace App\Filament\Pages;

use App\Models\GoodsReceiptNote;
use App\Models\Resource;
use App\Models\Supplier;
use App\Services\InventoryTransactionService;
use App\Services\UnitConversionService;
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
    protected static bool $shouldRegisterNavigation = false;
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
                ->description('Add multiple GRN records at once. Empty rows will be automatically skipped. Use Tab or Ctrl+Enter to add new rows.')
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
                        ->addActionLabel('➕ Add another GRN (Ctrl+Enter)')
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
            $createdCount = 0;
            $service = app(InventoryTransactionService::class);
            
            DB::transaction(function () use ($grns, &$createdCount, $service) {
                foreach ($grns as $grn) {
                    $quantity = (float) $grn['quantity_received'];
                    $unitPrice = (float) $grn['unit_price'];
                    $resourceId = (int) $grn['resource_id'];
                    $receiptUnit = $grn['receipt_unit'] ?? null;
                    
                    // Get the resource
                    $resource = Resource::find($resourceId);
                    if (!$resource) {
                        throw new \Exception("Resource {$resourceId} not found");
                    }
                    
                    // Determine receipt unit (defaultto base unit if not provided)
                    if (!$receiptUnit) {
                        $receiptUnit = $resource->base_unit;
                    }
                    
                    $receiptDate = $grn['receipt_date'] 
                        ? Carbon::parse($grn['receipt_date'])->format('Y-m-d')
                        : now()->format('Y-m-d');
                    
                    // Create the GRN header (without resource_id - it's now in line items)
                    $grnRecord = GoodsReceiptNote::create([
                        'supplier_id' => (int) $grn['supplier_id'],
                        'receipt_date' => $receiptDate,
                        'delivery_reference' => $grn['delivery_reference'] ?? null,
                        'notes' => $grn['notes'] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                    
                    // Create line item for this GRN
                    \App\Models\GoodsReceiptNoteLineItem::create([
                        'grn_id' => $grnRecord->id,
                        'resource_id' => $resourceId,
                        'quantity_received' => $quantity,
                        'receipt_unit' => $receiptUnit,
                        'unit_price' => $unitPrice,
                        'total_value' => round($quantity * $unitPrice, 2),
                    ]);
                    
                    // Record inventory transaction
                    try {
                        $service->recordGoodsReceipt($grnRecord, Auth::user());
                    } catch (\Exception $e) {
                        Log::warning('Failed to record inventory transaction for GRN: ' . $e->getMessage());
                        // Continue even if inventory transaction fails
                    }
                    
                    $createdCount++;
                }
            });
            
            Notification::make()
                ->success()
                ->title("✅ Success! {$createdCount} Goods Receipt" . ($createdCount > 1 ? 's' : '') . " Created")
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
            $service = new UnitConversionService();
            return $service->getConversionOptions($baseUnit);
        } catch (\Exception $e) {
            return [];
        }
    }

}
