<?php

namespace App\Filament\Widgets;

use App\Models\GoodsReceiptNote;
use App\Models\GoodsReceiptNoteLineItem;
use App\Models\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GoodsReceiptSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $grnCountToday = GoodsReceiptNote::whereDate('receipt_date', $today)->count();
        $grnCountThisWeek = GoodsReceiptNote::whereBetween('receipt_date', [$weekStart, now()])->count();
        $grnCountThisMonth = GoodsReceiptNote::whereBetween('receipt_date', [$monthStart, now()])->count();

        // Calculate total value from line items instead of grn table
        $grnValueToday = GoodsReceiptNoteLineItem::join('goods_receipt_notes', 'goods_receipt_note_line_items.grn_id', '=', 'goods_receipt_notes.id')
            ->whereDate('goods_receipt_notes.receipt_date', $today)
            ->sum('goods_receipt_note_line_items.total_value');

        $grnValueThisWeek = GoodsReceiptNoteLineItem::join('goods_receipt_notes', 'goods_receipt_note_line_items.grn_id', '=', 'goods_receipt_notes.id')
            ->whereBetween('goods_receipt_notes.receipt_date', [$weekStart, now()])
            ->sum('goods_receipt_note_line_items.total_value');

        $grnValueThisMonth = GoodsReceiptNoteLineItem::join('goods_receipt_notes', 'goods_receipt_note_line_items.grn_id', '=', 'goods_receipt_notes.id')
            ->whereBetween('goods_receipt_notes.receipt_date', [$monthStart, now()])
            ->sum('goods_receipt_note_line_items.total_value');

        $activeSuppliers = Supplier::where('is_active', true)->count();
        $totalGRNs = GoodsReceiptNote::count();

        return [
            Stat::make('GRNs Today', $grnCountToday)
                ->description('Goods receipts recorded today')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Today\'s Receipt Value', 'AED ' . number_format($grnValueToday, 2))
                ->description('Total value received today')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('This Week', $grnCountThisWeek . ' GRNs')
                ->description(number_format($grnValueThisWeek, 2) . ' AED')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('This Month', $grnCountThisMonth . ' GRNs')
                ->description('AED ' . number_format($grnValueThisMonth, 2))
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning'),

            Stat::make('Total GRNs', $totalGRNs)
                ->description('All goods receipts')
                ->descriptionIcon('heroicon-o-document-check')
                ->color('gray'),

            Stat::make('Active Suppliers', $activeSuppliers)
                ->description('Available suppliers')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('success'),
        ];
    }
}

