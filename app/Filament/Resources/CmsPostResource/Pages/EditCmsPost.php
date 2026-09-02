<?php

namespace App\Filament\Resources\CmsPostResource\Pages;

use App\Filament\Resources\CmsPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCmsPost extends EditRecord
{
    protected static string $resource = CmsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
     protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
