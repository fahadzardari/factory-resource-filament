<?php

namespace App\Filament\Resources;

use App\Models\UnitConversion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UnitConversionResource extends Resource
{
    protected static ?string $model = UnitConversion::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Unit Conversions';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 51;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('from_unit_id')
                    ->label('From Unit')
                    ->relationship('fromUnit', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('conversion_factor')
                    ->label('Multiply By')
                    ->numeric()
                    ->step(0.0001)
                    ->required(),

                Forms\Components\Select::make('to_unit_id')
                    ->label('To Unit')
                    ->relationship('toUnit', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fromUnit.code')
                    ->label('From')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('conversion_factor')
                    ->label('×')
                    ->alignment('center')
                    ->formatStateUsing(fn ($state) => number_format($state, 4)),

                Tables\Columns\TextColumn::make('toUnit.code')
                    ->label('To')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('fromUnit.unit_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'primary' => 'weight',
                        'success' => 'length',
                        'info' => 'volume',
                        'warning' => 'area',
                        'danger' => 'count',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M, Y'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\UnitConversionResource\Pages\ListUnitConversions::route('/'),
            'create' => \App\Filament\Resources\UnitConversionResource\Pages\CreateUnitConversion::route('/create'),
            'edit' => \App\Filament\Resources\UnitConversionResource\Pages\EditUnitConversion::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['fromUnit', 'toUnit']);
    }
}

