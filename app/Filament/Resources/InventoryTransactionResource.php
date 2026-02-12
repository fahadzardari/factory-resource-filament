<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryTransactionResource\Pages;
use App\Filament\Resources\InventoryTransactionResource\RelationManagers;
use App\Models\InventoryTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;

class InventoryTransactionResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'Transaction History';
    
    protected static ?string $modelLabel = 'Transaction';
    
    protected static ?string $navigationGroup = 'Inventory Management';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('note')
                    ->content('Transactions are immutable and cannot be edited. This is a read-only view.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('resource.name')
                    ->label('Resource')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->resource->sku ?? null),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Central Hub')
                    ->description(fn ($record) => $record->project ? $record->project->code : 'HUB'),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'GOODS_RECEIPT' => 'success',
                        'PURCHASE' => 'gray',
                        'DIRECT_CONSUMPTION' => 'danger',
                        'ALLOCATION_OUT' => 'warning',
                        'ALLOCATION_IN' => 'info',
                        'CONSUMPTION' => 'danger',
                        'TRANSFER_OUT' => 'gray',
                        'TRANSFER_IN' => 'primary',
                        'ADJUSTMENT' => 'secondary',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => 
                        str_replace('_', ' ', $state)
                    )
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->resource->base_unit ?? ''),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('AED')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money('AED')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('success')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('resource_id')
                    ->label('Resource')
                    ->relationship('resource', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label('Transaction Type')
                    ->options([
                        'GOODS_RECEIPT' => 'Goods Receipt (GRN)',
                        'DIRECT_CONSUMPTION' => 'Direct Consumption',
                        'ALLOCATION_OUT' => 'Allocation Out',
                        'ALLOCATION_IN' => 'Allocation In',
                        'CONSUMPTION' => 'Project Consumption',
                        'TRANSFER_OUT' => 'Transfer Out',
                        'TRANSFER_IN' => 'Transfer In',
                        'ADJUSTMENT' => 'Adjustment',
                        'PURCHASE' => 'Purchase (Legacy)',
                    ]),
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Export action can be added later
                ]),
            ])
            ->emptyStateHeading('No transactions yet')
            ->emptyStateDescription('Transaction history will appear here once you start recording purchases, allocations, consumption, and transfers.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Transaction Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('transaction_date')
                            ->label('Transaction Date')
                            ->date('F j, Y'),
                        Infolists\Components\TextEntry::make('transaction_type')
                            ->label('Type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'GOODS_RECEIPT' => 'success',
                                'PURCHASE' => 'gray',
                                'DIRECT_CONSUMPTION' => 'danger',
                                'ALLOCATION_OUT' => 'warning',
                                'ALLOCATION_IN' => 'info',
                                'CONSUMPTION' => 'danger',
                                'TRANSFER_OUT' => 'gray',
                                'TRANSFER_IN' => 'primary',
                                'ADJUSTMENT' => 'secondary',
                                default => 'secondary',
                            }),
                        Infolists\Components\TextEntry::make('resource.name')
                            ->label('Resource'),
                        Infolists\Components\TextEntry::make('resource.sku')
                            ->label('SKU'),
                        Infolists\Components\TextEntry::make('project.name')
                            ->label('Project')
                            ->placeholder('Central Hub'),
                        Infolists\Components\TextEntry::make('project.code')
                            ->label('Project Code')
                            ->placeholder('HUB'),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Quantity & Pricing')
                    ->schema([
                        Infolists\Components\TextEntry::make('quantity')
                            ->label('Quantity')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(fn ($record) => ' ' . $record->resource->base_unit),
                        Infolists\Components\TextEntry::make('unit_price')
                            ->label('Unit Price')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('total_value')
                            ->label('Total Value')
                            ->money('AED')
                            ->weight(FontWeight::Bold),
                    ])->columns(3),
                    
                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('metadata')
                            ->label('Additional Information')
                            ->formatStateUsing(fn ($state) => $state ? json_encode(json_decode($state), JSON_PRETTY_PRINT) : 'None')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('createdBy.name')
                            ->label('Created By')
                            ->placeholder('System'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Recorded At')
                            ->dateTime(),
                    ])->columns(2)
                    ->collapsible(),
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
            'index' => Pages\ListInventoryTransactions::route('/'),
            'view' => Pages\ViewInventoryTransaction::route('/{record}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit($record): bool
    {
        return false;
    }
    
    public static function canDelete($record): bool
    {
        return false;
    }
    
    public static function canDeleteAny(): bool
    {
        return false;
    }
}
