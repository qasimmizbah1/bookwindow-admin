<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use App\Observers\OrderObserver;


if (file_exists(app_path('helpers.php'))) {
    require_once app_path('helpers.php');
}

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         Order::observe(OrderObserver::class);

        \Filament\Tables\Table::$defaultCurrency = config('app.currency', 'INR');
        \Filament\Tables\Table::$defaultNumberLocale = config('app.currency_locale', 'en_IN');
        \Illuminate\Support\Number::useCurrency(config('app.currency', 'INR'));

        // Gate Policies for Menu Builder
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Menu::class, \App\Policies\MenuPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\MenuItem::class, \App\Policies\MenuItemPolicy::class);
        if (class_exists(\Biostate\FilamentMenuBuilder\Models\Menu::class)) {
            \Illuminate\Support\Facades\Gate::policy(\Biostate\FilamentMenuBuilder\Models\Menu::class, \App\Policies\MenuPolicy::class);
        }
        if (class_exists(\Biostate\FilamentMenuBuilder\Models\MenuItem::class)) {
            \Illuminate\Support\Facades\Gate::policy(\Biostate\FilamentMenuBuilder\Models\MenuItem::class, \App\Policies\MenuItemPolicy::class);
        }
        
    }
}
