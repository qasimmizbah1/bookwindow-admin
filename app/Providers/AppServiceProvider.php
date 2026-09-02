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
        
    }
}
