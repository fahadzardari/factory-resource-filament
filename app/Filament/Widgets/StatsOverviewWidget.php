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
        
        // Get low stock resources (hub stock < 100 units)
        $lowStockCount = ResourceModel::whereHas('inventoryTransactions', function ($query) {
            $query->whereNull('project_id');
        })
        ->get()
        ->filter(function ($resource) {
            return $resource->hub_stock < 100;
        })
        ->count();
        
        return [
            Stat::make('Hub Inventory Value', 'PKR ' . number_format($hubInventoryValue, 2))
                ->description('Central warehouse value')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('success'),
            
            Stat::make('Allocated Inventory', 'PKR ' . number_format($allocatedValue, 2))
                ->description('Value at project sites')
                ->descriptionIcon('heroicon-o-truck')
                ->color('info'),
            
            Stat::make('Active Projects', $activeProjects)
                ->description($completedProjects . ' completed, ' . $totalProjects . ' total')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('warning'),
            
            Stat::make('Total Resources', $totalResources)
                ->description($lowStockCount > 0 ? $lowStockCount . ' low stock items' : 'All items in stock')
                ->descriptionIcon('heroicon-o-cube')
                ->color($lowStockCount > 0 ? 'danger' : 'primary'),
            
            Stat::make('Today\'s Transactions', $todayTransactions)
                ->description($weekTransactions . ' this week')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('primary'),
            
            Stat::make('Total Transactions', InventoryTransaction::count())
                ->description('All time ledger entries')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('gray'),
        ];
    }
}

