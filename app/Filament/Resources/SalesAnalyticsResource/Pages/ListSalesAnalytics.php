<?php

namespace App\Filament\Resources\SalesAnalyticsResource\Pages;

use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\SalesAnalyticsResource;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListSalesAnalytics extends ListRecords
{
    protected static string $resource = SalesAnalyticsResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query())
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount (' . currency_symbol() . ')')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()->money()->money(),
                    ]),
                Tables\Columns\TextColumn::make('customername.first_name')
                    ->label('Customer'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('time_period')
                    ->label('Filter by')
                    ->options([
                        'today' => 'Today',
                        'weekly' => 'This Week',
                        'monthly' => 'This Month',
                        'quarterly' => 'This Quarter',
                        'yearly' => 'This Year',
                    ])
                    
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['value'])) {
                            $period = $data['value'];
                            $now = now();
                            
                            return match ($period) {
                                'today' => $query->whereDate('created_at', $now->toDateString()),
                                'weekly' => $query->whereBetween('created_at', [
                                    $now->copy()->startOfWeek(), 
                                    $now->copy()->endOfWeek()
                                ]),
                                'monthly' => $query->whereBetween('created_at', [
                                    $now->copy()->startOfMonth(), 
                                    $now->copy()->endOfMonth()
                                ]),
                                'quarterly' => $query->whereBetween('created_at', [
                                    $now->copy()->startOfQuarter(), 
                                    $now->copy()->endOfQuarter()
                                ]),
                                'yearly' => $query->whereBetween('created_at', [
                                    $now->copy()->startOfYear(), 
                                    $now->copy()->endOfYear()
                                ]),
                                default => $query,
                            };
                        }
                        
                        return $query;
                    }),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->bulkActions([
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn () => 'sales-analytics-' . date('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                    ]),
            ])
            ->headerActions([
            \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()
            ->exports([
            ExcelExport::make()
            ->fromTable()
            ->withFilename(fn () => 'sales-orders-' . date('Y-m-d'))
            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
            ->ignoreFormatting(['status'])
            ->except(['items_count'])
            ])
            ]);

    }

    protected function getHeaderActions(): array
    {
        return [
            // Add any header actions if needed
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesAnalyticsResource\Widgets\SalesOverview::class,
        ];
    }
}