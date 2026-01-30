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
                    
                    Forms\Components\TextInput::make('quantity')
                        ->label('Quantity Purchased')
                        ->helperText('How much did you buy?')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->suffix(fn () => $this->record->base_unit),
                    
                    Forms\Components\TextInput::make('unit_price')
                        ->label('Price per Unit')
                        ->helperText('Cost for one ' . ($this->record->base_unit ?? 'unit'))
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
                        $service->recordPurchase(
                            $this->record,
                            $data['quantity'],
                            $data['unit_price'],
                            \Carbon\Carbon::parse($data['transaction_date'])->format('Y-m-d'),
                            $data['supplier'] ?? null,
                            $data['invoice_number'] ?? null,
                            $data['notes'] ?? null
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('✅ Purchase Recorded Successfully!')
                            ->body("Added {$data['quantity']} {$this->record->base_unit} of {$this->record->name} to Central Hub inventory.")
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
                            return Project::where('status', 'Active')
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
}

