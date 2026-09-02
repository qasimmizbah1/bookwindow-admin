<?php

// app/Filament/Vendor/Resources/OrderResource/Pages/ViewOrder.php

namespace App\Filament\Vendor\Resources\OrderResource\Pages;

use App\Filament\Vendor\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}