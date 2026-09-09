<?php

namespace App\Filament\Resources\GlobalSettingResource\Pages;

use App\Filament\Resources\GlobalSettingResource;
use App\Models\GlobalSetting;
use Filament\Resources\Pages\ListRecords;

class ListGlobalSettings extends ListRecords
{
    protected static string $resource = GlobalSettingResource::class;

    public function mount(): void
    {
        $record = GlobalSetting::current();

        redirect()->to(GlobalSettingResource::getUrl('edit', ['record' => $record->id]));
    }
}
