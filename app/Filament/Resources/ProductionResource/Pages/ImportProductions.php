<?php

namespace App\Filament\Resources\ProductionResource\Pages;

use App\Filament\Resources\ProductionResource;
use Filament\Resources\Pages\Page;
use App\Imports\ProductionImport;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class ImportProductions extends Page
{
    protected static string $resource = ProductionResource::class;
    
    protected static string $view = 'filament.resources.category-resource.pages.import-productions';
    
}


