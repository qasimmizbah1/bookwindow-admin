<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Imports\ProductsImport;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;

class ImportProducts extends Page
{
    protected static string $resource = ProductResource::class;

    protected static string $view = 'filament.resources.product-resource.pages.import-products';

    public $file;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Import CSV')
                ->action('import'),
        ];
    }

    public function import()
    {
        $this->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        Excel::import(
            new ProductsImport(),
            $this->file,
            null,
            ExcelFormat::CSV
        );

        Notification::make()
            ->title('Products imported successfully!')
            ->success()
            ->send();
    }
}