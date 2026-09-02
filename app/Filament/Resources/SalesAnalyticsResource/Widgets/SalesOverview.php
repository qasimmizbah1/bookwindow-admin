<?php

namespace App\Filament\Resources\SalesAnalyticsResource\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SalesOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Today Orders', Order::whereDate('created_at', today())->count())
                ->description('Total orders today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($this->getTodayOrderChartData())
                ->color('success'),

            Stat::make('Weekly Orders', Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count())
                ->description('Orders this week')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($this->getWeeklyOrderChartData())
                ->color('info'),

            Stat::make('Monthly Orders', Order::whereMonth('created_at', now()->month)->count())
                ->description('Orders this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($this->getMonthlyOrderChartData())
                ->color('warning'),

            Stat::make('Quarterly Orders', Order::whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()])->count())
                ->description('Orders this quarter')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger'),

            Stat::make('Yearly Orders', Order::whereYear('created_at', now()->year)->count())
                ->description('Orders this year')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($this->getYearlyOrderChartData())
                ->color('primary'),
        ];
    }

    protected function getTodayOrderChartData(): array
    {
        $data = Order::whereDate('created_at', today())
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        return array_values($data);
    }

    protected function getWeeklyOrderChartData(): array
    {
        $data = Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->select(DB::raw('DAYNAME(created_at) as day'), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();

        // Ensure all days are present
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $result = [];
        foreach ($days as $day) {
            $result[] = $data[$day] ?? 0;
        }

        return $result;
    }

    protected function getMonthlyOrderChartData(): array
    {
        $data = Order::whereMonth('created_at', now()->month)
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();

        // Ensure all days are present
        $daysInMonth = now()->daysInMonth;
        $result = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $result[] = $data[$day] ?? 0;
        }

        return $result;
    }

    protected function getYearlyOrderChartData(): array
    {
        $data = Order::whereYear('created_at', now()->year)
            ->select(DB::raw('MONTHNAME(created_at) as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Ensure all months are present
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                  'July', 'August', 'September', 'October', 'November', 'December'];
        $result = [];
        foreach ($months as $month) {
            $result[] = $data[$month] ?? 0;
        }

        return $result;
    }
}