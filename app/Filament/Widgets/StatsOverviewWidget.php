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
        $activeProjects = Project::where('status', 'active')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        
        // Calculate total hub inventory value (ledger-based)
        $hubInventoryValue = InventoryTransaction::whereNull('project_id')
            ->sum(DB::raw('quantity * unit_price'));
        
        // Calculate total allocated value (inventory at all project sites)
        $allocatedValue = InventoryTransaction::whereNotNull('project_id')
            ->sum(DB::raw('quantity * unit_price'));
        
        // Count recent transactions
        $todayTransactions = InventoryTransaction::whereDate('transaction_date', today())->count();
        $weekTransactions = InventoryTransaction::whereBetween('transaction_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        
        // Total transaction count
        $totalTransactions = InventoryTransaction::count();
        
        return [
            Stat::make('Hub Inventory Value', 'AED ' . number_format($hubInventoryValue, 2))
                ->description('Central warehouse value')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('success'),
            
            Stat::make('Allocated Inventory', 'AED ' . number_format($allocatedValue, 2))
                ->description('Value at project sites')
                ->descriptionIcon('heroicon-o-truck')
                ->color('info'),
            
            Stat::make('Active Projects', $activeProjects)
                ->description($completedProjects . ' completed, ' . $totalProjects . ' total')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('warning'),
            
            Stat::make('Total Resources', $totalResources)
                ->description('Items in catalog')
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary'),
            
            Stat::make('Today\'s Transactions', $todayTransactions)
                ->description($weekTransactions . ' this week')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('primary'),
            
            Stat::make('Total Transactions', $totalTransactions)
                ->description('All time ledger entries')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('gray'),
        ];
    }
}

