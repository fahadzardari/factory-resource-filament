<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Models\Resource;

class BulkAddResources extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = '📦 Bulk Add Resources';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Bulk Operations';
    protected static string $view = 'filament.pages.bulk-add-resources';
    protected static ?string $title = 'Bulk Add Resources';

    public $rows = [];

    protected function getFormSchema(): array
    {
        return [
            Card::make()->schema([
                Repeater::make('rows')
                    ->schema([
                        TextInput::make('name')
                            ->label('Resource Name')
                            ->required()
                            ->placeholder('Cement')
                            ->columnSpan(2),

                        TextInput::make('sku')
                            ->label('SKU Code')
                            ->required()
                            ->placeholder('CEM-001')
                            ->unique(Resource::class, 'sku', ignoreRecord: true),

                        Select::make('category')
                            ->label('Category')
                            ->required()
                            ->options([
                                'Raw Materials' => 'Raw Materials',
                                'Consumables' => 'Consumables',
                                'Tools' => 'Tools',
                                'Equipment' => 'Equipment',
                                'Safety Equipment' => 'Safety Equipment',
                                'Office Supplies' => 'Office Supplies',
                                'Other' => 'Other',
                            ])
                            ->searchable()
                            ->columnSpan(2),

                        Select::make('base_unit')
                            ->label('Base Unit')
                            ->required()
                            ->options([
                                'kg' => 'Kilogram (kg)',
                                'g' => 'Gram (g)',
                                'ton' => 'Ton',
                                'liter' => 'Liter',
                                'ml' => 'Milliliter (ml)',
                                'gallon' => 'Gallon',
                                'm' => 'Meter (m)',
                                'cm' => 'Centimeter (cm)',
                                'ft' => 'Feet (ft)',
                                'pieces' => 'Pieces',
                                'box' => 'Box',
                                'bag' => 'Bag',
                                'dozen' => 'Dozen',
                            ])
                            ->searchable()
                            ->default('pieces'),

                        Textarea::make('description')
                            ->placeholder('Optional description')
                            ->rows(2)
                            ->columnSpan(3),
                    ])
                    ->columns(8)
                    ->defaultItems(5)
                    ->minItems(1)
                    ->addActionLabel('➕ Add another resource')
                    ->collapsible()
                    ->cloneable()
                    ->reorderable(false),
            ])->columnSpan('full'),
        ];
    }

    public function mount(): void
    {
        $this->form->fill([
            'rows' => array_fill(0, 5, []),
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
            return !empty($row['name']) && !empty($row['sku']) && !empty($row['category']) && !empty($row['base_unit']);
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
                    // Check for duplicate SKU
                    if (Resource::where('sku', $row['sku'])->exists()) {
                        $skipped[] = "Row " . ($index + 1) . ": SKU '{$row['sku']}' already exists";
                        continue;
                    }

                    Resource::create([
                        'name' => $row['name'],
                        'sku' => $row['sku'],
                        'category' => $row['category'],
                        'base_unit' => $row['base_unit'],
                        'description' => $row['description'] ?? null,
                    ]);

                    $created[] = $row['name'];
                }
            });

            $count = count($created);
            $notification = Notification::make();

            if ($count > 0) {
                $notification->success()
                    ->title("Success! {$count} resource" . ($count > 1 ? 's' : '') . " created");
            }

            if (!empty($skipped)) {
                $notification->warning()
                    ->title('Some rows were skipped')
                    ->body(implode("\n", $skipped));
            }

            $notification->send();

            // Reset form with fresh 5 rows
            $this->form->fill([
                'rows' => array_fill(0, 5, []),
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error creating resources')
                ->body($e->getMessage())
                ->send();
        }
    }
}
