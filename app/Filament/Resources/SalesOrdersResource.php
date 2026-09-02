<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrdersResource\Pages;
use App\Filament\Resources\SalesOrdersResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\SalesOrdersResource\Widgets\SalesByPaymentMethodChart;
use Illuminate\Support\Facades\Session;

class SalesOrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Sales Order By Payment';

    protected static ?string $slug = 'sales-order';
    
    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $modelLabel = 'Sales by Payment Method';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->getStateUsing(function ($record) {
                        return $record->customername ? $record->customername->first_name : 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('customername', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total (' . currency_symbol() . ')')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()->money(),
                    ]),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cod' => 'COD',
                        'razorpay' => 'Razor Pay',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cod' => 'warning',
                        'razorpay' => 'info',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make(),
                    ]),
            ])
           ->modifyQueryUsing(function (Builder $query) {
                // Apply the filter from session
                $filter = Session::get('payment_method_filter', 'all');
                if ($filter !== 'all') {
                    $query->where('payment_method', $filter);
                }
                return $query;
            })
            ->actions([])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn () => 'sales-orders-' . date('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                    ])
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn () => 'sales-orders-' . date('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                    ])
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrders::route('/'),
        ];
    }
    public static function getWidgets(): array
    {
        return [
            SalesByPaymentMethodChart::class,
        ];
    }
}