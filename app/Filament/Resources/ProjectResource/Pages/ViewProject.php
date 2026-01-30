<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Resource as ResourceModel;
use App\Models\Project;
use App\Services\InventoryTransactionService;
use App\Exports\DailyConsumptionExport;
use App\Exports\ProjectResourceUsageExport;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Project Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Project Name')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),
                        
                        Infolists\Components\TextEntry::make('code')
                            ->label('Project Code')
                            ->badge()
                            ->color('primary'),
                        
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match (strtolower($state)) {
                                'pending' => 'gray',
                                'active' => 'success',
                                'completed' => 'info',
                                default => 'gray',
                            }),
                        
                        Infolists\Components\TextEntry::make('location')
                            ->icon('heroicon-o-map-pin'),
                        
                        Infolists\Components\TextEntry::make('start_date')
                            ->date(),
                        
                        Infolists\Components\TextEntry::make('end_date')
                            ->date(),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Resources at Project Site')
                    ->description('Current inventory available at this project location')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('resourceStocks')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('resource_name')
                                    ->label('Resource')
                                    ->columnSpan(2),
                                
                                Infolists\Components\TextEntry::make('sku')
                                    ->label('SKU')
                                    ->badge()
                                    ->columnSpan(1),
                                
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Available Quantity')
                                    ->formatStateUsing(fn ($state, $record) => number_format($state, 2) . ' ' . $record['unit'])
                                    ->weight('bold')
                                    ->color('success')
                                    ->columnSpan(2),
                            ])
                            ->columns(5)
                            ->state(function ($record) {
                                $stocks = \App\Services\StockCalculator::getProjectResourceStocks($record->id);
                                return collect($stocks)->map(function ($stock) {
                                    return [
                                        'resource_name' => $stock['resource_name'],
                                        'sku' => $stock['sku'],
                                        'quantity' => $stock['quantity'],
                                        'unit' => $stock['unit'],
                                    ];
                                })->toArray();
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Infolists\Components\Section::make('Recent Transactions')
                    ->description('Last 50 transactions for this project')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('transactions')
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
                                
                                Infolists\Components\TextEntry::make('resource.name')
                                    ->label('Resource')
                                    ->columnSpan(2),
                                
                                Infolists\Components\TextEntry::make('quantity')
                                    ->formatStateUsing(fn ($state) => number_format($state, 2))
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
            Actions\Action::make('allocate')
                ->label('Allocate Resource')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'Active')
                ->form([
                    Forms\Components\Select::make('resource_id')
                        ->label('Resource')
                        ->options(function () {
                            return \App\Models\Resource::all()->pluck('name', 'id');
                        })
                        ->required()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $stock = \App\Services\StockCalculator::getHubStock($state);
                                $set('available_stock', $stock);
                            }
                        }),
                    
                    Forms\Components\Placeholder::make('available_info')
                        ->label('')
                        ->content(function ($get) {
                            if ($resourceId = $get('resource_id')) {
                                $resource = \App\Models\Resource::find($resourceId);
                                $stock = \App\Services\StockCalculator::getHubStock($resourceId);
                                return "Available at Hub: {$stock} {$resource->base_unit}";
                            }
                            return '';
                        })
                        ->visible(fn ($get) => $get('resource_id')),
                    
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->label('Quantity to Allocate'),
                    
                    Forms\Components\DatePicker::make('transaction_date')
                        ->label('Transaction Date')
                        ->required()
                        ->default(today())
                        ->maxDate(today()),
                    
                    Forms\Components\Textarea::make('notes')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $service = app(InventoryTransactionService::class);
                    
                    try {
                        $service->recordAllocation(
                            ResourceModel::find($data['resource_id']),
                            $this->record,
                            $data['quantity'],
                            \Carbon\Carbon::parse($data['transaction_date'])->format('Y-m-d'),
                            $data['notes'] ?? null
                        );
                        
                        Notification::make()
                            ->title('Resource allocated successfully')
                            ->success()
                            ->send();
                        
                        $this->refreshFormData(['infolist']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error allocating resource')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('consume')
                ->label('Consume Resource')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'active')
                ->form([
                    Forms\Components\Select::make('resource_id')
                        ->label('Resource')
                        ->options(function () {
                            $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                            return collect($stocks)->pluck('resource_name', 'resource_id');
                        })
                        ->required()
                        ->searchable()
                        ->reactive(),
                    
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->helperText(function ($get) {
                            if ($resourceId = $get('resource_id')) {
                                $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                                $stock = collect($stocks)->firstWhere('resource_id', $resourceId);
                                return $stock ? "Available: {$stock['quantity']} {$stock['unit']}" : '';
                            }
                            return '';
                        }),
                    
                    Forms\Components\DatePicker::make('transaction_date')
                        ->label('Transaction Date')
                        ->required()
                        ->default(today())
                        ->maxDate(today()),
                    
                    Forms\Components\Textarea::make('notes')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $service = app(InventoryTransactionService::class);
                    
                    try {
                        $service->recordConsumption(
                            ResourceModel::find($data['resource_id']),
                            $this->record,
                            $data['quantity'],
                            \Carbon\Carbon::parse($data['transaction_date'])->format('Y-m-d'),
                            $data['notes'] ?? null
                        );
                        
                        $resource = ResourceModel::find($data['resource_id']);
                        
                        Notification::make()
                            ->success()
                            ->title('Consumption Recorded')
                            ->body("Consumed {$data['quantity']} {$resource->base_unit} of {$resource->name}.")
                            ->send();
                        
                        redirect(request()->header('Referer'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Consumption Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make('transfer')
                ->label('Transfer Resource')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'Active')
                ->form([
                    Forms\Components\Select::make('resource_id')
                        ->label('Resource')
                        ->options(function () {
                            $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                            return collect($stocks)->pluck('resource_name', 'resource_id');
                        })
                        ->required()
                        ->searchable()
                        ->reactive(),
                    
                    Forms\Components\Select::make('to_project_id')
                        ->label('To Project')
                        ->options(Project::where('status', 'Active')->where('id', '!=', $this->record->id)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->helperText(function ($get) {
                            if ($resourceId = $get('resource_id')) {
                                $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                                $stock = collect($stocks)->firstWhere('resource_id', $resourceId);
                                return $stock ? "Available: {$stock['quantity']} {$stock['unit']}" : '';
                            }
                            return '';
                        }),
                    
                    Forms\Components\DatePicker::make('transaction_date')
                        ->label('Transaction Date')
                        ->required()
                        ->default(today())
                        ->maxDate(today()),
                    
                    Forms\Components\Textarea::make('notes')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $service = app(InventoryTransactionService::class);
                    
                    try {
                        $service->recordTransfer(
                            ResourceModel::find($data['resource_id']),
                            $this->record,
                            Project::find($data['to_project_id']),
                            $data['quantity'],
                            \Carbon\Carbon::parse($data['transaction_date'])->format('Y-m-d'),
                            $data['notes'] ?? null
                        );
                        
                        $resource = ResourceModel::find($data['resource_id']);
                        $toProject = Project::find($data['to_project_id']);
                        
                        Notification::make()
                            ->success()
                            ->title('Transfer Successful')
                            ->body("Transferred {$data['quantity']} {$resource->base_unit} of {$resource->name} to {$toProject->name}.")
                            ->send();
                        
                        redirect(request()->header('Referer'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Transfer Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make('exportDailyConsumption')
                ->label('Export Daily Consumption')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->form([
                    Forms\Components\DatePicker::make('date')
                        ->label('Date')
                        ->default(today())
                        ->required()
                        ->maxDate(today()),
                ])
                ->action(function (array $data) {
                    $filename = 'project_' . $this->record->code . '_daily_consumption_' . $data['date'] . '.xlsx';
                    
                    return Excel::download(
                        new DailyConsumptionExport($this->record->id, $data['date']),
                        $filename
                    );
                }),

            Actions\Action::make('exportResourceUsage')
                ->label('Export Resource Usage')
                ->icon('heroicon-o-document-chart-bar')
                ->color('success')
                ->form([
                    Forms\Components\DatePicker::make('date_from')
                        ->label('From Date')
                        ->default(now()->subDays(30))
                        ->maxDate(today()),
                    
                    Forms\Components\DatePicker::make('date_to')
                        ->label('To Date')
                        ->default(today())
                        ->maxDate(today()),
                ])
                ->action(function (array $data) {
                    $filename = 'project_' . $this->record->code . '_resource_usage_' . now()->format('Y-m-d') . '.xlsx';
                    
                    return Excel::download(
                        new ProjectResourceUsageExport(
                            $this->record->id,
                            $data['date_from'] ?? null,
                            $data['date_to'] ?? null
                        ),
                        $filename
                    );
                }),

            Actions\Action::make('completeProject')
                ->label('Complete Project')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'Active')
                ->requiresConfirmation()
                ->modalHeading('Complete Project')
                ->modalDescription('What would you like to do with remaining resources at this project site?')
                ->form(function () {
                    $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                    
                    if (empty($stocks)) {
                        return [
                            Forms\Components\Placeholder::make('no_resources')
                                ->content('No resources remaining at this project site. Project can be completed directly.'),
                        ];
                    }

                    $formFields = [];
                    foreach ($stocks as $stock) {
                        $formFields[] = Forms\Components\Section::make($stock['resource_name'] . ' (' . $stock['sku'] . ')')
                            ->description('Available: ' . number_format($stock['quantity'], 2) . ' ' . $stock['unit'])
                            ->schema([
                                Forms\Components\Select::make('action_' . $stock['resource_id'])
                                    ->label('Action')
                                    ->options([
                                        'return_hub' => 'Return to Hub',
                                        'transfer' => 'Transfer to Another Project',
                                        'keep' => 'Keep at Project (Write-off)',
                                    ])
                                    ->default('return_hub')
                                    ->required()
                                    ->reactive(),
                                
                                Forms\Components\Select::make('transfer_project_' . $stock['resource_id'])
                                    ->label('Transfer to Project')
                                    ->options(Project::where('status', 'Active')->where('id', '!=', $this->record->id)->pluck('name', 'id'))
                                    ->visible(fn ($get) => $get('action_' . $stock['resource_id']) === 'transfer')
                                    ->required(fn ($get) => $get('action_' . $stock['resource_id']) === 'transfer'),
                                
                                Forms\Components\Textarea::make('notes_' . $stock['resource_id'])
                                    ->label('Notes')
                                    ->rows(2),
                            ])
                            ->columns(2)
                            ->collapsible();
                    }

                    return $formFields;
                })
                ->action(function (array $data) {
                    $service = app(InventoryTransactionService::class);
                    $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                    
                    try {
                        DB::beginTransaction();

                        foreach ($stocks as $stock) {
                            $resourceId = $stock['resource_id'];
                            $action = $data['action_' . $resourceId] ?? 'return_hub';
                            $notes = $data['notes_' . $resourceId] ?? 'Project completion';

                            if ($action === 'return_hub') {
                                // Transfer back to hub (reverse allocation)
                                $service->recordTransfer(
                                    ResourceModel::find($resourceId),
                                    $this->record,
                                    null, // null means hub
                                    $stock['quantity'],
                                    today()->format('Y-m-d'),
                                    $notes . ' - Returned to hub on project completion'
                                );
                            } elseif ($action === 'transfer') {
                                $toProjectId = $data['transfer_project_' . $resourceId];
                                $service->recordTransfer(
                                    ResourceModel::find($resourceId),
                                    $this->record,
                                    Project::find($toProjectId),
                                    $stock['quantity'],
                                    today()->format('Y-m-d'),
                                    $notes . ' - Transferred on project completion'
                                );
                            }
                            // If 'keep', we don't create any transaction (write-off)
                        }

                        // Update project status
                        $this->record->update([
                            'status' => 'Completed',
                            'end_date' => today(),
                        ]);

                        DB::commit();

                        Notification::make()
                            ->success()
                            ->title('Project Completed')
                            ->body('All resources have been processed and project marked as completed.')
                            ->send();

                        redirect(ProjectResource::getUrl('index'));
                    } catch (\Exception $e) {
                        DB::rollBack();
                        
                        Notification::make()
                            ->danger()
                            ->title('Completion Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            
            Actions\EditAction::make(),
        ];
    }
}
