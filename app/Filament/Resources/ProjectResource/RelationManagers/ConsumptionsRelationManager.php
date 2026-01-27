<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Exports\DailyConsumptionExport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ConsumptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'consumptions';

    protected static ?string $title = 'Daily Consumption History';

    public function form(Form $form): Form
    {
        $project = $this->getOwnerRecord();

        return $form
            ->schema([
                Forms\Components\DatePicker::make('consumption_date')
                    ->label('Consumption Date')
                    ->required()
                    ->default(today())
                    ->maxDate(today())
                    ->unique(ignoreRecord: true)
                    ->validationAttribute('date'),

                Forms\Components\Select::make('resource_id')
                    ->label('Resource')
                    ->required()
                    ->options(function () use ($project) {
                        return $project->resources()
                            ->pluck('resources.name', 'resources.id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($project) {
                        if ($state) {
                            // Get the current available quantity from pivot
                            $pivot = $project->resources()->where('resource_id', $state)->first()?->pivot;
                            if ($pivot) {
                                $set('opening_balance', $pivot->quantity_available);
                            }
                        }
                    })
                    ->helperText('Select from resources allocated to this project'),

                Forms\Components\TextInput::make('opening_balance')
                    ->label('Opening Balance')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix(function (Forms\Get $get) use ($project) {
                        $resourceId = $get('resource_id');
                        if ($resourceId) {
                            $resource = $project->resources()->find($resourceId);

                            return $resource?->unit_type ?? 'units';
                        }

                        return 'units';
                    })
                    ->helperText('Available quantity at start of day')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\TextInput::make('quantity_consumed')
                    ->label('Quantity Consumed')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->suffix(function (Forms\Get $get) use ($project) {
                        $resourceId = $get('resource_id');
                        if ($resourceId) {
                            $resource = $project->resources()->find($resourceId);

                            return $resource?->unit_type ?? 'units';
                        }

                        return 'units';
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $opening = $get('opening_balance');
                        if ($opening && $state) {
                            $set('closing_balance', max(0, $opening - $state));
                        }
                    })
                    ->rule(function (Forms\Get $get) {
                        return function ($attribute, $value, $fail) use ($get) {
                            $opening = $get('opening_balance');
                            if ($value > $opening) {
                                $fail("Cannot consume more than opening balance of {$opening}");
                            }
                        };
                    }),

                Forms\Components\TextInput::make('closing_balance')
                    ->label('Closing Balance')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix(function (Forms\Get $get) use ($project) {
                        $resourceId = $get('resource_id');
                        if ($resourceId) {
                            $resource = $project->resources()->find($resourceId);

                            return $resource?->unit_type ?? 'units';
                        }

                        return 'units';
                    })
                    ->helperText('Available quantity at end of day')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->placeholder('Any additional information about today\'s consumption')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('consumption_date')
            ->columns([
                Tables\Columns\TextColumn::make('consumption_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('resource.name')
                    ->label('Resource')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('opening_balance')
                    ->label('Opening Balance')
                    ->numeric(2)
                    ->suffix(fn ($record) => ' '.$record->resource->unit_type)
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_consumed')
                    ->label('Consumed')
                    ->numeric(2)
                    ->suffix(fn ($record) => ' '.$record->resource->unit_type)
                    ->sortable()
                    ->color('danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('closing_balance')
                    ->label('Closing Balance')
                    ->numeric(2)
                    ->suffix(fn ($record) => ' '.$record->resource->unit_type)
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Recorded By')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('consumption_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('resource_id')
                    ->label('Resource')
                    ->options(function () {
                        $project = $this->getOwnerRecord();

                        return $project->resources()
                            ->pluck('resources.name', 'resources.id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('consumption_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('consumption_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('consumption_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Record Daily Consumption')
                    ->icon('heroicon-o-minus-circle')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['recorded_by'] = auth()->id();

                        return $data;
                    })
                    ->after(function ($record) {
                        // Update the pivot table's quantity_available
                        $project = $record->project;
                        $project->resources()->updateExistingPivot(
                            $record->resource_id,
                            ['quantity_available' => $record->closing_balance]
                        );
                    }),
                Tables\Actions\Action::make('export')
                    ->label('Export Consumption History')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->default(now()->subMonth()),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->default(now()),
                        Forms\Components\Select::make('resource_ids')
                            ->label('Filter by Resources (Optional)')
                            ->multiple()
                            ->options(function () {
                                $project = $this->getOwnerRecord();

                                return $project->resources()
                                    ->pluck('resources.name', 'resources.id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (array $data) {
                        $project = $this->getOwnerRecord();

                        return Excel::download(
                            new DailyConsumptionExport($project->id, $data),
                            "consumption-history-{$project->code}-".now()->format('Y-m-d-His').'.xlsx'
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->disabled(fn ($record) => $record->consumption_date < today()->subDays(7))
                    ->tooltip(fn ($record) => $record->consumption_date < today()->subDays(7)
                        ? 'Cannot edit records older than 7 days'
                        : null),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn ($record) => $record->consumption_date < today()->subDays(7))
                    ->tooltip(fn ($record) => $record->consumption_date < today()->subDays(7)
                        ? 'Cannot delete records older than 7 days'
                        : null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No consumption records yet')
            ->emptyStateDescription('Start recording daily resource consumption for this project')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
