<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VendorStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->isVendor() ?? false;
    }

    protected function getStats(): array
    {
        $this->columns = 3;

        $vendor = auth()->user()?->vendor;

        if (!$vendor) {
            return [
                Stat::make('Vendor Profile', 'Not Configured')
                    ->description('Please contact admin to link your vendor profile')
                    ->color('danger')
            ];
        }

        $totalProducts = Product::where('vendor_id', $vendor->id)->count();
        $activeProducts = Product::where('vendor_id', $vendor->id)->where('is_visible', 1)->count();
        $pendingProducts = $totalProducts - $activeProducts;

        $totalOrders = Order::whereHas('items', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })->count();

        $pendingFulfillment = Order::whereHas('items', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })->whereIn('status', ['new', 'pending', 'processing'])->count();

        $last30DaysEarnings = OrderItem::where('vendor_id', $vendor->id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->sum(DB::raw('quantity * price'));

        $totalEarnings = OrderItem::where('vendor_id', $vendor->id)
            ->sum(DB::raw('quantity * price'));

        return [
            Stat::make('My Products', $totalProducts)
                ->description("{$activeProducts} Active | {$pendingProducts} Pending Approval")
                ->descriptionIcon('heroicon-m-book-open')
                ->color('info')
                ->chart([3, 5, 4, 6, 8, 7, $totalProducts]),

            Stat::make('My Orders', $totalOrders)
                ->description('Total orders containing your items')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning')
                ->chart([2, 4, 3, 5, 4, 6, $totalOrders]),

            Stat::make('Pending Fulfillment', $pendingFulfillment)
                ->description('Orders awaiting dispatch / shipping')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingFulfillment > 0 ? 'danger' : 'success'),

            Stat::make('30 Days Earnings', '₹' . number_format($last30DaysEarnings, 2))
                ->description('Revenue from last 30 days')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Lifetime Earnings', '₹' . number_format($totalEarnings, 2))
                ->description('Total gross sales across all orders')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }
}
