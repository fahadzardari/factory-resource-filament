<?php

namespace App\Filament\Pages;

use App\Models\GoodsReceiptNote;
use App\Models\Resource;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
                                ->required()
                                ->columnSpan(2),
                            
                            Forms\Components\Select::make('resource_id')
                                ->label('Resource/Item')
                                ->options(Resource::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->columnSpan(2),
                            
                            Forms\Components\TextInput::make('quantity_received')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->minValue(0.001)
                                ->step(0.001)
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->prefix('AED')
                                ->columnSpan(1),
                            
                            Forms\Components\DatePicker::make('receipt_date')
                                ->label('Receipt Date')
                                ->required()
                                ->default(now())
                                ->maxDate(now())
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('delivery_reference')
                                ->label('Delivery Reference')
                                ->placeholder('e.g., SHIP-2026-0001')
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('notes')
                                ->label('Notes')
                                ->placeholder('Optional remarks about the receipt')
                                ->columnSpan(2),
                        ])
                        ->columns(6)
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
        
        // Filter out incomplete rows
        $grns = array_filter($grns, function ($row) {
            return !empty($row['supplier_id']) && !empty($row['resource_id']) 
                   && !empty($row['quantity_received']) && !empty($row['unit_price']);
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
            foreach ($grns as $grn) {
                GoodsReceiptNote::create([
                    'supplier_id' => $grn['supplier_id'],
                    'resource_id' => $grn['resource_id'],
                    'quantity_received' => $grn['quantity_received'],
                    'unit_price' => $grn['unit_price'],
                    'total_value' => $grn['quantity_received'] * $grn['unit_price'],
                    'receipt_date' => Carbon::parse($grn['receipt_date'])->format('Y-m-d'),
                    'delivery_reference' => $grn['delivery_reference'] ?? null,
                    'notes' => $grn['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
                $count++;
            }
            
            Notification::make()
                ->success()
                ->title("✅ Success! {$count} Goods Receipt" . ($count > 1 ? 's' : '') . " Created")
                ->body("All GRN records have been created and inventory has been updated automatically.")
                ->duration(5)
                ->send();
            
            // Reset form
            $this->form->fill();
            
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('❌ Bulk Create Failed')
                ->body('Error: ' . $e->getMessage())
                ->send();
        }
    }
}
