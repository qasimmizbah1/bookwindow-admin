<?php

namespace App\Filament\Resources\ShippingSettingResource\Pages;

use App\Filament\Resources\ShippingSettingResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditShippingSetting extends EditRecord
{
    protected static string $resource = ShippingSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manage_methods')
                ->label('View All Shipping Methods')
                ->color('gray')
                ->url(\App\Filament\Resources\ShippingMethodResource::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()->id]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Shipping & COD Rules Updated')
            ->body('Shipping rates, weight slabs, and COD tiers have been updated successfully.');
    }
}
