<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Filament\Actions\Action;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
    

    protected function getHeaderActions(): array
    {
        return [
            // UPDATE BUTTON
            Action::make('update')
                ->label('Update Product')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(function () {
                    $this->save();
                }),

            // VIEW PRODUCT
            Action::make('view')
                ->label('View Product')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(
                    fn () =>
                    rtrim(config('app.frontend_url'), '/') .
                    '/product-detail/' .
                    $this->record->slug
                )
                ->openUrlInNewTab(),

            // DUPLICATE
            Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $duplicate = $this->record->replicate();

                    $duplicate->slug =
                        Str::slug($duplicate->name) . '-' . time();

                    $duplicate->sku = null;
                    $duplicate->updated_by = auth()->id();

                    $duplicate->save();

                    return redirect(
                        ProductResource::getUrl('edit', [
                            'record' => $duplicate,
                        ])
                    );
                }),

            // DELETE
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        if (auth()->user()->isVendor()) {
            $vendor = auth()->user()->vendor;
            if ($vendor) {
                $data['vendor_id'] = $vendor->id;
            }
        }

        return $data;
    }
   
    public function getHeading(): string
{
    return 'Product';
}

public function getBreadcrumbs(): array
{
    return [];
}

}