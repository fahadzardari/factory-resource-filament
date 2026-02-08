<?php

namespace App\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Filament\Resources\GoodsReceiptNoteResource;
use App\Models\GoodsReceiptNote;
use App\Models\Resource;
use App\Models\Supplier;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class ListGoodsReceiptNotes extends ListRecords
{
    protected static string $resource = GoodsReceiptNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('bulk_create_grns')
                ->label('📦 Bulk Create GRNs')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->modalHeading('📦 Bulk Goods Receipt Entry')
                ->modalDescription('Add multiple GRN records at once. Empty rows will be skipped.')
                ->modalWidth('7xl')
                ->form([
                    Forms\Components\Placeholder::make('info')
                        ->content('✅ **Quick Bulk Entry:** Add multiple goods receipts. Fill in the fields below and empty rows will be automatically skipped.')
                        ->columnSpanFull(),
                    
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
                                ->label('Qty')
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
                                ->label('Date')
                                ->required()
                                ->default(now())
                                ->maxDate(now())
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('delivery_reference')
                                ->label('Delivery Ref')
                                ->placeholder('Tracking#')
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('notes')
                                ->label('Notes')
                                ->placeholder('Optional remarks')
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
                ])
                ->action(function (array $data) {
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
                            ->body('Please fill in at least one complete row.')
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
                            ->title("✅ Success! {$count} GRN" . ($count > 1 ? 's' : '') . " created")
                            ->body("All goods receipts have been recorded and inventory updated.")
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('❌ Bulk Create Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}

