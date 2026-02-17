<?php

namespace App\Filament\Resources;

use App\Models\Unit;
use App\Models\UnitConversion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Unit Management';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 50;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Unit Information')
                    ->description('Define a new measurement unit')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Unit Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Kilogram, Meter, Liter')
                            ->helperText('Full name of the unit'),

                        Forms\Components\TextInput::make('code')
                            ->label('Unit Code')
                            ->required()
                            ->unique('units', 'code', ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('e.g., kg, m, l')
                            ->helperText('Short code used in forms and conversions'),

                        Forms\Components\Select::make('unit_type')
                            ->label('Unit Type/Category')
                            ->required()
                            ->options([
                                'weight' => 'Weight (kg, g, lb, etc.)',
                                'length' => 'Length (m, cm, ft, etc.)',
                                'volume' => 'Volume (liter, ml, gallon, etc.)',
                                'area' => 'Area (m², ft², etc.)',
                                'count' => 'Count/Quantity (piece, dozen, box, etc.)',
                                'custom' => 'Custom Type',
                            ])
                            ->helperText('Units of the same type can be converted to each other')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_base_unit')
                            ->label('Base Unit for Type?')
                            ->helperText('Mark the primary unit of this type (e.g., kg for weight, m for length)')
                            ->default(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('E.g., Standard kilogram used for weight measurements')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Unit Conversions')
                    ->description('How this unit converts to other units')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Forms\Components\Repeater::make('conversionsFrom')
                            ->relationship('conversionsFrom')
                            ->label('')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('conversion_factor')
                                        ->label('Multiply By')
                                        ->numeric()
                                        ->step(0.0001)
                                        ->required(),

                                    Forms\Components\Select::make('to_unit_id')
                                        ->label('To Unit')
                                        ->relationship('toUnit', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('➕ Add Conversion'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Unit Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('unit_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->colors([
                        'primary' => 'weight',
                        'success' => 'length',
                        'info' => 'volume',
                        'warning' => 'area',
                        'danger' => 'count',
                        'gray' => 'custom',
                    ]),

                Tables\Columns\IconColumn::make('is_base_unit')
                    ->label('Base Unit?')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('unit_type')
                    ->label('Unit Type')
                    ->options([
                        'weight' => 'Weight',
                        'length' => 'Length',
                        'volume' => 'Volume',
                        'area' => 'Area',
                        'count' => 'Count',
                        'custom' => 'Custom',
                    ])
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_base_unit')
                    ->label('Base Units Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Warn if unit has conversions
                        if ($record->conversionsFrom()->exists()) {
                            Notification::make()
                                ->warning()
                                ->title('⚠️ Unit Has Conversions')
                                ->body('This unit has conversion factors defined. Deleting it will remove those conversions.')
                                ->persistent()
                                ->show();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('unit_type', 'asc')
            ->defaultPaginationPageOption(50);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\UnitResource\Pages\ListUnits::route('/'),
            'create' => \App\Filament\Resources\UnitResource\Pages\CreateUnit::route('/create'),
            'edit' => \App\Filament\Resources\UnitResource\Pages\EditUnit::route('/{record}/edit'),
        ];
    }

    /**
     * Customize the creation form
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('conversionsFrom.toUnit');
    }
}
