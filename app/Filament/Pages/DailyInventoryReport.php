<?php

namespace App\Filament\Pages;

use App\Models\Resource;
use App\Models\Project;
use App\Models\InventoryTransaction;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyInventoryReport extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Daily Inventory Report';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Reports';
    protected static string $view = 'filament.pages.daily-inventory-report';
    protected static ?string $title = 'Daily Inventory Report';

    public ?array $reportData = null;
    public ?\Carbon\Carbon $selectedDate = null;
    public ?array $selectedProjects = null;
    public ?string $report_date = null;
    public ?array $projects = null;

    public function mount(): void
    {
        $this->report_date = now()->format('Y-m-d');
        $this->projects = [];
        $this->form->fill([
            'report_date' => $this->report_date,
            'projects' => [],
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Report Filters')
                ->description('Select date and optionally filter by projects')
                ->schema([
                    Forms\Components\Placeholder::make('instructions')
                        ->content(fn () => view('filament.pages.daily-inventory-report-instructions'))
                        ->columnSpanFull(),

                    Forms\Components\DatePicker::make('report_date')
                        ->label('Report Date')
                        ->required()
                        ->default(now())
                        ->maxDate(now())
                        ->live(),

                    Forms\Components\MultiSelect::make('projects')
                        ->label('Filter by Projects (Optional - Leave empty for all items)')
                        ->hint('Select specific projects or leave empty to see hub inventory')
                        ->options(Project::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('generate')
                ->label('📊 Generate Report')
                ->color('primary')
                ->submit('generateReport'),
        ];
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();
        
        $this->report_date = $data['report_date'] ?? now()->format('Y-m-d');
        $this->projects = $data['projects'] ?? [];
        
        $this->selectedDate = Carbon::createFromFormat('Y-m-d', $this->report_date);
        $this->selectedProjects = $this->projects;

        try {
            $this->reportData = $this->buildReport($this->selectedDate, $this->selectedProjects);
            
            Notification::make()
                ->success()
                ->title('✅ Report Generated')
                ->body("Daily report for " . $this->selectedDate->format('d-M-Y') . " with " . count($this->reportData) . " items")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('❌ Report Generation Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    private function buildReport(\Carbon\Carbon $date, array $projectIds): array
    {
        $resources = Resource::with('transactions')->orderBy('name')->get();
        $report = [];

        foreach ($resources as $resource) {
            // Get opening balance (before this date)
            $openingTxn = $this->getBalanceAsOfDate($resource->id, $date->copy()->subDay()->endOfDay(), $projectIds);
            
            // Get closing balance (end of this date)
            $closingTxn = $this->getBalanceAsOfDate($resource->id, $date->copy()->endOfDay(), $projectIds);

            // Get movements for this date
            $inMovements = $this->getMovementsForDate($resource->id, $date, 'IN', $projectIds);
            $outMovements = $this->getMovementsForDate($resource->id, $date, 'OUT', $projectIds);

            // Calculate totals
            $inQty = $inMovements->sum('quantity');
            $inValue = $inMovements->sum('total_value');
            $outQty = abs($outMovements->sum('quantity')); // OUT transactions are negative
            $outValue = abs($outMovements->sum('total_value'));

            // Only include if there are movements or stock exists
            if ($openingTxn['qty'] > 0 || $closingTxn['qty'] > 0 || $inQty > 0 || $outQty > 0) {
                $report[] = [
                    'resource_name' => $resource->name,
                    'item_code' => $resource->sku,
                    'base_unit' => $resource->base_unit,
                    'projects' => $this->getProjectsForResource($resource->id),
                    'opening_qty' => $openingTxn['qty'],
                    'opening_value' => $openingTxn['value'],
                    'in_qty' => $inQty,
                    'in_value' => $inValue,
                    'out_qty' => $outQty,
                    'out_value' => $outValue,
                    'closing_qty' => $closingTxn['qty'],
                    'avg_price' => $closingTxn['rate'],
                    'closing_value' => $closingTxn['value'],
                    'suppliers' => $this->getSuppliersForDate($resource->id, $date, $projectIds),
                ];
            }
        }

        return $report;
    }

    private function getBalanceAsOfDate(int $resourceId, \Carbon\Carbon $asOfDate, array $projectIds): array
    {
        $query = InventoryTransaction::where('resource_id', $resourceId)
            ->where('transaction_date', '<=', $asOfDate->format('Y-m-d'));

        if (!empty($projectIds)) {
            $query->whereIn('project_id', $projectIds);
        } else {
            $query->whereNull('project_id'); // Hub only
        }

        $transactions = $query->get();
        $totalQty = $transactions->sum('quantity');
        $totalValue = $transactions->sum('total_value');
        $rate = $totalQty > 0 ? $totalValue / $totalQty : 0;

        return [
            'qty' => max(0, $totalQty),
            'value' => max(0, $totalValue),
            'rate' => $rate,
        ];
    }

    private function getMovementsForDate(int $resourceId, \Carbon\Carbon $date, string $type, array $projectIds): Collection
    {
        $query = InventoryTransaction::where('resource_id', $resourceId)
            ->where('transaction_date', $date->format('Y-m-d'));

        if (!empty($projectIds)) {
            $query->whereIn('project_id', $projectIds);
        } else {
            $query->whereNull('project_id'); // Hub only
        }

        if ($type === 'IN') {
            $query->where('quantity', '>', 0)
                ->whereIn('transaction_type', ['GOODS_RECEIPT', 'ALLOCATION_IN', 'TRANSFER_IN']);
        } else {
            $query->where('quantity', '<', 0)
                ->whereIn('transaction_type', ['CONSUMPTION', 'DIRECT_CONSUMPTION', 'ALLOCATION_OUT', 'TRANSFER_OUT']);
        }

        return $query->get();
    }

    private function getSuppliersForDate(int $resourceId, \Carbon\Carbon $date, array $projectIds): string
    {
        $suppliers = InventoryTransaction::where('resource_id', $resourceId)
            ->where('transaction_date', $date->format('Y-m-d'))
            ->where('transaction_type', 'GOODS_RECEIPT')
            ->whereNotNull('supplier')
            ->distinct()
            ->pluck('supplier')
            ->join(', ');

        return $suppliers ?: '-';
    }

    private function getProjectsForResource(int $resourceId): string
    {
        $projectNames = InventoryTransaction::where('resource_id', $resourceId)
            ->distinct()
            ->with('project')
            ->get()
            ->pluck('project.name')
            ->filter()
            ->unique()
            ->join(', ');

        return $projectNames ?: ' ';
    }

    public function downloadExcel()
    {
        if ($this->reportData === null) {
            Notification::make()
                ->warning()
                ->title('⚠️ No Report Generated')
                ->body('Please generate a report first')
                ->send();
            return;
        }

        try {
            $fileName = 'Inventory_Report_' . $this->selectedDate->format('Y-m-d') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
            ];

            $callback = function () {
                $file = fopen('php://output', 'w');
                
                // Write UTF-8 BOM for proper Excel character encoding
                fputs($file, "\xEF\xBB\xBF");
                
                // Write header
                fputcsv($file, [
                    'Item Code',
                    'Item Description',
                    'Unit',
                    'Opening Qty',
                    'Opening Value',
                    'In Qty',
                    'In Value',
                    'Out Qty',
                    'Out Value',
                    'Closing Qty',
                    'Avg Price',
                    'Closing Value',
                    'Projects',
                    'Supplier',
                ]);

                // Write data rows
                foreach ($this->reportData as $item) {
                    fputcsv($file, [
                        $item['item_code'],
                        $item['resource_name'],
                        $item['base_unit'],
                        round($item['opening_qty'], 2),
                        round($item['opening_value'], 2),
                        round($item['in_qty'], 2),
                        round($item['in_value'], 2),
                        round($item['out_qty'], 2),
                        round($item['out_value'], 2),
                        round($item['closing_qty'], 2),
                        round($item['avg_price'], 2),
                        round($item['closing_value'], 2),
                        $item['projects'],
                        $item['suppliers'],
                    ]);
                }

                // Write totals row
                fputcsv($file, [
                    'TOTAL',
                    '',
                    '',
                    round(collect($this->reportData)->sum('opening_qty'), 2),
                    round(collect($this->reportData)->sum('opening_value'), 2),
                    round(collect($this->reportData)->sum('in_qty'), 2),
                    round(collect($this->reportData)->sum('in_value'), 2),
                    round(collect($this->reportData)->sum('out_qty'), 2),
                    round(collect($this->reportData)->sum('out_value'), 2),
                    round(collect($this->reportData)->sum('closing_qty'), 2),
                    '',
                    round(collect($this->reportData)->sum('closing_value'), 2),
                    '',
                    '',
                ]);

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('❌ Download Failed')
                ->body($e->getMessage())
                ->send();
        }
    }
}
