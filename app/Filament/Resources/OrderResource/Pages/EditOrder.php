<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('downloadPdf')
                ->label('Invoice')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('orders.pdf', $this->record->id))
                ->openUrlInNewTab(),
        ];
    }
}
