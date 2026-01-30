<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResourceResource\Pages;
use App\Filament\Resources\ResourceResource\RelationManagers;
use App\Models\Resource as ResourceModel;
use App\Models\Project;
use App\Services\InventoryTransactionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class ResourceResource extends Resource
{
    protected static ?string $model = ResourceModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationLabel = 'Resources';
    
    protected static ?string $modelLabel = 'Resource';
    
    protected static ?string $navigationGroup = 'Inventory Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Core resource details. The SKU must be unique across all resources.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive name for the resource (e.g., "Portland Cement")'),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Stock Keeping Unit - unique identifier for tracking'),
                        Forms\Components\Select::make('category')
                            ->options([
                                'Raw Materials' => 'Raw Materials',
                                'Tools' => 'Tools',
                                'Equipment' => 'Equipment',
                                'Consumables' => 'Consumables',
                                'Others' => 'Others',
                            ])
                            ->searchable()
                            ->native(false)
                            ->helperText('Group resources by type for easier management'),
                        Forms\Components\TextInput::make('base_unit')
                            ->label('Base Unit')
                            ->required()
                            ->maxLength(50)
                            ->helperText('Unit of measurement (e.g., kg, liter, piece)'),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->helperText('Add notes about specifications, usage, or handling instructions'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('SKU copied!')
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn ($record) => $record->description ? Str::limit($record->description, 50) : null),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Raw Materials' => 'info',
                        'Tools' => 'warning',
                        'Equipment' => 'primary',
                        'Consumables' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_unit')
                    ->label('Unit')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('hub_stock')
                    ->label('Hub Stock')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => 'At central hub'),
                Tables\Columns\TextColumn::make('weighted_avg_price')
                    ->label('Avg Price')
                    ->money('USD')
                    ->description('Weighted avg')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('hub_value')
                    ->label('Hub Value')
                    ->money('USD')
                    ->weight(FontWeight::Bold)
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Raw Materials' => 'Raw Materials',
                        'Tools' => 'Tools',
                        'Equipment' => 'Equipment',
                        'Consumables' => 'Consumables',
                        'Others' => 'Others',
                    ]),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (< 100)')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('transactions', function ($q) {
                            // This is a placeholder - would need proper calculation
                        })
                    ),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDoesntHave('transactions')
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('purchase')
                    ->label('Purchase')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->label('Quantity')
                            ->suffix(fn ($record) => $record->base_unit),
                        Forms\Components\TextInput::make('unit_price')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('$')
                            ->label('Unit Price'),
                        Forms\Components\DatePicker::make('transaction_date')
                            ->required()
                            ->default(now())
                            ->label('Purchase Date')
                            ->maxDate(now()),
                        Forms\Components\TextInput::make('supplier')
                            ->maxLength(255)
                            ->label('Supplier Name'),
                        Forms\Components\TextInput::make('invoice_number')
                            ->maxLength(255)
                            ->label('Invoice #'),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->rows(3),
                    ])
                    ->action(function (ResourceModel $record, array $data) {
                        $service = app(InventoryTransactionService::class);
                        
                        try {
                            $metadata = [];
                            if (!empty($data['supplier'])) $metadata['supplier'] = $data['supplier'];
                            if (!empty($data['invoice_number'])) $metadata['invoice_number'] = $data['invoice_number'];
                            if (!empty($data['notes'])) $metadata['notes'] = $data['notes'];
                            
                            $service->recordPurchase(
                                $record->id,
                                $data['quantity'],
                                $data['unit_price'],
                                \Carbon\Carbon::parse($data['transaction_date']),
                                json_encode($metadata)
                            );
                            
                            Notification::make()
                                ->success()
                                ->title('Purchase Recorded')
                                ->body("Added {$data['quantity']} {$record->base_unit} of {$record->name} to hub inventory.")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Purchase Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('allocate')
                    ->label('Allocate')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('project_id')
                            ->label('Allocate to Project')
                            ->required()
                            ->options(Project::where('status', 'Active')->pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Select the project site to allocate inventory to'),
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->label('Quantity')
                            ->suffix(fn ($record) => $record->base_unit)
                            ->helperText(fn ($record) => "Available at hub: {$record->hub_stock} {$record->base_unit}"),
                        Forms\Components\DatePicker::make('transaction_date')
                            ->required()
                            ->default(now())
                            ->label('Allocation Date')
                            ->maxDate(now()),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->rows(3)
                            ->helperText('Optional notes about this allocation'),
                    ])
                    ->action(function (ResourceModel $record, array $data) {
                        $service = app(InventoryTransactionService::class);
                        
                        try {
                            $metadata = [];
                            if (!empty($data['notes'])) $metadata['notes'] = $data['notes'];
                            
                            $service->recordAllocation(
                                $record->id,
                                $data['project_id'],
                                $data['quantity'],
                                \Carbon\Carbon::parse($data['transaction_date']),
                                !empty($metadata) ? json_encode($metadata) : null
                            );
                            
                            $project = Project::find($data['project_id']);
                            
                            Notification::make()
                                ->success()
                                ->title('Allocation Successful')
                                ->body("Allocated {$data['quantity']} {$record->base_unit} of {$record->name} to {$project->name}.")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Allocation Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('This will delete the resource and ALL associated transactions. This action cannot be undone.'),
                ]),
            ])
            ->emptyStateHeading('No resources yet')
            ->emptyStateDescription('Start by adding resources to your central inventory.')
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add First Resource')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResources::route('/'),
            'create' => Pages\CreateResource::route('/create'),
            'view' => Pages\ViewResource::route('/{record}'),
            'edit' => Pages\EditResource::route('/{record}/edit'),
        ];
    }
}
