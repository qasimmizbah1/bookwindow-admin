<?php

namespace App\Filament\Resources\ShippingMethodResource\Pages;

use App\Filament\Resources\ShippingMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShippingMethods extends ListRecords
{
    protected static string $resource = ShippingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Shipping Method'),
            Actions\Action::make('configure_slabs')
                ->label('Configure Weight & COD Rules')
                ->color('gray')
                ->url(\App\Filament\Resources\ShippingSettingResource::getUrl('edit', ['record' => 1])),
        ];
    }
}
