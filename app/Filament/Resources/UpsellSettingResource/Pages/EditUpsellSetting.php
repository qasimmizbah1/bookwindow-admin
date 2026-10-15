<?php

namespace App\Filament\Resources\UpsellSettingResource\Pages;

use App\Filament\Resources\UpsellSettingResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUpsellSetting extends EditRecord
{
    protected static string $resource = UpsellSettingResource::class;

    protected static ?string $title = 'Checkout Upsell & Recommendation Settings';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()->id]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Upsell Settings Saved')
            ->body('Checkout upsell and recommendation settings updated successfully.');
    }
}
