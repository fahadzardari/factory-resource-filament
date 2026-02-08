<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryTransaction;
use App\Models\Resource;

class BulkPurchase extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    // DEPRECATED: Bulk Purchase page - Using GRN system instead
    // Commenting out navigation to hide from sidebar
    // protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    // protected static ?string $navigationLabel = '🛒 Bulk Purchases';
    // protected static ?int $navigationSort = 20;
    // protected static ?string $navigationGroup = 'Inventory';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.bulk-purchase';
    protected static ?string $title = 'Bulk Purchase Entry (DEPRECATED)';

    public $rows = [];

    protected function getFormSchema(): array
    {
        return [
            Card::make()->schema([
                Repeater::make('rows')
                    ->schema([
                        Select::make('resource_id')
                            ->label('Resource')
                            ->options(Resource::orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->placeholder('100')
                            ->suffix('units'),

                        TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->required()
                            ->placeholder('25.50')
                            ->prefix('AED'),

                        DatePicker::make('transaction_date')
                            ->label('Date')
                            ->default(now())
                            ->required(),

                        TextInput::make('supplier')
                            ->placeholder('Supplier name')
                            ->columnSpan(2),

                        TextInput::make('invoice_number')
                            ->label('Invoice #')
                            ->placeholder('INV-001'),
                    ])
                    ->columns(8)
                    ->defaultItems(5)
                    ->minItems(1)
                    ->addActionLabel('➕ Add another purchase')
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
                'transaction_date' => now()->toDateString(),
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
            return !empty($row['resource_id']) && !empty($row['quantity']) && !empty($row['unit_price']);
        });

        if (empty($rows)) {
            Notification::make()
                ->danger()
                ->title('No valid rows provided')
                ->body('Please fill at least one complete row with resource, quantity, and price.')
                ->send();
            return;
        }

        try {
            DB::transaction(function () use ($rows) {
                foreach ($rows as $row) {
                    InventoryTransaction::create([
                        'resource_id' => $row['resource_id'],
                        'project_id' => null,
                        'transaction_type' => InventoryTransaction::TYPE_PURCHASE,
                        'quantity' => $row['quantity'],
                        'unit_price' => $row['unit_price'],
                        'transaction_date' => $row['transaction_date'] ?? now()->toDateString(),
                        'supplier' => $row['supplier'] ?? null,
                        'invoice_number' => $row['invoice_number'] ?? null,
                        'created_by' => auth()->user()?->id,
                    ]);
                }
            });

            $count = count($rows);
            Notification::make()
                ->success()
                ->title("Success! {$count} purchase record" . ($count > 1 ? 's' : '') . " created")
                ->send();

            // Reset form with fresh 5 rows
            $this->form->fill([
                'rows' => array_fill(0, 5, [
                    'transaction_date' => now()->toDateString(),
                ]),
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error creating purchases')
                ->body($e->getMessage())
                ->send();
        }
    }
}
