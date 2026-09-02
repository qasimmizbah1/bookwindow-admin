<?php

namespace App\Filament\Resources\SalesByProductsAndCategoryResource\Pages;

use App\Filament\Resources\SalesByProductsAndCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSalesByProductsAndCategories extends ManageRecords
{
    protected static string $resource = SalesByProductsAndCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
