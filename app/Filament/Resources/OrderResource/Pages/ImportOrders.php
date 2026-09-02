<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\Page;
use App\Imports\OrdersImport;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class ImportOrders extends Page
{
    protected static string $resource = OrderResource::class;
    
    protected static string $view = 'filament.resources.category-resource.pages.import-categories';
    
}


