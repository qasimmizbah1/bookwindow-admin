<?php

namespace App\Filament\Resources\SalesOrdersResource\Pages;

use App\Filament\Resources\SalesOrdersResource;
use App\Filament\Widgets\SalesByPaymentMethodChart;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesOrders extends ListRecords
{
    protected static string $resource = SalesOrdersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            SalesByPaymentMethodChart::class,
        ];
    }
}