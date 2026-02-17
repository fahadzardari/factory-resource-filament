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
                Forms\Components\View::make('conversions.direct-help-text'),

                Forms\Components\Section::make('Conversion Definition')
                    ->schema([
                        Forms\Components\Select::make('from_unit_id')
                            ->label('Convert From (Source Unit)')
                            ->relationship('fromUnit', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the unit you are converting FROM. Example: if converting kg to g, select Kilogram'),

                        Forms\Components\TextInput::make('conversion_factor')
                            ->label('Conversion Factor')
                            ->numeric()
                            ->required()
                            ->helperText('Enter how many of the target unit equal 1 of the source unit. Example: for kg → g, enter 1000'),

                        Forms\Components\Select::make('to_unit_id')
                            ->label('Convert To (Target Unit)')
                            ->relationship('toUnit', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the unit you are converting TO. Must be the same type as the source unit.'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fromUnit.code')
                    ->label('From Unit')
                    ->badge()
                    ->color('primary')
                    ->description(fn ($record) => $record->fromUnit?->name),

                Tables\Columns\TextColumn::make('conversion_factor')
                    ->label('Conversion Rule')
                    ->formatStateUsing(fn ($state, $record) => "1 {$record->fromUnit->code} = {$state} {$record->toUnit->code}")
                    ->description('Conversion factor'),

                Tables\Columns\TextColumn::make('toUnit.code')
                    ->label('To Unit')
                    ->badge()
                    ->color('success')
                    ->description(fn ($record) => $record->toUnit?->name),

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
            ->filters([
                Tables\Filters\SelectFilter::make('fromUnit.unit_type')
                    ->label('Conversion Type')
                    ->relationship('fromUnit', 'unit_type')
                    ->options([
                        'weight' => 'Weight',
                        'length' => 'Length',
                        'volume' => 'Volume',
                        'area' => 'Area',
                        'count' => 'Count',
                        'custom' => 'Custom',
                    ]),
            ])
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

