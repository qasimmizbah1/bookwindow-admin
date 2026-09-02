<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {

        $this->columns = 2; 

        return [
            
            Stat::make('Total Customers', Customer::count())
                ->description('Increase in customers')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
                
            Stat::make('Total Products', Product::count())
                ->description('Total products in app')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

             Stat::make('Total Orders', Order::count())
                ->description('Orders')
                ->color('warning')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([9, 3, 4, 7, 6, 2, 5, 9]),

            Stat::make('Last 7 Days Revenue', Order::where('created_at', '>=', Carbon::now()->subDays(7))->sum('total_amount'))
            ->description('Revenue from last week')
            ->color('rose')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ,
            Stat::make('Last Month Revenue', Order::where('created_at', '>=', Carbon::now()->subDays(30))->sum('total_amount'))
                ->description('Revenue from last month')
                ->color('danger')
                ->descriptionIcon('heroicon-m-arrow-trending-up'),


            Stat::make('Best Selling Product', function () {
            $product = Product::query()
            ->withSum('orderItems', 'quantity')
            ->orderByDesc('order_items_sum_quantity')
            ->first();

            return $product ? "{$product->name} ({$product->order_items_sum_quantity} sold)" : 'No sales yet';
            })
            ->description('By quantity sold')
            ->color('info')
            ->descriptionIcon('heroicon-m-star'),
                



            
        ];
    }
}