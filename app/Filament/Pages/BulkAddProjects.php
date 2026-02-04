<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Models\Project;

class BulkAddProjects extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = '🏗️ Bulk Add Projects';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationGroup = 'Projects';
    protected static string $view = 'filament.pages.bulk-add-projects';
    protected static ?string $title = 'Bulk Add Projects';

    public $rows = [];

    protected function getFormSchema(): array
    {
        return [
            Card::make()->schema([
                Repeater::make('rows')
                    ->schema([
                        TextInput::make('name')
                            ->label('Project Name')
                            ->required()
                            ->placeholder('Factory Construction Phase 1')
                            ->columnSpan(2),

                        TextInput::make('code')
                            ->label('Project Code')
                            ->required()
                            ->placeholder('PROJ-001')
                            ->unique(Project::class, 'code', ignoreRecord: true),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'on_hold' => 'On Hold',
                            ])
                            ->default('pending'),

                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(now()),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->after('start_date'),

                        Textarea::make('description')
                            ->placeholder('Optional project description')
                            ->rows(2)
                            ->columnSpan(2),
                    ])
                    ->columns(8)
                    ->defaultItems(5)
                    ->minItems(1)
                    ->addActionLabel('➕ Add another project')
                    ->collapsible()
                    ->cloneable()
                    ->reorderable(false),
            ])->columnSpan('full'),
        ];
    }

    public function mount(): void
    {
        $this->form->fill([
            'rows' => array_fill(0, 5, [
                'status' => 'pending',
                'start_date' => now()->toDateString(),
            ]),
        ]);
    }

    protected function getFormModel(): ?string
    {
        return null;
    }

    public function submit()
    {
        $data = $this->form->getState();
        $rows = $data['rows'] ?? [];

        // Filter out empty rows
        $rows = array_filter($rows, function ($row) {
            return !empty($row['name']) && !empty($row['code']) && !empty($row['status']) && !empty($row['start_date']);
        });

        if (empty($rows)) {
            Notification::make()
                ->danger()
                ->title('No valid rows provided')
                ->body('Please fill at least one complete row with all required fields.')
                ->send();
            return;
        }

        try {
            $created = [];
            $skipped = [];

            DB::transaction(function () use ($rows, &$created, &$skipped) {
                foreach ($rows as $index => $row) {
                    // Check for duplicate code
                    if (Project::where('code', $row['code'])->exists()) {
                        $skipped[] = "Row " . ($index + 1) . ": Code '{$row['code']}' already exists";
                        continue;
                    }

                    Project::create([
                        'name' => $row['name'],
                        'code' => $row['code'],
                        'status' => $row['status'],
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date'] ?? null,
                        'description' => $row['description'] ?? null,
                    ]);

                    $created[] = $row['name'];
                }
            });

            $count = count($created);
            $notification = Notification::make();

            if ($count > 0) {
                $notification->success()
                    ->title("Success! {$count} project" . ($count > 1 ? 's' : '') . " created");
            }

            if (!empty($skipped)) {
                $notification->warning()
                    ->title('Some rows were skipped')
                    ->body(implode("\n", $skipped));
            }

            $notification->send();

            // Reset form with fresh 5 rows
            $this->form->fill([
                'rows' => array_fill(0, 5, [
                    'status' => 'pending',
                    'start_date' => now()->toDateString(),
                ]),
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error creating projects')
                ->body($e->getMessage())
                ->send();
        }
    }
}
