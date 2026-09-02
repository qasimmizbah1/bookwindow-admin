<?php

namespace App\Filament\Resources\CmsCategoryResource\Pages;

use App\Filament\Resources\CmsCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsCategories extends ListRecords
{
    protected static string $resource = CmsCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
