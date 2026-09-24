<?php

namespace App\Filament\Resources\ShippingSettingResource\Pages;

use App\Filament\Resources\ShippingSettingResource;
use App\Models\ShippingSetting;
use Filament\Resources\Pages\ListRecords;

class ListShippingSettings extends ListRecords
{
    protected static string $resource = ShippingSettingResource::class;

    public function mount(): void
    {
        $record = ShippingSetting::current();

        redirect()->to(ShippingSettingResource::getUrl('edit', ['record' => $record->id]));
    }
}
