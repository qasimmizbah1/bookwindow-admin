<?php

namespace App\Filament\Resources\SalesTopSellingProductsResource\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class TopSellingProductsChart extends ChartWidget
{
    protected static ?string $heading = 'Top Selling Products Trend';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'year';
    public ?int $productLimit = 10; // Number of top products to show

    public function mount(): void
    {
        $this->filter = Session::get('top_selling_filter', 'year');
    }

    protected function getData(): array
    {
        $data = OrderItem::query()
            ->select([
                'order_items.product_id',
                'order_items.product_name', // Using product_name from order_items to avoid GROUP BY issues
                DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue'),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders'),
            ])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->when($this->filter, function ($query) {
                return match ($this->filter) {
                    'today' => $query->whereDate('orders.created_at', today()),
                    'week' => $query->whereBetween('orders.created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                    'month' => $query->whereBetween('orders.created_at', [now()->startOfMonth(), now()->endOfMonth()]),
                    'quarter' => $query->whereBetween('orders.created_at', [now()->startOfQuarter(), now()->endOfQuarter()]),
                    'year' => $query->whereBetween('orders.created_at', [now()->startOfYear(), now()->endOfYear()]),
                    default => $query,
                };
            })
            ->groupBy('order_items.product_id', 'order_items.product_name') // Group by both fields from order_items
            ->orderByDesc('total_orders') // Changed to order by total_orders instead of total_quantity
            ->limit($this->productLimit)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Order Count',
                    'data' => $data->map(fn ($item) => $item->total_orders),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6',
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Quantity Sold',
                    'data' => $data->map(fn ($item) => $item->total_quantity),
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                    'borderWidth' => 2,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $data->map(fn ($item) => 'Product ID - ' . $item->product_id), // Simplified label
            'rawData' => $data,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            'quarter' => 'This Quarter',
            'year' => 'This Year',
        ];
    }

    public function updatedFilter(): void
    {
        // Store the filter value in session when it changes
        Session::put('top_selling_filter', $this->filter);
        
        // Refresh the page to apply the filter to both chart and table
        $this->redirect(request()->header('Referer'));
    }

    protected function getOptions(): array
    {
        return [
            'animation' => [
                'duration' => 1500,
                'easing' => 'easeOutQuart',
            ],
            'responsive' => true,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Order Count',
                    ],
                    'beginAtZero' => true,
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Quantity Sold',
                    ],
                    'beginAtZero' => true,
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
                'x' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'drawOnChartArea' => true,
                    ],
                ],
            ],
        ];
    }
}