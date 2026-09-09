<?php

namespace App\Filament\Resources\GlobalSettingResource\Pages;

use App\Filament\Resources\GlobalSettingResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditGlobalSetting extends EditRecord
{
    protected static string $resource = GlobalSettingResource::class;

    protected static ?string $title = 'Global Website Settings';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(function () {
                    $this->save();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Settings Saved')
            ->body('Global website settings have been successfully updated.');
    }
}
