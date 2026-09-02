<?php

namespace App\Filament\Vendor\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.vendor.pages.dashboard';
}
