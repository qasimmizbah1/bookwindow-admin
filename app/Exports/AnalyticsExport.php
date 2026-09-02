<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AnalyticsExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Best Selling Products' => new BestSellingProductsSheet(),
            'Revenue Distribution' => new RevenueDistributionSheet(),
            'Total Revenue' => new TotalRevenueSheet(),
            'Sales Trends' => new SalesTrendSheet(),
            'Sales by Category' => new SalesByCategorySheet(),
            'Sales by Payment' => new SalesByPaymentSheet(),
        ];
    }
}

class BestSellingProductsSheet implements FromCollection, WithHeadings
{
    public function collection()
    {
        return OrderItem::select('product_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(quantity * price) as total_revenue')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Product Name',
            'Total Quantity Sold',
            'Total Revenue Generated',
        ];
    }
}

// Similar classes for RevenueDistributionSheet, TotalRevenueSheet, etc.
// Would follow the same pattern with different queries