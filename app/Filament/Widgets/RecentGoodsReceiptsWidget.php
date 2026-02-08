<?php

namespace App\Filament\Widgets;

use App\Models\GoodsReceiptNote;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentGoodsReceiptsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Goods Receipts (GRN)';
    protected static ?int $sort = 2;
    protected static ?string $maxContentWidth = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(GoodsReceiptNote::query()->latest('receipt_date')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('grn_number')
                    ->label('GRN #')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),

                Tables\Columns\TextColumn::make('resource.name')
                    ->label('Resource')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('quantity_received')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 2)
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Value')
                    ->money('gbp')
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('receipt_date')
                    ->label('Date')
                    ->date('d M, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('delivery_reference')
                    ->label('Delivery Ref')
                    ->limit(15)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('receipt_date', 'desc')
            ->paginated(false);
    }
}
