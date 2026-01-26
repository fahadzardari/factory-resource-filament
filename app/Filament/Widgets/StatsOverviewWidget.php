<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Resource as ResourceModel;
use App\Models\ResourceBatch;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalResources = ResourceModel::count();
        $lowStockResources = ResourceModel::where('available_quantity', '<', 10)->count();
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        
        // Calculate total inventory value from all batches (accurate batch-based valuation)
        $totalInventoryValue = ResourceBatch::sum(DB::raw('quantity_remaining * purchase_price'));
        
        return [
            Stat::make('Total Resources', $totalResources)
                ->description('Unique items in catalog')
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary'),
            Stat::make('Low Stock Items', $lowStockResources)
                ->description('Resources with qty < 10')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning'),
            Stat::make('Total Projects', $totalProjects)
                ->description($activeProjects . ' active, ' . $completedProjects . ' completed')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('success'),
            Stat::make('Inventory Value', '$' . number_format($totalInventoryValue, 2))
                ->description('Batch-based accurate valuation')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('info'),
        ];
    }
}

