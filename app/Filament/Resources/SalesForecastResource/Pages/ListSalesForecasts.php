<?php

namespace App\Filament\Resources\SalesForecastResource\Pages;

use App\Filament\Resources\SalesForecastResource;
use App\Filament\Resources\SalesForecastResource\Widgets\SalesForecastChart;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;

class ListSalesForecast extends ListRecords
{
    protected static string $resource = SalesForecastResource::class;

    public function getTableRecords(): EloquentCollection|Paginator|CursorPaginator
    {
        $records = parent::getTableRecords();

        // Only inject predicted data if results are a collection (not paginated)
        if ($records instanceof EloquentCollection) {
            $now = Carbon::now();
            $currentMonth = $now->format('Y-m');
            $nextMonth = $now->addMonth()->format('Y-m');
            
            // Check if we already have next month's data (real data)
            $hasNextMonthData = $records->contains('month', $nextMonth);
            
            if (!$hasNextMonthData && $records->isNotEmpty()) {
                // Use the most recent month's data for prediction
                $mostRecent = $records->first();
                
                $forecastSales = round($mostRecent->total_sales * 1.10, 2); // +10%
                $forecastOrders = ceil($mostRecent->order_count * 1.10);

                // Build forecast object
                $forecastRecord = (object)[
                    'id' => null,
                    'month' => $nextMonth,
                    'total_sales' => $forecastSales,
                    'order_count' => $forecastOrders,
                ];

                // Insert at the beginning to maintain descending order
                $records->prepend($forecastRecord);
            }
        }

        return $records;
    }
     protected function getHeaderWidgets(): array
    {
        return [
            SalesForecastChart::class,
        ];
    }
}