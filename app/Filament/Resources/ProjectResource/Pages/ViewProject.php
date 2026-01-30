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
            // Primary action group for resource operations
            Actions\ActionGroup::make([
                Actions\Action::make('allocate')
                    ->label('Allocate Resource')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn () => strtolower($this->record->status) === 'active')
                ->modalHeading('Allocate Resource from Hub to Project')
                ->modalDescription('Move resources from the Central Hub warehouse to this project site. This will reduce Hub inventory and increase project inventory.')
                ->modalIcon('heroicon-o-truck')
                ->form([
                    Forms\Components\Placeholder::make('info')
                        ->content('📦 Select a resource from the Central Hub and specify how much you want to send to this project. Make sure you have enough stock at the Hub before allocating.')
                        ->columnSpanFull(),
                    
                    Forms\Components\Select::make('resource_id')
                        ->label('Resource')
                        ->helperText('Choose which resource you want to allocate to this project')
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
                        ->label('Available Stock at Hub')
                        ->content(function ($get) {
                            if ($resourceId = $get('resource_id')) {
                                $resource = \App\Models\Resource::find($resourceId);
                                $stock = \App\Services\StockCalculator::getHubStock($resourceId);
                                return "✅ Available: {$stock} {$resource->base_unit}";
                            }
                            return '⏳ Select a resource first';
                        })
                        ->visible(fn ($get) => $get('resource_id')),
                    
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->label('Quantity to Allocate')
                        ->helperText('Enter the amount you want to send to this project. Cannot exceed available Hub stock.'),
                    
                    Forms\Components\DatePicker::make('transaction_date')
                        ->label('Transaction Date')
                        ->helperText('When did this allocation happen? Cannot be a future date.')
                        ->required()
                        ->default(today())
                        ->maxDate(today()),
                    
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes (Optional)')
                        ->helperText('Add any additional information about this allocation')
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
                ->visible(fn () => strtolower($this->record->status) === 'active')
                ->modalHeading('Record Resource Consumption')
                ->modalDescription('Track materials used in this project. This will reduce the project inventory and cannot be undone.')
                ->modalIcon('heroicon-o-fire')
                ->form([
                    Forms\Components\Placeholder::make('info')
                        ->content('🔥 Record materials consumed/used in this project. For example: cement used in construction, paint used for walls, etc. This permanently removes the resource from inventory.')
                        ->columnSpanFull(),
                    
                    Forms\Components\Select::make('resource_id')
                        ->label('Resource to Consume')
                        ->helperText('Select which material was used in the project')
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
                        ->label('Quantity Consumed')
                        ->helperText(function ($get) {
                            if ($resourceId = $get('resource_id')) {
                                $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                                $stock = collect($stocks)->firstWhere('resource_id', $resourceId);
                                return $stock ? "⚠️ Available at project: {$stock['quantity']} {$stock['unit']} - Don't exceed this amount!" : '';
                            }
                            return 'Enter how much was used';
                        }),
                    
                    Forms\Components\DatePicker::make('transaction_date')
                        ->label('Consumption Date')
                        ->helperText('When was this resource used? Cannot be a future date.')
                        ->required()
                        ->default(today())
                        ->maxDate(today()),
                    
                    Forms\Components\Textarea::make('notes')
                        ->label('Usage Notes (Optional)')
                        ->helperText('Describe what this resource was used for. E.g., "Used in foundation work" or "Applied to exterior walls"')
                        ->placeholder('Example: Used 50kg cement for column casting on ground floor')
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
                ->visible(fn () => strtolower($this->record->status) === 'active')
                ->modalHeading('Transfer Resource to Another Location')
                ->modalDescription('Move resources from this project to another project or back to the Central Hub.')
                ->modalIcon('heroicon-o-truck')
                ->form([
                    Forms\Components\Placeholder::make('info')
                        ->content('🚚 Transfer resources to another location. Choose "Hub" to return materials to the central warehouse, or select another project to send materials there directly.')
                        ->columnSpanFull(),
                    
                    Forms\Components\Select::make('resource_id')
                        ->label('Resource to Transfer')
                        ->helperText('Which material do you want to move?')
                        ->options(function () {
                            $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                            return collect($stocks)->pluck('resource_name', 'resource_id');
                        })
                        ->required()
                        ->searchable()
                        ->reactive(),
                    
                    Forms\Components\Select::make('destination')
                        ->label('Transfer Destination')
                        ->helperText('Where do you want to send this resource?')
                        ->options(function () {
                            $options = ['hub' => '🏢 Central Hub (Main Warehouse)'];
                            $projects = Project::where('status', 'active')
                                ->where('id', '!=', $this->record->id)
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->id => "🏗️ {$p->name} ({$p->code})"]);
                            return $options + $projects->toArray();
                        })
                        ->required()
                        ->searchable()
                        ->reactive(),
                    
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->label('Quantity to Transfer')
                        ->helperText(function ($get) {
                            if ($resourceId = $get('resource_id')) {
                                $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                                $stock = collect($stocks)->firstWhere('resource_id', $resourceId);
                                return $stock ? "⚠️ Available at this project: {$stock['quantity']} {$stock['unit']}" : '';
                            }
                            return 'Enter the amount to transfer';
                        }),
                    
                    Forms\Components\DatePicker::make('transaction_date')
                        ->label('Transfer Date')
                        ->helperText('When did/will this transfer happen?')
                        ->required()
                        ->default(today())
                        ->maxDate(today()),
                    
                    Forms\Components\Textarea::make('notes')
                        ->label('Transfer Notes (Optional)')
                        ->helperText('Add reason for transfer or any other details')
                        ->placeholder('Example: Excess materials no longer needed, or Materials needed urgently at other site')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $service = app(InventoryTransactionService::class);
                    $resource = ResourceModel::find($data['resource_id']);
                    
                    // Determine destination
                    $toProject = null;
                    $destinationName = 'Central Hub';
                    
                    if ($data['destination'] !== 'hub') {
                        $toProject = Project::find($data['destination']);
                        $destinationName = $toProject->name;
                    }
                    
                    try {
                        $service->recordTransfer(
                            $resource,
                            $this->record,
                            $toProject,
                            $data['quantity'],
                            \Carbon\Carbon::parse($data['transaction_date'])->format('Y-m-d'),
                            $data['notes'] ?? null
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('Transfer Successful! ✅')
                            ->body("Transferred {$data['quantity']} {$resource->base_unit} of {$resource->name} to {$destinationName}.")
                            ->send();
                        
                        $this->refreshFormData(['infolist']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Transfer Failed ❌')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            ])
            ->label('Resource Operations')
            ->icon('heroicon-o-cube')
            ->color('primary')
            ->button()
            ->visible(fn () => strtolower($this->record->status) === 'active'),
            
            // Export actions group
            Actions\ActionGroup::make([
                Actions\Action::make('exportDailyConsumption')
                ->label('Export Daily Consumption')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->modalHeading('📊 Export Daily Consumption Report')
                ->modalDescription('Download a detailed report showing all resources at this project for a specific day, including opening balance, transactions, and closing balance.')
                ->form([
                    Forms\Components\Placeholder::make('info')
                        ->content('📅 This report shows a snapshot of all materials at the project for one specific day. It includes opening stock, any materials added or used that day, and the closing stock.')
                        ->columnSpanFull(),
                    
                    Forms\Components\DatePicker::make('date')
                        ->label('Select Date')
                        ->helperText('Choose which day you want to see the report for')
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
                ->modalHeading('📈 Export Resource Usage Report')
                ->modalDescription('Download a comprehensive report of all resource transactions within a date range.')
                ->form([
                    Forms\Components\Placeholder::make('info')
                        ->content('📋 This report shows ALL transactions (allocations, consumption, transfers) for this project within your selected date range. Perfect for auditing and analysis.')
                        ->columnSpanFull(),
                    
                    Forms\Components\DatePicker::make('date_from')
                        ->label('From Date')
                        ->helperText('Start of the period you want to analyze')
                        ->default(now()->subDays(30))
                        ->maxDate(today()),
                    
                    Forms\Components\DatePicker::make('date_to')
                        ->label('To Date')
                        ->helperText('End of the period you want to analyze')
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
            ])
            ->label('Export Reports')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('info')
            ->button(),

            Actions\Action::make('completeProject')
                ->label('Complete Project')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => strtolower($this->record->status) === 'active')
                ->requiresConfirmation()
                ->modalHeading('🎉 Complete This Project')
                ->modalDescription('You are about to mark this project as completed. Please decide what to do with all remaining resources at this project site.')
                ->modalIcon('heroicon-o-check-circle')
                ->modalSubmitActionLabel('Complete Project & Process Resources')
                ->modalWidth('4xl')
                ->form(function () {
                    $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                    
                    if (empty($stocks)) {
                        return [
                            Forms\Components\Placeholder::make('no_resources')
                                ->content('✅ Great! No resources remaining at this project site. The project can be completed without any material transfers.')
                                ->columnSpanFull(),
                        ];
                    }

                    $formFields = [
                        Forms\Components\Placeholder::make('instructions')
                            ->content('📦 You have resources remaining at this project. For EACH resource below, choose what to do with it:

• **Return to Hub**: Send back to central warehouse for future use
• **Transfer to Project**: Send directly to another active project  
• **Keep at Project**: Leave it here (write-off, will not be tracked anymore)')
                            ->columnSpanFull(),
                    ];
                    
                    foreach ($stocks as $stock) {
                        $formFields[] = Forms\Components\Section::make($stock['resource_name'] . ' (' . $stock['sku'] . ')')
                            ->description('📊 Current Stock: ' . number_format($stock['quantity'], 2) . ' ' . $stock['unit'] . ' available at this project')
                            ->schema([
                                Forms\Components\Select::make('action_' . $stock['resource_id'])
                                    ->label('What to do with this resource?')
                                    ->helperText('Choose how to handle these materials')
                                    ->options([
                                        'return_hub' => '🏢 Return to Central Hub',
                                        'transfer' => '🚚 Transfer to Another Project',
                                        'keep' => '❌ Keep at Project (Write-off)',
                                    ])
                                    ->default('return_hub')
                                    ->required()
                                    ->reactive(),
                                
                                Forms\Components\Select::make('transfer_project_' . $stock['resource_id'])
                                    ->label('Transfer to Which Project?')
                                    ->helperText('Select the project that will receive these materials')
                                    ->options(function () {
                                        return Project::where('status', 'active')
                                            ->where('id', '!=', $this->record->id)
                                            ->get()
                                            ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} ({$p->code})"]);
                                    })
                                    ->visible(fn ($get) => $get('action_' . $stock['resource_id']) === 'transfer')
                                    ->required(fn ($get) => $get('action_' . $stock['resource_id']) === 'transfer')
                                    ->searchable(),
                                
                                Forms\Components\Textarea::make('notes_' . $stock['resource_id'])
                                    ->label('Notes (Optional)')
                                    ->helperText('Add any remarks about this resource disposition')
                                    ->placeholder('Example: Excess materials from project, Good condition for reuse')
                                    ->rows(2),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->collapsed(false);
                    }

                    return $formFields;
                })
                ->action(function (array $data) {
                    $service = app(InventoryTransactionService::class);
                    $stocks = \App\Services\StockCalculator::getProjectResourceStocks($this->record->id);
                    
                    try {
                        DB::beginTransaction();

                        $processedCount = 0;
                        $returnedToHub = 0;
                        $transferred = 0;
                        $keptAtProject = 0;

                        foreach ($stocks as $stock) {
                            $resourceId = $stock['resource_id'];
                            $action = $data['action_' . $resourceId] ?? 'return_hub';
                            $notes = $data['notes_' . $resourceId] ?? 'Project completion';

                            if ($action === 'return_hub') {
                                // Transfer back to hub
                                $service->recordTransfer(
                                    ResourceModel::find($resourceId),
                                    $this->record,
                                    null, // null = Hub
                                    $stock['quantity'],
                                    today()->format('Y-m-d'),
                                    $notes . ' - Returned to Hub on project completion'
                                );
                                $returnedToHub++;
                            } elseif ($action === 'transfer') {
                                $toProjectId = $data['transfer_project_' . $resourceId];
                                $service->recordTransfer(
                                    ResourceModel::find($resourceId),
                                    $this->record,
                                    Project::find($toProjectId),
                                    $stock['quantity'],
                                    today()->format('Y-m-d'),
                                    $notes . ' - Transferred to another project on completion'
                                );
                                $transferred++;
                            } else {
                                // Keep at project (write-off)
                                $keptAtProject++;
                            }
                            $processedCount++;
                        }

                        // Update project status
                        $this->record->update([
                            'status' => 'Completed',
                            'end_date' => today(),
                        ]);

                        DB::commit();

                        $summary = "Processed {$processedCount} resource(s): ";
                        $details = [];
                        if ($returnedToHub > 0) $details[] = "{$returnedToHub} returned to Hub";
                        if ($transferred > 0) $details[] = "{$transferred} transferred to other projects";
                        if ($keptAtProject > 0) $details[] = "{$keptAtProject} kept at project";
                        
                        Notification::make()
                            ->success()
                            ->title('🎉 Project Completed Successfully!')
                            ->body($summary . implode(', ', $details) . '. Project status updated to Completed.')
                            ->duration(8000)
                            ->send();

                        redirect(ProjectResource::getUrl('index'));
                    } catch (\Exception $e) {
                        DB::rollBack();
                        
                        Notification::make()
                            ->danger()
                            ->title('❌ Project Completion Failed')
                            ->body('Error: ' . $e->getMessage() . '. No changes were made.')
                            ->send();
                    }
                }),
            
            Actions\EditAction::make(),
        ];
    }
}
