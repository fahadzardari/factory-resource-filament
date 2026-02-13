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

        // If multiple projects selected, organize by project
        if (!empty($projectIds) && count($projectIds) > 1) {
            // Get project names
            $projects = Project::whereIn('id', $projectIds)->get()->keyBy('id');
            
            // Initialize report structure: [project_id => [items]]
            foreach ($projectIds as $projectId) {
                $report[$projectId] = [];
            }

            // Build report for each resource and each project separately
            foreach ($resources as $resource) {
                foreach ($projectIds as $projectId) {
                    $reportItem = $this->buildResourceReportForProject($resource, $date, $projectId);
                    
                    if ($reportItem) {
                        $report[$projectId][] = $reportItem;
                    }
                }
            }

            // Format for view with project grouping
            $formattedReport = [];
            foreach ($projectIds as $projectId) {
                $items = $report[$projectId];
                if (!empty($items)) {
                    $formattedReport[] = [
                        'project_id' => $projectId,
                        'project_name' => $projects[$projectId]->name ?? 'Project ' . $projectId,
                        'items' => $items,
                        'totals' => $this->calculateProjectTotals($items),
                    ];
                }
            }

            return $formattedReport;
        } else {
            // Single project or hub - return flat report
            foreach ($resources as $resource) {
                $projectId = !empty($projectIds) ? $projectIds[0] : null;
                $reportItem = $this->buildResourceReportForProject($resource, $date, $projectId);
                
                if ($reportItem) {
                    $report[] = $reportItem;
                }
            }

            return $report;
        }
    }

    private function buildResourceReportForProject(\App\Models\Resource $resource, \Carbon\Carbon $date, ?int $projectId): ?array
    {
        // Get opening balance
        $openingTxn = $this->getBalanceAsOfDate($resource->id, $date->copy()->subDay()->endOfDay(), $projectId ? [$projectId] : []);
        
        // Get closing balance
        $closingTxn = $this->getBalanceAsOfDate($resource->id, $date->copy()->endOfDay(), $projectId ? [$projectId] : []);

        // Get movements for this date
        $inMovements = $this->getMovementsForDate($resource->id, $date, 'IN', $projectId ? [$projectId] : []);
        $outMovements = $this->getMovementsForDate($resource->id, $date, 'OUT', $projectId ? [$projectId] : []);

        // Calculate totals
        $inQty = $inMovements->sum('quantity');
        $inValue = $inMovements->sum('total_value');
        $outQty = abs($outMovements->sum('quantity'));
        $outValue = abs($outMovements->sum('total_value'));

        // Only include if there are movements or stock exists
        $hasMovements = $inQty > 0 || $outQty > 0;
        $hasStock = $openingTxn['qty'] != 0 || $closingTxn['qty'] != 0;
        
        if (!($hasMovements || $hasStock)) {
            return null;
        }

        return [
            'resource_name' => $resource->name,
            'item_code' => $resource->sku,
            'base_unit' => $resource->base_unit,
            'opening_qty' => $openingTxn['qty'],
            'opening_value' => $openingTxn['value'],
            'in_qty' => $inQty,
            'in_value' => $inValue,
            'out_qty' => $outQty,
            'out_value' => $outValue,
            'closing_qty' => $closingTxn['qty'],
            'avg_price' => $closingTxn['rate'],
            'closing_value' => $closingTxn['value'],
            'suppliers' => $this->getSuppliersForDate($resource->id, $date, $projectId ? [$projectId] : []),
        ];
    }

    private function calculateProjectTotals(array $items): array
    {
        return [
            'opening_qty' => collect($items)->sum('opening_qty'),
            'opening_value' => collect($items)->sum('opening_value'),
            'in_qty' => collect($items)->sum('in_qty'),
            'in_value' => collect($items)->sum('in_value'),
            'out_qty' => collect($items)->sum('out_qty'),
            'out_value' => collect($items)->sum('out_value'),
            'closing_qty' => collect($items)->sum('closing_qty'),
            'closing_value' => collect($items)->sum('closing_value'),
        ];
    }

    private function getBalanceAsOfDate(int $resourceId, \Carbon\Carbon $asOfDate, array $projectIds): array
    {
        $query = InventoryTransaction::where('resource_id', $resourceId)
            ->where('transaction_date', '<=', $asOfDate->format('Y-m-d'));

        if (!empty($projectIds)) {
            // PROJECT REPORT: Include all movements to/from that project
            $query->whereIn('project_id', $projectIds);
        } else {
            // SYSTEM-WIDE REPORT: Include only REAL movements
            // - Consumption at ANY project reduces system inventory (include it)
            // - Only exclude internal allocations/transfers
            // Note: We do NOT filter by whereNull('project_id') because consumption
            // at project level still removes items from total system
            $query->whereIn('transaction_type', [
                InventoryTransaction::TYPE_GOODS_RECEIPT,
                InventoryTransaction::TYPE_PURCHASE,
                InventoryTransaction::TYPE_CONSUMPTION,
                InventoryTransaction::TYPE_DIRECT_CONSUMPTION,
            ]);
        }

        $transactions = $query->get();
        $totalQty = $transactions->sum('quantity');
        $totalValue = $transactions->sum('total_value');
        
        // Calculate average price based on actual quantity
        $rate = $totalQty != 0 ? $totalValue / $totalQty : 0;

        return [
            'qty' => $totalQty,           // Show actual balance
            'value' => $totalValue,       // Show actual value
            'rate' => $rate,
        ];
    }

    private function getMovementsForDate(int $resourceId, \Carbon\Carbon $date, string $type, array $projectIds): Collection
    {
        $query = InventoryTransaction::where('resource_id', $resourceId)
            ->where('transaction_date', $date->format('Y-m-d'));

        if ($type === 'IN') {
            // IN movements: Different for system vs project reports
            if (!empty($projectIds)) {
                // Project report: Include all receipts and allocations to this project
                $query->whereIn('project_id', $projectIds);
                $inTypes = ['GOODS_RECEIPT', 'ALLOCATION_IN', 'TRANSFER_IN'];
            } else {
                // System-wide: Only real additions (GOODS_RECEIPT, PURCHASE)
                // Exclude allocations - these are internal transfers, not real system additions
                $query->whereNull('project_id');
                $inTypes = ['GOODS_RECEIPT', 'PURCHASE'];
            }
            
            $query->where('quantity', '>', 0)
                ->whereIn('transaction_type', $inTypes);
        } else {
            // OUT movements: Different for system vs project reports
            if (!empty($projectIds)) {
                // Project report: All consumptions and allocations OUT from this project
                $query->whereIn('project_id', $projectIds);
                $outTypes = ['CONSUMPTION', 'DIRECT_CONSUMPTION', 'ALLOCATION_OUT', 'TRANSFER_OUT'];
            } else {
                // System-wide: Include ALL real consumption (from any location)
                // because consumption removes items from the system entirely
                // But EXCLUDE allocations - they're internal transfers
                // Note: We do NOT filter by whereNull('project_id') for consumption
                // because consumption at a project still reduces total system inventory
                $query->whereIn('transaction_type', ['CONSUMPTION', 'DIRECT_CONSUMPTION']);
            }
            
            $query->where('quantity', '<', 0);
            
            // Apply transaction type filter only if project-filtered
            if (!empty($projectIds)) {
                $query->whereIn('transaction_type', $outTypes);
            }
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

            // Detect if report is grouped by projects or flat
            $isGroupedByProject = !empty($this->reportData) && isset($this->reportData[0]['project_id']);

            $callback = function () use ($isGroupedByProject) {
                $file = fopen('php://output', 'w');
                
                // Write UTF-8 BOM for proper Excel character encoding
                fputs($file, "\xEF\xBB\xBF");
                
                if ($isGroupedByProject) {
                    // GROUPED REPORT: Write by project
                    $isFirstProject = true;
                    
                    foreach ($this->reportData as $projectSection) {
                        // Add blank row between projects (except first)
                        if (!$isFirstProject) {
                            fputcsv($file, []);
                        }
                        $isFirstProject = false;

                        // Write project header
                        fputcsv($file, [
                            'PROJECT: ' . $projectSection['project_name'],
                        ]);

                        // Write column headers
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
                            'Supplier',
                        ]);

                        // Write items for this project
                        foreach ($projectSection['items'] as $item) {
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
                                $item['suppliers'],
                            ]);
                        }

                        // Write project totals
                        fputcsv($file, [
                            $projectSection['project_name'] . ' TOTALS',
                            '',
                            '',
                            round($projectSection['totals']['opening_qty'], 2),
                            round($projectSection['totals']['opening_value'], 2),
                            round($projectSection['totals']['in_qty'], 2),
                            round($projectSection['totals']['in_value'], 2),
                            round($projectSection['totals']['out_qty'], 2),
                            round($projectSection['totals']['out_value'], 2),
                            round($projectSection['totals']['closing_qty'], 2),
                            '',
                            round($projectSection['totals']['closing_value'], 2),
                            '',
                        ]);
                    }
                } else {
                    // FLAT REPORT: Single project or hub
                    // Write column headers
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
                    ]);
                }

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
