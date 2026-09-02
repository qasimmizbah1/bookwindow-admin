<?php

namespace App\Filament\Vendor\Resources\ClearResource\Pages;

use App\Filament\Vendor\Resources\ClearResource;
use Filament\Resources\Pages\Page;

class Dashboard extends Page
{
    protected static string $resource = ClearResource::class;

    protected static string $view = 'filament.vendor.resources.clear-resource.pages.dashboard';
}
