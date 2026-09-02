<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesByProductsAndCategoryResource\Pages;
use App\Models\Category;
use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Support\Facades\Session;

class SalesByProductsAndCategoryResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-s-shopping-cart';
    protected static ?string $navigationLabel = 'Sales by products and category';
    protected static ?string $modelLabel = 'Sales Report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $model = \App\Models\SalesByProductsAndCategory::class;
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Item Name')
                    ->wrap()
                    ->width('400px'),
                TextColumn::make('total_sales')
                    ->label('Total Sales (' . currency_symbol() . ')')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Sales')
                            ->money('inr'),
                    ]),
                TextColumn::make('total_quantity')
                    ->label('Total Quantity')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Quantity'),
                    ]),
                TextColumn::make('last_order_date')
                    ->label('Last Order')
                    ->date(),
            ])
            ->filters([
                SelectFilter::make('report_type')
                    ->options([
                        'category' => 'By Category',
                        'product' => 'By Product',
                    ])
                    ->label('Report Type')
                    ->default('product'),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
           // ->modifyQueryUsing(function (Builder $query) {
           //      // Apply the filter from session
           //      $filter = Session::get('product_type_filter', 'all');
                
           //      if ($filter === 'category') {
           //          return Category::query()
           //              ->select([
           //                  'categories.id',
           //                  'categories.name',
           //                  DB::raw('SUM(order_items.price * order_items.quantity) as total_sales'),
           //                  DB::raw('SUM(order_items.quantity) as total_quantity'),
           //                  DB::raw('MAX(orders.created_at) as last_order_date'),
           //              ])
           //              ->leftJoin('products', 'products.category_id', '=', 'categories.id')
           //              ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
           //              ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
           //              ->groupBy('categories.id', 'categories.name')
           //              ->having('total_sales', '>', 0);
           //      } else {
           //          return Product::query()
           //              ->select([
           //                  'products.id',
           //                  'products.name',
           //                  DB::raw('SUM(order_items.price * order_items.quantity) as total_sales'),
           //                  DB::raw('SUM(order_items.quantity) as total_quantity'),
           //                  DB::raw('MAX(orders.created_at) as last_order_date'),
           //              ])
           //              ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
           //              ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
           //              ->groupBy('products.id', 'products.name')
           //              ->having('total_sales', '>', 0);
           //      }
           //  })
            ->bulkActions([
                ExportBulkAction::make()->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename(fn () => 'sales-report-' . date('Y-m-d'))
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                ]),
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn () => 'sales-report-' . date('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                    ])
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Get the active filters
        $livewire = filament()->getCurrentPanel()->getLivewire();
        $reportType = $livewire->tableFilters['report_type']['value'] ?? 'product';

        if ($reportType === 'category') {
            return Category::query()
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
            return Product::query()
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
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesByProductsAndCategory::route('/'),
        ];
    }
}