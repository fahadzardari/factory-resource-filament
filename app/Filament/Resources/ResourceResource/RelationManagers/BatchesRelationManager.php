<?php

namespace App\Filament\Resources\ResourceResource\RelationManagers;

use App\Models\ResourceBatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class BatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'batches';
    
    protected static ?string $title = 'Purchase Batches';
    
    protected static ?string $recordTitleAttribute = 'batch_number';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Batch Overview')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('batch_number')
                                    ->label('Batch Number')
                                    ->weight(FontWeight::Bold)
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('supplier')
                                    ->label('Supplier')
                                    ->icon('heroicon-o-building-storefront'),
                                Infolists\Components\TextEntry::make('purchase_date')
                                    ->label('Purchase Date')
                                    ->date()
                                    ->icon('heroicon-o-calendar'),
                            ]),
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('unit_type')
                                    ->label('Unit')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => ResourceBatch::UNIT_TYPES[$state] ?? $state),
                                Infolists\Components\TextEntry::make('purchase_price')
                                    ->label('Unit Price')
                                    ->money('USD')
                                    ->icon('heroicon-o-currency-dollar'),
                                Infolists\Components\TextEntry::make('conversion_factor')
                                    ->label('Conversion Factor')
                                    ->visible(fn ($record) => $record->conversion_factor != 1.0),
                            ]),
                        ])->from('md'),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Quantities')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('quantity_purchased')
                                    ->label('Originally Purchased')
                                    ->numeric(2)
                                    ->suffix(fn ($record) => ' ' . $record->unit_label)
                                    ->icon('heroicon-o-shopping-cart')
                                    ->color('info')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold),
                            ]),
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('quantity_remaining')
                                    ->label('Current Stock')
                                    ->numeric(2)
                                    ->suffix(fn ($record) => ' ' . $record->unit_label)
                                    ->icon('heroicon-o-cube')
                                    ->color(fn ($record) => $record->quantity_remaining > 0 ? 'success' : 'danger')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold),
                            ]),
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('quantity_used')
                                    ->label('Already Used')
                                    ->state(fn ($record) => $record->quantity_used)
                                    ->numeric(2)
                                    ->suffix(fn ($record) => ' ' . $record->unit_label)
                                    ->icon('heroicon-o-minus-circle')
                                    ->color('warning')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold),
                            ]),
                        ])->from('md'),
                    ])
                    ->columns(3),
                    
                Infolists\Components\Section::make('Financial')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_value')
                            ->label('Current Inventory Value')
                            ->state(fn ($record) => $record->total_value)
                            ->money('USD')
                            ->hint(fn ($record) => "Based on {$record->quantity_remaining} {$record->unit_label} × \${$record->purchase_price}")
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('success'),
                        Infolists\Components\TextEntry::make('usage_percentage')
                            ->label('Usage Rate')
                            ->state(fn ($record) => number_format($record->usage_percentage, 1) . '%')
                            ->hint('Percentage of batch consumed'),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->markdown()
                            ->placeholder('No notes recorded'),
                    ])
                    ->visible(fn ($record) => !empty($record->notes))
                    ->collapsible(),
                    
                Infolists\Components\Section::make('Change History')
                    ->schema([
                        Infolists\Components\ViewEntry::make('activity')
                            ->view('filament.infolists.activity-log')
                            ->state(fn ($record) => $record->activities()->latest()->limit(10)->get()),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📦 New Purchase Details')
                    ->description('Record a new inventory purchase. This adds stock to the warehouse.')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Forms\Components\TextInput::make('batch_number')
                            ->label('Batch Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'BATCH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                            ->helperText('📋 Auto-generated unique identifier. You can customize it if needed.'),
                            
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('Purchase Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->helperText('📅 When was this batch purchased?'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('📊 Quantity & Unit')
                    ->description('⚠️ IMPORTANT: You can purchase in ANY unit. Example: Resource is tracked in "kg" but you can buy in "tons"')
                    ->icon('heroicon-o-scale')
                    ->schema([
                        Forms\Components\Select::make('unit_type')
                            ->label('Unit of Measurement')
                            ->options(ResourceBatch::UNIT_TYPES)
                            ->required()
                            ->searchable()
                            ->default(fn () => $this->getOwnerRecord()->unit_type)
                            ->helperText('⚖️ What unit is THIS batch measured in? Can be different from the resource default.')
                            ->native(false)
                            ->live(),
                            
                        Forms\Components\TextInput::make('quantity_purchased')
                            ->label('Quantity Purchased')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->suffix(function (Forms\Get $get) {
                                $unitType = $get('unit_type') ?? 'units';
                                return ResourceBatch::UNIT_TYPES[$unitType] ?? $unitType;
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $set('quantity_remaining', $state);
                                $price = $get('purchase_price');
                                if ($price && $state) {
                                    $set('_calculated_total', number_format($state * $price, 2));
                                }
                            })
                            ->helperText('🔢 How many units did you purchase?'),
                            
                        Forms\Components\TextInput::make('conversion_factor')
                            ->label('Conversion Factor (Advanced)')
                            ->numeric()
                            ->default(1.0)
                            ->minValue(0.000001)
                            ->step(0.000001)
                            ->helperText('🔄 For unit conversion. Example: If buying in tons but resource tracks kg, use 1000. Leave as 1.0 for same units.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('💰 Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Unit Purchase Price')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('$')
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $quantity = $get('quantity_purchased');
                                if ($quantity && $state) {
                                    $set('_calculated_total', number_format($quantity * $state, 2));
                                }
                            })
                            ->helperText('💵 Price per unit (not total price)'),
                            
                        Forms\Components\Placeholder::make('total_cost_display')
                            ->label('💎 Total Batch Cost')
                            ->content(function (Forms\Get $get) {
                                $quantity = $get('quantity_purchased') ?? 0;
                                $price = $get('purchase_price') ?? 0;
                                $unitType = $get('unit_type') ?? 'units';
                                $unitLabel = ResourceBatch::UNIT_TYPES[$unitType] ?? $unitType;
                                return '$' . number_format($quantity * $price, 2) . " ({$quantity} {$unitLabel} × \${$price})";
                            }),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('🏢 Supplier Information')
                    ->schema([
                        Forms\Components\TextInput::make('supplier')
                            ->label('Supplier')
                            ->maxLength(255)
                            ->placeholder('e.g., ABC Steel Company')
                            ->helperText('🏭 Who did you buy this from?'),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Quality grade, delivery notes, special conditions, etc.')
                            ->helperText('📝 Any additional information about this purchase'),
                    ])
                    ->columns(1)
                    ->collapsible(),
                    
                Forms\Components\Section::make('⚠️ Editing Existing Batch')
                    ->description('You can adjust the remaining quantity if you need to correct stock levels.')
                    ->schema([
                        Forms\Components\TextInput::make('quantity_remaining')
                            ->label('Current Stock Remaining')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(function (Forms\Get $get) {
                                $unitType = $get('unit_type') ?? 'units';
                                return ResourceBatch::UNIT_TYPES[$unitType] ?? $unitType;
                            })
                            ->helperText(function (Forms\Get $get) {
                                $purchased = $get('quantity_purchased') ?? 0;
                                $remaining = $get('quantity_remaining') ?? 0;
                                $used = $purchased - $remaining;
                                return "⚠️ Cannot exceed {$purchased} (purchased). Currently {$used} units have been used.";
                            })
                            ->live(),
                    ])
                    ->visible(fn ($operation) => $operation === 'edit')
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('batch_number')
            ->heading('📦 Inventory Purchase Batches')
            ->description('Each batch is a separate purchase. Stock is consumed from oldest batches first (FIFO). To add MORE stock, click "Record New Purchase" below.')
            ->columns([
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Batch #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Batch number copied!')
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->purchase_date->format('M d, Y')),
                    
                Tables\Columns\TextColumn::make('unit_type')
                    ->label('Unit')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => $state),
                    
                Tables\Columns\TextColumn::make('quantity_purchased')
                    ->label('📥 Purchased')
                    ->numeric(2)
                    ->sortable()
                    ->color('info')
                    ->description('Original qty'),
                    
                Tables\Columns\TextColumn::make('quantity_remaining')
                    ->label('📊 In Stock')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($record) => $record->quantity_remaining > 0 ? 'success' : 'danger')
                    ->weight(FontWeight::Bold)
                    ->icon(fn ($record) => $record->is_depleted ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->description(fn ($record) => $record->is_depleted ? 'Depleted' : 'Available'),
                    
                Tables\Columns\TextColumn::make('quantity_used')
                    ->label('📤 Used')
                    ->state(fn ($record) => $record->quantity_used)
                    ->numeric(2)
                    ->color('warning')
                    ->description('Already consumed'),
                    
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('💰 Price/Unit')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_value')
                    ->label('💎 Current Value')
                    ->state(fn ($record) => $record->total_value)
                    ->money('USD')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('success')
                    ->description('Stock × Price'),
                    
                Tables\Columns\TextColumn::make('supplier')
                    ->label('Supplier')
                    ->searchable()
                    ->toggleable()
                    ->wrap()
                    ->limit(20),
            ])
            ->defaultSort('purchase_date', 'asc') // FIFO order - oldest first!
            ->filters([
                Tables\Filters\Filter::make('has_stock')
                    ->label('✅ Has Stock')
                    ->query(fn (Builder $query) => $query->where('quantity_remaining', '>', 0))
                    ->default(),
                Tables\Filters\Filter::make('depleted')
                    ->label('❌ Depleted')
                    ->query(fn (Builder $query) => $query->where('quantity_remaining', '<=', 0)),
            ])
            ->headerActions([
                Tables\Actions\Action::make('how_it_works')
                    ->label('ℹ️ How It Works')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('info')
                    ->modalHeading('📚 Understanding Purchase Batches')
                    ->modalDescription(null)
                    ->modalContent(view('filament.modals.batch-help'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Got it!'),
                Tables\Actions\CreateAction::make()
                    ->label('🛒 Record New Purchase')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->size('lg')
                    ->modalHeading('📦 Record New Stock Purchase')
                    ->modalDescription('Add a new purchase batch to increase inventory. This does NOT affect existing batches.')
                    ->modalWidth('6xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Ensure quantity_remaining equals quantity_purchased for new batches
                        $data['quantity_remaining'] = $data['quantity_purchased'];
                        return $data;
                    })
                    ->successNotificationTitle('✅ Purchase Recorded Successfully')
                    ->after(function ($record) {
                        $resource = $record->resource;
                        Notification::make()
                            ->title('Inventory Updated')
                            ->body("Added {$record->quantity_purchased} {$record->unit_label} of {$resource->name}. New total: {$resource->fresh()->available_quantity} {$resource->unit_type}")
                            ->success()
                            ->duration(8000)
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => "📦 Batch: {$record->batch_number}")
                    ->modalWidth('5xl'),
                Tables\Actions\EditAction::make()
                    ->modalHeading(fn ($record) => "✏️ Edit: {$record->batch_number}")
                    ->modalDescription('⚠️ Be careful when editing. This changes inventory levels.')
                    ->modalWidth('6xl')
                    ->successNotificationTitle('Batch Updated'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Purchase Batch')
                    ->modalDescription(fn ($record) => 
                        $record->quantity_used > 0 
                            ? "❌ Cannot delete: {$record->quantity_used} units have already been consumed from this batch." 
                            : "Are you sure you want to delete this batch? This will remove {$record->quantity_remaining} units from inventory."
                    )
                    ->disabled(fn ($record) => $record->quantity_used > 0)
                    ->tooltip(fn ($record) => 
                        $record->quantity_used > 0 
                            ? 'Cannot delete: Partially consumed' 
                            : 'Delete this batch'
                    )
                    ->successNotificationTitle('Batch Deleted')
                    ->after(function () {
                        Notification::make()
                            ->title('Inventory Updated')
                            ->body('Batch removed and inventory quantities synced')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // Disabled bulk actions for safety
            ])
            ->emptyStateHeading('📭 No Purchase Batches Yet')
            ->emptyStateDescription('Start by recording your first purchase. Each time you buy stock, add it as a new batch with its own price and quantity.')
            ->emptyStateIcon('heroicon-o-archive-box-x-mark')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('🛒 Record First Purchase')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success'),
            ]);
    }
}
