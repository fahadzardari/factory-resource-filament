<?php

namespace App\Filament\Resources;

use App\Models\GoodsReceiptNote;
use App\Models\InventoryTransaction;
use App\Services\InventoryTransactionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptNoteResource extends Resource
{
    protected static ?string $model = GoodsReceiptNote::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Goods Receipts (GRN)';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'grn_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Receipt Information')
                    ->description('Record when goods physically arrive at warehouse')
                    ->schema([
                        Forms\Components\TextInput::make('grn_number')
                            ->label('GRN Number')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn () => 'Auto-generated')
                            ->helperText('Auto-generated on save'),

                        Forms\Components\DatePicker::make('receipt_date')
                            ->label('Receipt Date')
                            ->required()
                            ->default(now())
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Supplier & Resource')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Supplier Name')
                                    ->required()
                                    ->unique('suppliers', 'name'),
                                Forms\Components\TextInput::make('contact_person')
                                    ->label('Contact Person'),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel(),
                            ]),

                        Forms\Components\Select::make('resource_id')
                            ->label('Resource/Item')
                            ->relationship('resource', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Quantity & Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('quantity_received')
                            ->label('Quantity Received')
                            ->required()
                            ->numeric()
                            ->minValue(0.001)
                            ->step(0.001)
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $unitPrice = $get('unit_price');
                                if ($state && $unitPrice) {
                                    $set('total_value', round($state * $unitPrice, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Unit Price (per base unit)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $quantity = $get('quantity_received');
                                if ($quantity && $state) {
                                    $set('total_value', round($quantity * $state, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('total_value')
                            ->label('Total Value')
                            ->disabled()
                            ->dehydrated()
                            ->numeric()
                            ->step(0.01),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Details')
                    ->schema([
                        Forms\Components\TextInput::make('delivery_reference')
                            ->label('Delivery Reference / Tracking Number')
                            ->placeholder('e.g., Shipment #, AWB, Invoice Qty, etc.')
                            ->maxLength(255)
                            ->columnSpan('full'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes / Remarks')
                            ->rows(3)
                            ->placeholder('e.g., "Damaged 5 units", "Missing items", etc.')
                            ->columnSpan('full'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('grn_number')
                    ->label('GRN #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn ($record) => route('filament.admin.resources.goods-receipt-notes.edit', $record)),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('resource.name')
                    ->label('Resource/Item')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('quantity_received')
                    ->label('Qty Received')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('gbp')
                    ->sortable()
                    ->alignment('right')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money('gbp')
                    ->sortable()
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('receipt_date')
                    ->label('Receipt Date')
                    ->date('d M, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivery_reference')
                    ->label('Delivery Ref')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('resource_id')
                    ->label('Resource')
                    ->relationship('resource', 'name')
                    ->preload()
                    ->searchable(),

                Tables\Filters\Filter::make('receipt_date')
                    ->form([
                        Forms\Components\DatePicker::make('receipt_from')
                            ->label('Receipt Date From'),
                        Forms\Components\DatePicker::make('receipt_until')
                            ->label('Receipt Date To'),
                    ])
                    ->query(function ($query, array $data): mixed {
                        return $query
                            ->when(
                                $data['receipt_from'],
                                fn ($q) => $q->whereDate('receipt_date', '>=', $data['receipt_from'])
                            )
                            ->when(
                                $data['receipt_until'],
                                fn ($q) => $q->whereDate('receipt_date', '<=', $data['receipt_until'])
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('receipt_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\GoodsReceiptNoteResource\Pages\ListGoodsReceiptNotes::route('/'),
            'create' => \App\Filament\Resources\GoodsReceiptNoteResource\Pages\CreateGoodsReceiptNote::route('/create'),
            'edit' => \App\Filament\Resources\GoodsReceiptNoteResource\Pages\EditGoodsReceiptNote::route('/{record}/edit'),
        ];
    }
}
