<?php

namespace App\Filament\Resources\SalesByProductsAndCategoryResource\Pages;

use App\Filament\Resources\SalesByProductsAndCategoryResource;
use App\Models\Category;
use App\Models\Product;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\SalesByProductsAndCategoryResource\Widgets\SalesTrendChart;

class ListSalesByProductsAndCategory extends ListRecords
{
    protected static string $resource = SalesByProductsAndCategoryResource::class;

    protected function getTableQuery(): Builder
    {
        $filters = $this->tableFilters;


        
        $reportType = $filters['report_type']['value'] ?? 'category';
        $categoryId = $filters['category_id']['value'] ?? null;
        $productId = $filters['product_id']['value'] ?? null;

        

        if ($reportType === 'category') {
            $query = Category::query()
                ->when($categoryId, fn($q) => $q->where('categories.id', $categoryId))
                ->select([
                    'categories.id',
                    'categories.name',
                    DB::raw('SUM(order_items.price * order_items.quantity) as total_sales'),
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('MAX(orders.created_at) as last_order_date'),
                ])
                ->leftJoin('products', 'products.category_id', '=', 'categories.id')
                ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
                ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
                ->groupBy('categories.id', 'categories.name')
                ->having('total_sales', '>', 0);
        } else {
            $query = Product::query()
                ->when($productId, fn($q) => $q->where('products.id', $productId))
                ->select([
                    'products.id',
                    'products.name',
                    DB::raw('SUM(order_items.price * order_items.quantity) as total_sales'),
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('MAX(orders.created_at) as last_order_date'),
                ])
                ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
                ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
                ->groupBy('products.id', 'products.name')
                ->having('total_sales', '>', 0);
        }

        return $query;
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'total_sales';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function applyFiltersToTableQuery(Builder $query): Builder
    {
        // Don't apply filters here as we're handling them in getTableQuery()
        return $query;
    }
        // protected function getHeaderWidgets(): array
        // {
        // return [
        // SalesTrendChart::class,
        // ];
        // }
}