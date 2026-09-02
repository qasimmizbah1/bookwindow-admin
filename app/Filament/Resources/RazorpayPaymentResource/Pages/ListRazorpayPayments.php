<?php

namespace App\Filament\Resources\RazorpayPaymentResource\Pages;

use App\Filament\Resources\RazorpayPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRazorpayPayments extends ListRecords
{
    protected static string $resource = RazorpayPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
