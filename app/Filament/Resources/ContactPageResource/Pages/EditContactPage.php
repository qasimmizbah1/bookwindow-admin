<?php

namespace App\Filament\Resources\ContactPageResource\Pages;

use App\Filament\Resources\ContactPageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactPage extends EditRecord
{
    protected static string $resource = ContactPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
     protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
