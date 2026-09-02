<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesTopSellingProductsResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Widgets\TopSellingProductsChart;
use Illuminate\Support\Facades\Session;

class SalesTopSellingProductsResource extends Resource
{
    protected static ?string $model = OrderItem::class;
    protected static ?string $navigationIcon = 'heroicon-s-arrow-up-right';
    protected static ?string $navigationLabel = 'Top Selling Products';
    protected static ?string $slug = 'top-selling-products';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $modelLabel = 'Best Selling';
    protected static ?int $navigationSort = 4;
     public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Form schema if needed
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $filter = Session::get('top_selling_filter', 'year');

                $query = $query->select([
                        'products.id as id', // This is crucial for Filament's record identification
                        'products.id as product_id',
                        'products.name as product_name',
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(order_items.quantity) as total_quantity'),
                        DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue'),
                    ])
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->groupBy('products.id', 'products.name')
                    ->orderByDesc('total_orders');
                    
                    return match ($filter) {
                    'today' => $query->whereDate('orders.created_at', today()),
                    'week' => $query->whereBetween('orders.created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                    'month' => $query->whereBetween('orders.created_at', [now()->startOfMonth(), now()->endOfMonth()]),
                    'quarter' => $query->whereBetween('orders.created_at', [now()->startOfQuarter(), now()->endOfQuarter()]),
                    'year' => $query->whereBetween('orders.created_at', [now()->startOfYear(), now()->endOfYear()]),
                    default => $query,
                };

            })
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                Tables\Columns\TextColumn::make('product_id')
                    ->label('Product ID')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->width("350"),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->width("350"),
                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Total Orders')
                    ->sortable()
                    ->summarize([
                            Tables\Columns\Summarizers\Sum::make()
                                ->label('Total Orders'),
                    ]),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Total Quantity')
                    ->sortable()
                    ->summarize([
                            Tables\Columns\Summarizers\Sum::make()
                                ->label('Total Quantity'),
                    ]),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Total Revenue (' . currency_symbol() . ')')
                    
                    ->sortable()
                    ->summarize([
                            Tables\Columns\Summarizers\Sum::make()
                                ->label('Total Revenue')
                                ->money('INR'),
                    ]),
            ])
            // ->filters([
            //     Tables\Filters\SelectFilter::make('time_period')
            //         ->options([
            //             'today' => 'Today',
            //             'week' => 'This Week',
            //             'month' => 'This Month',
            //             'quarter' => 'This Quarter',
            //             'year' => 'This Year',
            //         ])
            //         ->default('year')
            //         ->query(function (Builder $query, array $data) {
            //             $value = $data['value'] ?? 'week';
                        
            //             $query->when($value, function (Builder $query, string $value) {
            //                 return match ($value) {
            //                     'today' => $query->whereDate('orders.created_at', today()),
            //                     'yesterday' => $query->whereDate('orders.created_at', today()->subDay()),
            //                     'week' => $query->whereBetween('orders.created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            //                     'last_week' => $query->whereBetween('orders.created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
            //                     'month' => $query->whereBetween('orders.created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            //                     'last_month' => $query->whereBetween('orders.created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()]),
            //                     'quarter' => $query->whereBetween('orders.created_at', [now()->startOfQuarter(), now()->endOfQuarter()]),
            //                     'year' => $query->whereBetween('orders.created_at', [now()->startOfYear(), now()->endOfYear()]),
            //                     'last_year' => $query->whereBetween('orders.created_at', [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()]),
            //                     default => $query,
            //                 };
            //             });
            //         }),
            // ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                // Actions if needed
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->label('Export Selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn () => 'top-selling-products-' . date('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->except(['id', 'product_id']) // Exclude these columns from export
                    ])
            ])
             ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()
                    ->exports([
                       ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn () => 'top-selling-products-' . date('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                    ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSalesTopSellingProducts::route('/'),
        ];
    }

    public static function getRecordKey(Model $record): string
    {
        return (string) $record->id;
    }
}