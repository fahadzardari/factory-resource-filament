<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Project;
use App\Models\ResourceTransfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ResourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'resources';
    
    protected static ?string $title = 'Project Resources';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('resource_id')
                    ->relationship('resource', 'name')
                    ->label('Resource')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn ($context) => $context === 'edit'),
                Forms\Components\TextInput::make('quantity_allocated')
                    ->label('Quantity Allocated')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->helperText('Total quantity assigned to this project'),
                Forms\Components\TextInput::make('quantity_consumed')
                    ->label('Quantity Consumed')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->default(0)
                    ->helperText('Amount already used/consumed'),
                Forms\Components\TextInput::make('quantity_available')
                    ->label('Quantity Available On-Site')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->default(0)
                    ->helperText('Currently available at project site'),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge(),
                Tables\Columns\TextColumn::make('unit_type')
                    ->label('Unit'),
                Tables\Columns\TextColumn::make('pivot.quantity_allocated')
                    ->label('Allocated')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('pivot.quantity_consumed')
                    ->label('Consumed')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('pivot.quantity_available')
                    ->label('Available')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'warning'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('quantity_allocated')
                            ->label('Quantity Allocated')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        Forms\Components\TextInput::make('quantity_consumed')
                            ->label('Quantity Consumed')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0),
                        Forms\Components\TextInput::make('quantity_available')
                            ->label('Quantity Available On-Site')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('transfer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('to_project_id')
                            ->label('Transfer To')
                            ->options(function () {
                                return Project::where('id', '!=', $this->getOwnerRecord()->id)
                                    ->where('status', '!=', 'completed')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->helperText('Select project to transfer resources to'),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity to Transfer')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->helperText(fn ($record) => 'Available: ' . $record->pivot->quantity_available),
                        Forms\Components\Textarea::make('notes')
                            ->label('Transfer Notes'),
                    ])
                    ->action(function ($record, array $data) {
                        $fromProject = $this->getOwnerRecord();
                        $resource = $record;
                        
                        // Validate available quantity
                        if ($data['quantity'] > $resource->pivot->quantity_available) {
                            Notification::make()
                                ->danger()
                                ->title('Insufficient quantity')
                                ->body('Cannot transfer more than available quantity.')
                                ->send();
                            return;
                        }
                        
                        // Update source project
                        $fromProject->resources()->updateExistingPivot($resource->id, [
                            'quantity_available' => $resource->pivot->quantity_available - $data['quantity'],
                        ]);
                        
                        // Update or create in destination project
                        $toProject = Project::find($data['to_project_id']);
                        $existing = $toProject->resources()->where('resource_id', $resource->id)->first();
                        
                        if ($existing) {
                            $toProject->resources()->updateExistingPivot($resource->id, [
                                'quantity_allocated' => $existing->pivot->quantity_allocated + $data['quantity'],
                                'quantity_available' => $existing->pivot->quantity_available + $data['quantity'],
                            ]);
                        } else {
                            $toProject->resources()->attach($resource->id, [
                                'quantity_allocated' => $data['quantity'],
                                'quantity_available' => $data['quantity'],
                                'quantity_consumed' => 0,
                            ]);
                        }
                        
                        // Log transfer
                        ResourceTransfer::create([
                            'resource_id' => $resource->id,
                            'from_project_id' => $fromProject->id,
                            'to_project_id' => $data['to_project_id'],
                            'quantity' => $data['quantity'],
                            'transfer_type' => 'project_to_project',
                            'notes' => $data['notes'] ?? null,
                            'transferred_by' => auth()->id(),
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Resource transferred')
                            ->body("Transferred {$data['quantity']} {$resource->unit_type} to {$toProject->name}")
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
