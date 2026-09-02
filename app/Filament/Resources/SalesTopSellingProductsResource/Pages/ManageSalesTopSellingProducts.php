<?php

namespace App\Filament\Resources\SalesTopSellingProductsResource\Pages;

use App\Filament\Resources\SalesTopSellingProductsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Resources\SalesTopSellingProductsResource\Widgets\TopSellingProductsChart;


class ManageSalesTopSellingProducts extends ManageRecords
{
    protected static string $resource = SalesTopSellingProductsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
     protected function getHeaderWidgets(): array
    {
        return [
            TopSellingProductsChart::class,
        ];
    }
}
