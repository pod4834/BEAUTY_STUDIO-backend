<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use App\Models\Order;
use Illuminate\Support\Carbon;

class SalesAnalytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = '売上集計';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.sales-analytics';

    public $todaySales = 0;
    public $monthlySales = 0;
    public $totalSales = 0;
    public $totalOrdersCount = 0;
    public $recentOrders = [];

    public function mount(): void
    {
        // 오늘 매출
        $this->todaySales = Order::whereDate('created_at', Carbon::today())->sum('total_amount') ?? 0;

        // 이번 달 매출
        $this->monthlySales = Order::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_amount') ?? 0;

        // 누적 총 매출
        $this->totalSales = Order::sum('total_amount') ?? 0;

        // 총 주문 건수
        $this->totalOrdersCount = Order::count() ?? 0;

        // 최근 주문 10건
        $this->recentOrders = Order::latest()->take(10)->get();
    }
}