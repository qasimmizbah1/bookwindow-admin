<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\Page;
use App\Imports\CategoriesImport;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class ImportCategories extends Page
{
    protected static string $resource = CategoryResource::class;
    
    protected static string $view = 'filament.resources.category-resource.pages.import-categories';
    
}


