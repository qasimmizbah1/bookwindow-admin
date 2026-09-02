<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesAnalyticsResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\ListRecords;


class SalesAnalyticsResource extends Resource
{
    protected static ?string $model = Order::class;
    

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Sales Analytics';
    protected static ?string $slug = 'sales-analytics';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $modelLabel = 'Sales Analytics';
    protected static ?int $navigationSort = 1;
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesAnalytics::route('/'),
        ];
    }

   
}