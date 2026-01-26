<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use App\Models\ResourceTransfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    
    protected static ?string $navigationLabel = 'Projects';
    
    protected static ?string $navigationGroup = 'Project Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('Project Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier for this project'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'active' => 'Active',
                                'completed' => 'Completed',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Timeline')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->native(false),
                        Forms\Components\DatePicker::make('end_date')
                            ->native(false)
                            ->after('start_date'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'active' => 'success',
                        'completed' => 'info',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('resources_count')
                    ->counts('resources')
                    ->label('Resources')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn (Project $record) => $record->status === 'completed')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Project')
                    ->modalDescription(fn (Project $record) => "Are you sure you want to complete project '{$record->name}'? All remaining resources will be returned to the warehouse.")
                    ->action(function (Project $record) {
                        // Return all resources to warehouse
                        foreach ($record->resources as $resource) {
                            if ($resource->pivot->quantity_available > 0) {
                                // Return to warehouse
                                $resource->update([
                                    'available_quantity' => $resource->available_quantity + $resource->pivot->quantity_available,
                                ]);
                                
                                // Log transfer
                                ResourceTransfer::create([
                                    'resource_id' => $resource->id,
                                    'from_project_id' => $record->id,
                                    'to_project_id' => null,
                                    'quantity' => $resource->pivot->quantity_available,
                                    'transfer_type' => 'project_to_warehouse',
                                    'notes' => 'Project completion - returned to warehouse',
                                    'transferred_by' => auth()->id(),
                                ]);
                            }
                        }
                        
                        // Update project status
                        $record->update([
                            'status' => 'completed',
                            'end_date' => $record->end_date ?? now(),
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Project Completed')
                            ->body('All resources have been returned to the warehouse.')
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ResourcesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
