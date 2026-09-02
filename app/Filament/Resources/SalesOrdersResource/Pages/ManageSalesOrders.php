<?php

namespace App\Filament\Resources\SalesOrdersResource\Pages;

use App\Filament\Resources\SalesOrdersResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSalesOrders extends ManageRecords
{
    protected static string $resource = SalesOrdersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
