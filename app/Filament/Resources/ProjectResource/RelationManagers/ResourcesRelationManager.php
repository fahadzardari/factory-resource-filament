<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Project;
use App\Models\ResourceTransfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'batches';
    
    protected static ?string $title = 'Project Inventory';
    
    protected static ?string $recordTitleAttribute = 'batch_number';

    public function form(Form $form): Form
    {
        // This relation manager now shows batches, not the pivot
        // Form not needed as batches are read-only in project view
        return $form
            ->schema([
                Forms\Components\Placeholder::make('info')
                    ->content('Project inventory is managed via transfers from Central Hub. Use the "Transfer from Hub" action to add inventory.')
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('batch_number')
            ->heading('📦 Project Inventory (Batches)')
            ->description('🏭 These are the batches currently at this project site. Batches are transferred from Central Hub.')
            ->columns([
                Tables\Columns\TextColumn::make('resource.name')
                    ->label('Resource')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->resource->sku),
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Batch #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_remaining')
                    ->label('Quantity')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn ($record) => ' ' . ($record->unit_type))
                    ->sortable()
                    ->description(fn ($record) => 
                        number_format($record->quantity_remaining * $record->conversion_factor, 2) . 
                        ' ' . $record->resource->unit_type . ' (base unit)'
                    ),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Unit Cost')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Purchase Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('transfer_from_hub')
                    ->label('🚚 Transfer from Central Hub')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Transfer Inventory from Central Hub')
                    ->modalDescription('⚠️ IMPORTANT: This PHYSICALLY MOVES inventory from Central Hub to this project. The inventory will be REMOVED from Central Hub.')
                    ->modalWidth('2xl')
                    ->form([
                        Forms\Components\Section::make('Transfer Information')
                            ->description('📋 Select the resource and quantity to transfer from Central Hub to this project')
                            ->schema([
                                Forms\Components\Select::make('resource_id')
                                    ->label('Resource')
                                    ->options(\App\Models\Resource::where('available_quantity', '>', 0)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $resource = \App\Models\Resource::find($state);
                                            $set('available_in_hub', $resource->central_hub_quantity);
                                            $set('unit_type', $resource->unit_type);
                                        }
                                    })
                                    ->helperText('💡 Only showing resources with available inventory in Central Hub'),
                                    
                                Forms\Components\Placeholder::make('available_display')
                                    ->label('📊 Available in Central Hub')
                                    ->content(function (Forms\Get $get) {
                                        $resourceId = $get('resource_id');
                                        if ($resourceId) {
                                            $resource = \App\Models\Resource::find($resourceId);
                                            return number_format($resource->central_hub_quantity, 2) . ' ' . $resource->unit_type;
                                        }
                                        return 'Select a resource first';
                                    }),
                                    
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity to Transfer')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->suffix(fn (Forms\Get $get) => $get('unit_type') ?? 'units')
                                    ->helperText('⚠️ This amount will be REMOVED from Central Hub and ADDED to this project'),
                                    
                                Forms\Components\Textarea::make('notes')
                                    ->label('Transfer Notes')
                                    ->placeholder('Optional: Reason for transfer, expected usage, etc.'),
                            ]),
                    ])
                    ->action(function (array $data) {
                        $project = $this->getOwnerRecord();
                        $resource = \App\Models\Resource::find($data['resource_id']);
                        
                        try {
                            // Use the new transfer system
                            $resource->transferToProject($project, $data['quantity'], $data['notes'] ?? null);
                            
                            Notification::make()
                                ->success()
                                ->title('✅ Transfer Complete')
                                ->body("Successfully transferred {$data['quantity']} {$resource->unit_type} of {$resource->name} from Central Hub to {$project->name}")
                                ->send();
                                
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()
                                ->danger()
                                ->title('❌ Transfer Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('help')
                    ->label('ℹ️ How Transfers Work')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('info')
                    ->modalHeading('📚 Understanding Project Inventory Transfers')
                    ->modalDescription('This system uses PHYSICAL TRANSFERS, not copies. When you transfer inventory from Central Hub to a project, it is REMOVED from Central Hub and ADDED to the project.')
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Got it!'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => "📦 Batch: {$record->batch_number}"),
                    
                Tables\Actions\Action::make('return_to_hub')
                    ->label('Return to Hub')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->visible(fn ($record) => $record->quantity_remaining > 0)
                    ->modalHeading('Return Inventory to Central Hub')
                    ->modalDescription('Move unused inventory back to Central Hub for use in other projects')
                    ->form([
                        Forms\Components\TextInput::make('quantity_to_return')
                            ->label('Quantity to Return')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(fn ($record) => $record->quantity_remaining * $record->conversion_factor)
                            ->step(0.01)
                            ->suffix(fn ($record) => $record->resource->unit_type)
                            ->helperText(fn ($record) => 'Max: ' . number_format($record->quantity_remaining * $record->conversion_factor, 2) . ' ' . $record->resource->unit_type),
                        Forms\Components\Textarea::make('return_notes')
                            ->label('Return Notes')
                            ->placeholder('Optional: Reason for return'),
                    ])
                    ->action(function ($record, array $data) {
                        $project = $this->getOwnerRecord();
                        $resource = $record->resource;
                        
                        try {
                            $resource->returnToHub($project, $data['quantity_to_return'], $data['return_notes'] ?? null);
                            
                            Notification::make()
                                ->success()
                                ->title('✅ Return Complete')
                                ->body("Successfully returned {$data['quantity_to_return']} {$resource->unit_type} to Central Hub")
                                ->send();
                                
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()
                                ->danger()
                                ->title('❌ Return Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                // Disabled for safety
            ])
            ->emptyStateHeading('📭 No Inventory Yet')
            ->emptyStateDescription('This project has no inventory yet. Transfer resources from Central Hub to get started.')
            ->emptyStateIcon('heroicon-o-archive-box-x-mark')
            ->emptyStateActions([
                Tables\Actions\Action::make('transfer_first')
                    ->label('🚚 Transfer from Central Hub')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Transfer Inventory from Central Hub')
                    ->modalDescription('⚠️ IMPORTANT: This PHYSICALLY MOVES inventory from Central Hub to this project.')
                    ->modalWidth('2xl')
                    ->form([
                        Forms\Components\Section::make('Transfer Information')
                            ->description('📋 Select the resource and quantity to transfer')
                            ->schema([
                                Forms\Components\Select::make('resource_id')
                                    ->label('Resource')
                                    ->options(\App\Models\Resource::where('available_quantity', '>', 0)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $resource = \App\Models\Resource::find($state);
                                            $set('unit_type', $resource->unit_type);
                                        }
                                    }),
                                    
                                Forms\Components\Placeholder::make('available_display')
                                    ->label('📊 Available in Central Hub')
                                    ->content(function (Forms\Get $get) {
                                        $resourceId = $get('resource_id');
                                        if ($resourceId) {
                                            $resource = \App\Models\Resource::find($resourceId);
                                            return number_format($resource->central_hub_quantity, 2) . ' ' . $resource->unit_type;
                                        }
                                        return 'Select a resource first';
                                    }),
                                    
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity to Transfer')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->suffix(fn (Forms\Get $get) => $get('unit_type') ?? 'units'),
                                    
                                Forms\Components\Textarea::make('notes')
                                    ->label('Transfer Notes')
                                    ->placeholder('Optional'),
                            ]),
                    ])
                    ->action(function (array $data) {
                        $project = $this->getOwnerRecord();
                        $resource = \App\Models\Resource::find($data['resource_id']);
                        
                        try {
                            $resource->transferToProject($project, $data['quantity'], $data['notes'] ?? null);
                            
                            Notification::make()
                                ->success()
                                ->title('✅ Transfer Complete')
                                ->body("Successfully transferred {$data['quantity']} {$resource->unit_type}")
                                ->send();
                                
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()
                                ->danger()
                                ->title('❌ Transfer Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ]);
    }
}
