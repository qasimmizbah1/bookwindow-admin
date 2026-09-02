<?php

namespace App\Filament\Resources\SalesForecastResource\Pages;

use App\Filament\Resources\SalesForecastResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSalesForecasts extends ManageRecords
{
    protected static string $resource = SalesForecastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
