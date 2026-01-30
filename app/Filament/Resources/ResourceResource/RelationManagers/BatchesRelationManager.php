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

class BatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'batches';
    
    protected static ?string $title = 'Purchase Batches';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Batch Information')
                    ->description('💡 Each batch represents a separate purchase. You can use different units for each batch.')
                    ->schema([
                        Forms\Components\TextInput::make('batch_number')
                            ->label('Batch Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'BATCH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                            ->helperText('Auto-generated unique identifier. Can be customized.'),
                            
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('Purchase Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->helperText('Date when this batch was purchased'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Quantity & Unit')
                    ->description('⚠️ Different batches can use different units (e.g., one in kg, another in tons)')
                    ->schema([
                        Forms\Components\Select::make('unit_type')
                            ->label('Unit Type')
                            ->options(ResourceBatch::UNIT_TYPES)
                            ->required()
                            ->searchable()
                            ->default(fn () => $this->getOwnerRecord()->unit_type)
                            ->helperText('Unit of measurement for this batch')
                            ->native(false),
                            
                        Forms\Components\TextInput::make('conversion_factor')
                            ->label('Conversion Factor')
                            ->numeric()
                            ->default(1.0)
                            ->minValue(0.000001)
                            ->step(0.000001)
                            ->helperText('Multiplier to convert to base unit (1.0 if same as resource default)'),
                            
                        Forms\Components\TextInput::make('quantity_purchased')
                            ->label('Quantity Purchased')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $set('quantity_remaining', $state);
                                $price = $get('purchase_price');
                                if ($price && $state) {
                                    $set('_calculated_total', number_format($state * $price, 2));
                                }
                            })
                            ->helperText('Total quantity in this purchase'),
                            
                        Forms\Components\TextInput::make('quantity_remaining')
                            ->label('Quantity Remaining')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->helperText('⚠️ Cannot be negative or exceed purchased quantity')
                            ->visible(fn ($operation) => $operation === 'edit'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Pricing')
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
                            ->helperText('Price per unit for this batch'),
                            
                        Forms\Components\Placeholder::make('total_cost_display')
                            ->label('Total Batch Cost')
                            ->content(function (Forms\Get $get) {
                                $quantity = $get('quantity_purchased') ?? 0;
                                $price = $get('purchase_price') ?? 0;
                                return '$' . number_format($quantity * $price, 2);
                            }),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Supplier Information')
                    ->schema([
                        Forms\Components\TextInput::make('supplier')
                            ->label('Supplier')
                            ->maxLength(255)
                            ->placeholder('Supplier company name')
                            ->helperText('Track where this batch was purchased from'),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Quality grade, special conditions, delivery notes...')
                            ->helperText('Any additional information about this purchase'),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('batch_number')
            ->description('📦 Purchase batches track individual stock purchases. Oldest batches are consumed first (FIFO).')
            ->columns([
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Batch #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Batch number copied!')
                    ->weight(FontWeight::Bold),
                    
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->description(fn ($record) => $record->purchase_date->diffForHumans()),
                    
                Tables\Columns\TextColumn::make('unit_type')
                    ->label('Unit')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => ResourceBatch::UNIT_TYPES[$state] ?? $state),
                    
                Tables\Columns\TextColumn::make('quantity_purchased')
                    ->label('Purchased')
                    ->numeric(2)
                    ->sortable()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('quantity_remaining')
                    ->label('Remaining')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($record) => $record->quantity_remaining > 0 ? 'success' : 'danger')
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->is_depleted ? '⚠️ Depleted' : null),
                    
                Tables\Columns\TextColumn::make('quantity_used')
                    ->label('Used')
                    ->state(fn ($record) => $record->quantity_used)
                    ->numeric(2)
                    ->color('warning'),
                    
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Unit Price')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Current Value')
                    ->state(fn ($record) => $record->total_value)
                    ->money('USD')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('success')
                    ->description('Remaining × Price'),
                    
                Tables\Columns\TextColumn::make('supplier')
                    ->label('Supplier')
                    ->searchable()
                    ->toggleable()
                    ->wrap(),
            ])
            ->defaultSort('purchase_date', 'asc') // FIFO order
            ->filters([
                Tables\Filters\Filter::make('has_stock')
                    ->label('Has Stock')
                    ->query(fn (Builder $query) => $query->where('quantity_remaining', '>', 0))
                    ->default(),
                Tables\Filters\Filter::make('depleted')
                    ->label('Depleted')
                    ->query(fn (Builder $query) => $query->where('quantity_remaining', '<=', 0)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Purchase Batch')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Record New Purchase')
                    ->modalDescription('Add a new batch when you purchase more stock. Each batch tracks its own quantity and price.')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Ensure quantity_remaining equals quantity_purchased for new batches
                        $data['quantity_remaining'] = $data['quantity_purchased'];
                        return $data;
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Batch Added Successfully')
                            ->body("Added {$record->quantity_purchased} units to inventory")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => "Batch: {$record->batch_number}"),
                Tables\Actions\EditAction::make()
                    ->modalHeading(fn ($record) => "Edit Batch: {$record->batch_number}")
                    ->before(function ($record, array $data) {
                        // Validate quantity_remaining doesn't go negative
                        if (isset($data['quantity_remaining']) && $data['quantity_remaining'] < 0) {
                            Notification::make()
                                ->title('Invalid Quantity')
                                ->body('Remaining quantity cannot be negative.')
                                ->danger()
                                ->send();
                            return false;
                        }
                    }),
                Tables\Actions\Action::make('adjust')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        Forms\Components\Radio::make('adjustment_type')
                            ->label('Adjustment Type')
                            ->options([
                                'reduce' => '➖ Reduce Stock (waste, damage, correction)',
                                'restore' => '➕ Restore Stock (returns, corrections)',
                            ])
                            ->required()
                            ->inline(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->placeholder('Explain the adjustment reason for audit trail'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            if ($data['adjustment_type'] === 'reduce') {
                                $record->consume($data['quantity']);
                                Notification::make()
                                    ->title('Stock Reduced')
                                    ->body("Reduced {$data['quantity']} from batch. Reason: {$data['reason']}")
                                    ->success()
                                    ->send();
                            } else {
                                $record->restore($data['quantity']);
                                Notification::make()
                                    ->title('Stock Restored')
                                    ->body("Restored {$data['quantity']} to batch. Reason: {$data['reason']}")
                                    ->success()
                                    ->send();
                            }
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()
                                ->title('Adjustment Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Adjust Batch Stock')
                    ->modalDescription('⚠️ Stock adjustments should only be used for corrections, waste, or returns.'),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn ($record) => $record->quantity_used > 0)
                    ->tooltip(fn ($record) => $record->quantity_used > 0 
                        ? 'Cannot delete: ' . $record->quantity_used . ' units already consumed from this batch' 
                        : 'Delete this batch'),
            ])
            ->bulkActions([
                // No bulk actions for safety
            ])
            ->emptyStateHeading('No purchase batches yet')
            ->emptyStateDescription('Add your first purchase batch to start tracking inventory. Each batch records a separate purchase with its own price and quantity.')
            ->emptyStateIcon('heroicon-o-archive-box')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add First Purchase')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
