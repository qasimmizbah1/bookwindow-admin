<?php

namespace App\Filament\Resources\AnalyticsResource\Widgets;

use App\Models\OrderItem;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;

class BestSellingProductsChart extends ChartWidget
{
    protected static ?string $heading = 'Best Selling Products';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = OrderItem::select('product_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Best Selling Products',
                    'data' => $data->pluck('total_quantity')->toArray(),
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                ],
            ],
            'labels' => $data->pluck('product_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}