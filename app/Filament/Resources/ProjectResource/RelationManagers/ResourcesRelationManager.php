<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Project;
use App\Models\Resource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'resources';
    
    protected static ?string $title = 'Allocated Resources';
    
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Allocation Details')
                    ->description('Allocate resources from Central Hub to this project')
                    ->schema([
                        Forms\Components\TextInput::make('quantity_allocated')
                            ->label('Quantity to Allocate')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->suffix(fn ($get) => Resource::find($get('resource_id'))?->unit_type ?? 'units')
                            ->helperText('Amount to reserve for this project from Central Hub'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('� Project Resources')
            ->description('Resources allocated to this project from Central Hub')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Resource')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->sku),
                    
                Tables\Columns\TextColumn::make('pivot.quantity_allocated')
                    ->label('Allocated')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn ($record) => ' ' . $record->unit_type)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('pivot.quantity_consumed')
                    ->label('Consumed')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn ($record) => ' ' . $record->unit_type)
                    ->sortable()
                    ->default(0),
                    
                Tables\Columns\TextColumn::make('pivot.quantity_available')
                    ->label('Available')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn ($record) => ' ' . $record->unit_type)
                    ->sortable()
                    ->getStateUsing(fn ($record) => 
                        ($record->pivot->quantity_allocated ?? 0) - ($record->pivot->quantity_consumed ?? 0)
                    )
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('➕ Allocate Resource')
                    ->color('success')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('quantity_allocated')
                            ->label('Quantity to Allocate')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->helperText('Amount to reserve for this project'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Adjust')
                    ->form([
                        Forms\Components\TextInput::make('quantity_allocated')
                            ->label('Allocated Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0.01),
                    ]),
                Tables\Actions\DetachAction::make()
                    ->label('Remove'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Resources Allocated')
            ->emptyStateDescription('Click "Allocate Resource" to assign resources from Central Hub to this project')
            ->emptyStateIcon('heroicon-o-archive-box-x-mark');
    }
}
