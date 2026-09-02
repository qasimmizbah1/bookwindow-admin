<?php

namespace App\Filament\Resources\SalesAnalyticsResource\Pages;

use App\Filament\Resources\SalesAnalyticsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSalesAnalytics extends ManageRecords
{
    protected static string $resource = SalesAnalyticsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Remove or keep the create action based on your needs
            // Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesAnalyticsResource\Widgets\SalesChart::class,
        ];
    }
}