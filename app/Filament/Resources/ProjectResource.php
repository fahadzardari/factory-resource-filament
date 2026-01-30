<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use App\Models\Resource as ResourceModel;
use App\Services\InventoryTransactionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                                'Pending' => 'Pending',
                                'Active' => 'Active',
                                'Completed' => 'Completed',
                            ])
                            ->required()
                            ->default('Pending')
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
                        'Pending' => 'gray',
                        'Active' => 'success',
                        'Completed' => 'info',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Active' => 'Active',
                        'Completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('consume')
                    ->label('Consume')
                    ->icon('heroicon-o-fire')
                    ->color('danger')
                    ->visible(fn (Project $record) => $record->status === 'Active')
                    ->form([
                        Forms\Components\Select::make('resource_id')
                            ->label('Resource')
                            ->required()
                            ->options(ResourceModel::pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->helperText('Select the resource to consume'),
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->label('Quantity')
                            ->helperText('Amount to consume from project inventory'),
                        Forms\Components\DatePicker::make('transaction_date')
                            ->required()
                            ->default(now())
                            ->label('Consumption Date')
                            ->maxDate(now()),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->rows(3)
                            ->helperText('Optional notes about this consumption'),
                    ])
                    ->action(function (Project $record, array $data) {
                        $service = app(InventoryTransactionService::class);
                        
                        try {
                            $metadata = [];
                            if (!empty($data['notes'])) $metadata['notes'] = $data['notes'];
                            
                            $service->recordConsumption(
                                $data['resource_id'],
                                $record->id,
                                $data['quantity'],
                                \Carbon\Carbon::parse($data['transaction_date']),
                                !empty($metadata) ? json_encode($metadata) : null
                            );
                            
                            $resource = ResourceModel::find($data['resource_id']);
                            
                            Notification::make()
                                ->success()
                                ->title('Consumption Recorded')
                                ->body("Consumed {$data['quantity']} {$resource->base_unit} of {$resource->name} at {$record->name}.")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Consumption Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('transfer')
                    ->label('Transfer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Project $record) => $record->status === 'Active')
                    ->form([
                        Forms\Components\Select::make('resource_id')
                            ->label('Resource')
                            ->required()
                            ->options(ResourceModel::pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Select the resource to transfer'),
                        Forms\Components\Select::make('to_project_id')
                            ->label('To Project')
                            ->required()
                            ->options(fn ($record) => 
                                Project::where('status', 'Active')
                                    ->where('id', '!=', $record->id)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->helperText('Destination project'),
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->label('Quantity'),
                        Forms\Components\DatePicker::make('transaction_date')
                            ->required()
                            ->default(now())
                            ->label('Transfer Date')
                            ->maxDate(now()),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->rows(3),
                    ])
                    ->action(function (Project $record, array $data) {
                        $service = app(InventoryTransactionService::class);
                        
                        try {
                            $metadata = [];
                            if (!empty($data['notes'])) $metadata['notes'] = $data['notes'];
                            
                            $service->recordTransfer(
                                $data['resource_id'],
                                $record->id,
                                $data['to_project_id'],
                                $data['quantity'],
                                \Carbon\Carbon::parse($data['transaction_date']),
                                !empty($metadata) ? json_encode($metadata) : null
                            );
                            
                            $resource = ResourceModel::find($data['resource_id']);
                            $toProject = Project::find($data['to_project_id']);
                            
                            Notification::make()
                                ->success()
                                ->title('Transfer Successful')
                                ->body("Transferred {$data['quantity']} {$resource->base_unit} of {$resource->name} from {$record->name} to {$toProject->name}.")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Transfer Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
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
            //
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
