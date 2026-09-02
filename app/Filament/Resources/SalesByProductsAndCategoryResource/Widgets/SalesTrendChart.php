<?php

namespace App\Filament\Resources\SalesByProductsAndCategoryResource\Widgets;

use App\Models\Category;
use App\Models\Product;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SalesTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Sales Overview';

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'all';

    public function mount(): void
    {
        $this->filter = Session::get('product_type_filter', 'all');
    }

    protected function getData(): array
    {
        $reportType = $this->filter ?? 'all';

       
        if ($reportType === 'category') {
            $categoryData = $this->getCategorySalesData();
            return [
                'datasets' => [
                    [
                        'label' => 'Category Sales',
                        'data' => $categoryData->pluck('total_sales'),
                        'backgroundColor' => '#4f46e5', // indigo
                    ],
                ],
                'labels' => $categoryData->pluck('label'),
            ];
        } 
        elseif ($reportType === 'product') {
            $productData = $this->getProductSalesData();
            return [
                'datasets' => [
                    [
                        'label' => 'Product Sales',
                        'data' => $productData->pluck('total_sales'),
                        'backgroundColor' => '#10b981', // emerald
                    ],
                ],
                'labels' => $productData->pluck('label'),
            ];
        } 
        else {
            // For 'all' option, show both datasets
            $categoryData = $this->getCategorySalesData();
            $productData = $this->getProductSalesData();
            
            return [
                'datasets' => [
                    [
                        'label' => 'Category Sales',
                        'data' => $categoryData->pluck('total_sales'),
                        'backgroundColor' => '#4f46e5', // indigo
                        'borderColor' => '#3b82f6',
                    ],
                    [
                        'label' => 'Product Sales',
                        'data' => $productData->pluck('total_sales'),
                        'backgroundColor' => '#10b981', // emerald
                        'borderColor' => '#f59e0b',
                    ],
                ],
                'labels' => $categoryData->pluck('label')->merge($productData->pluck('label'))->unique(),
            ];
        }
    }

    protected function getProductSalesData()
    {
        return Product::select('products.name as label', DB::raw('SUM(order_items.price * order_items.quantity) as total_sales'))
            ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('products.name')
            ->orderByDesc('total_sales')
            ->limit(20)
            ->get();
    }

    protected function getCategorySalesData()
    {
        return Category::select('categories.name as label', DB::raw('SUM(order_items.price * order_items.quantity) as total_sales'))
            ->leftJoin('products', 'products.category_id', '=', 'categories.id')
            ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('categories.name')
            ->orderByDesc('total_sales')
            ->limit(20)
            ->get();
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            'all' => 'All',
            'category' => 'By Category',
            'product' => 'By Product',
        ];
    }
     public function updatedFilter(): void
    {
        // Store the filter value in session when it changes
        Session::put('product_type_filter', $this->filter);
        
        // Refresh the page to apply the filter to both chart and table
        $this->redirect(request()->header('Referer'));
    }
}