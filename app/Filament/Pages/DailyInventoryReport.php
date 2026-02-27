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
            
            // Count total items across all projects
            $totalItems = 0;
            $totalProjects = 0;
            foreach ($this->reportData as $section) {
                if (!($section['is_system_total'] ?? false)) {
                    $totalItems += count($section['items']);
                    $totalProjects++;
                }
            }
            
            Notification::make()
                ->success()
                ->title('✅ Report Generated')
                ->body("Daily report for " . $this->selectedDate->format('d-M-Y') . " - " . $totalProjects . " project(s) with " . $totalItems . " items")
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
        
        // Determine which projects to include
        if (empty($projectIds)) {
            // SYSTEM-WIDE REPORT: Get all projects that have transactions up to this date
            $projectIds = InventoryTransaction::where('transaction_date', '<=', $date->format('Y-m-d'))
                ->whereNotNull('project_id')
                ->distinct()
                ->pluck('project_id')
                ->values()
                ->toArray();
            
            // If no project transactions, still show hub/system inventory
            $includeHub = true;
        } else {
            // FILTERED REPORT: Only show selected projects
            $includeHub = false;
        }

        // Get all projects for reference
        $allProjects = Project::whereIn('id', $projectIds)->get()->keyBy('id');

        // Build report grouped by project
        $reportSections = [];
        $systemTotals = [
            'opening_qty' => 0,
            'opening_value' => 0,
            'in_qty' => 0,
            'in_value' => 0,
            'out_qty' => 0,
            'out_value' => 0,
            'closing_qty' => 0,
            'closing_value' => 0,
        ];

        // Build report for each project
        foreach ($projectIds as $projectId) {
            $projectItems = [];

            foreach ($resources as $resource) {
                $reportItem = $this->buildResourceReportForProject($resource, $date, $projectId);

                if ($reportItem) {
                    $projectItems[] = $reportItem;
                }
            }

            // Only include project section if it has items
            if (!empty($projectItems)) {
                $projectTotals = $this->calculateProjectTotals($projectItems);
                
                $reportSections[] = [
                    'project_id' => $projectId,
                    'project_name' => $allProjects[$projectId]->name ?? 'Project ' . $projectId,
                    'items' => $projectItems,
                    'totals' => $projectTotals,
                ];

                // Add to system totals
                $systemTotals['opening_qty'] += $projectTotals['opening_qty'];
                $systemTotals['opening_value'] += $projectTotals['opening_value'];
                $systemTotals['in_qty'] += $projectTotals['in_qty'];
                $systemTotals['in_value'] += $projectTotals['in_value'];
                $systemTotals['out_qty'] += $projectTotals['out_qty'];
                $systemTotals['out_value'] += $projectTotals['out_value'];
                $systemTotals['closing_qty'] += $projectTotals['closing_qty'];
                $systemTotals['closing_value'] += $projectTotals['closing_value'];
            }
        }

        // Add system-wide totals section at the end
        if (!empty($reportSections)) {
            $reportSections[] = [
                'project_id' => null,
                'project_name' => '📊 SYSTEM WIDE TOTAL',
                'items' => [],
                'totals' => $systemTotals,
                'is_system_total' => true,
            ];
        }

        return $reportSections;
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
            'projects' => $this->getProjectsForResource($resource->id, $projectId ? [$projectId] : [], $this->selectedProjects ?? []),
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

    private function getProjectsForResource(int $resourceId, array $reportProjectIds = [], array $selectedProjectIds = []): string
    {
        // Get all projects where this resource has been used
        $allProjects = InventoryTransaction::where('resource_id', $resourceId)
            ->whereNotNull('project_id')
            ->distinct()
            ->with('project')
            ->get()
            ->pluck('project.id')
            ->unique();

        // Filter based on context
        if (!empty($selectedProjectIds)) {
            // Filtered report: Only show projects from the selected filter
            $projectIds = $allProjects->intersect($selectedProjectIds);
        } else {
            // System-wide report: Show all projects where resource is used
            $projectIds = $allProjects;
        }

        // Get project names
        if ($projectIds->isEmpty()) {
            return '-';
        }

        $projectNames = Project::whereIn('id', $projectIds->values())
            ->orderBy('name')
            ->pluck('name')
            ->join(', ');

        return $projectNames ?: '-';
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
            $fileName = 'Inventory_Report_' . $this->selectedDate->format('Y-m-d') . '.xlsx';
            
            // Create export class with report data
            $export = new class($this->reportData, $this->selectedDate) implements \Maatwebsite\Excel\Concerns\FromArray, 
                                     \Maatwebsite\Excel\Concerns\WithStyles,
                                     \Maatwebsite\Excel\Concerns\WithColumnWidths
                {
                    private $reportData;
                    private $selectedDate;

                    public function __construct($reportData, $selectedDate)
                    {
                        $this->reportData = $reportData;
                        $this->selectedDate = $selectedDate;
                    }

                    public function array(): array
                    {
                        $rows = [];

                        // Row 1: Title
                        $rows[] = ['SYSTEM WIDE INVENTORY REPORT - ' . $this->selectedDate->format('d M Y')];
                        // Row 2: blank spacer (use [''] not [] so row actually gets created)
                        $rows[] = [''];

                        foreach ($this->reportData as $projectSection) {
                            if ($projectSection['is_system_total'] ?? false) {
                                // Blank spacer row before system total
                                $rows[] = [''];
                                // System total header
                                $rows[] = ['SYSTEM WIDE TOTAL'];
                                // Column headers
                                $rows[] = [
                                    'Metric',
                                    'Opening Value (AED)',
                                    'In Value (AED)',
                                    'Out Value (AED)',
                                    'Closing Value (AED)',
                                ];
                                // Values
                                $rows[] = [
                                    'TOTAL',
                                    round($projectSection['totals']['opening_value'], 2),
                                    round($projectSection['totals']['in_value'], 2),
                                    round($projectSection['totals']['out_value'], 2),
                                    round($projectSection['totals']['closing_value'], 2),
                                ];
                            } else {
                                // Project name row
                                $rows[] = [strtoupper($projectSection['project_name'])];
                                // Column headers
                                $rows[] = [
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
                                ];
                                // Item rows - always show 0 for zero values
                                foreach ($projectSection['items'] as $item) {
                                    $rows[] = [
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
                                    ];
                                }
                                // Project totals row
                                $rows[] = [
                                    strtoupper($projectSection['project_name']) . ' TOTALS',
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
                                ];
                            }
                        }

                        return $rows;
                    }

                    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
                    {
                        $styles = [];
                        $row = 1;

                        // Row 1: Title
                        $styles[$row] = ['font' => ['bold' => true, 'size' => 14]];
                        $row++; // row 2: blank spacer
                        $row++; // row 3: first data row

                        foreach ($this->reportData as $projectSection) {
                            if ($projectSection['is_system_total'] ?? false) {
                                // blank spacer
                                $row++;
                                // SYSTEM WIDE TOTAL header
                                $styles[$row] = ['font' => ['bold' => true, 'size' => 12]];
                                $row++;
                                // Metric column headers
                                $styles[$row] = ['font' => ['bold' => true]];
                                $row++;
                                // Values row
                                $styles[$row] = ['font' => ['bold' => true]];
                                $row++;
                            } else {
                                // Project name - BLUE background across all columns
                                $columnRange = 'A' . $row . ':M' . $row;
                                $styles[$columnRange] = [
                                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
                                ];
                                $row++;
                                // Column headers - bold
                                $styles[$row] = ['font' => ['bold' => true]];
                                $row++;
                                // Item rows - no styling
                                foreach ($projectSection['items'] as $item) {
                                    $row++;
                                }
                                // Totals row - bold
                                $styles[$row] = ['font' => ['bold' => true]];
                                $row++;
                            }
                        }

                        return $styles;
                    }

                    public function columnWidths(): array
                    {
                        return [
                            'A' => 15,
                            'B' => 30,
                            'C' => 12,
                            'D' => 14,
                            'E' => 16,
                            'F' => 12,
                            'G' => 14,
                            'H' => 12,
                            'I' => 14,
                            'J' => 14,
                            'K' => 12,
                            'L' => 16,
                            'M' => 25,
                        ];
                    }
                };

            return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('❌ Download Failed')
                ->body($e->getMessage())
                ->send();
        }
    }
}
