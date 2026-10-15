<?php

namespace App\Filament\Resources\UpsellSettingResource\Pages;

use App\Filament\Resources\UpsellSettingResource;
use App\Models\UpsellSetting;
use Filament\Resources\Pages\ListRecords;

class ListUpsellSettings extends ListRecords
{
    protected static string $resource = UpsellSettingResource::class;

    public function mount(): void
    {
        $record = UpsellSetting::current();

        redirect()->to(UpsellSettingResource::getUrl('edit', ['record' => $record->id]));
    }
}
