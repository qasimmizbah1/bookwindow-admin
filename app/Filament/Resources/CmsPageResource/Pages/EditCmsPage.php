<?php

namespace App\Filament\Resources\CmsPageResource\Pages;

use App\Filament\Resources\CmsPageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;

class EditCmsPage extends EditRecord
{
    protected static string $resource = CmsPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
             Action::make('update')
                ->label('Update Category')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(function () {
                    $this->save();
                }),

            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
