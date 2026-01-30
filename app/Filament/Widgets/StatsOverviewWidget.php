<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Resource as ResourceModel;
use App\Models\InventoryTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalResources = ResourceModel::count();
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'Active')->count();
        $completedProjects = Project::where('status', 'Completed')->count();
        
        // Calculate total inventory value from transactions (ledger-based)
        $totalInventoryValue = InventoryTransaction::whereNull('project_id')
            ->sum(DB::raw('quantity * unit_price'));
        
        // Count recent transactions
        $todayTransactions = InventoryTransaction::whereDate('transaction_date', today())->count();
        
        return [
            Stat::make('Total Resources', $totalResources)
                ->description('Unique items in catalog')
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary'),
            Stat::make('Today\'s Transactions', $todayTransactions)
                ->description('Inventory movements today')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('info'),
            Stat::make('Total Projects', $totalProjects)
                ->description($activeProjects . ' active, ' . $completedProjects . ' completed')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('success'),
            Stat::make('Hub Inventory Value', '$' . number_format($totalInventoryValue, 2))
                ->description('Ledger-based valuation')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}

