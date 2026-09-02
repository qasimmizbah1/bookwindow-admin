<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PublicationController;
use App\Http\Controllers\OrderPdfController;

// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

Route::redirect('/', 'admin/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::get('/orders/{order}/pdf', [OrderPdfController::class, 'download'])
    ->name('orders.pdf');
Route::get('/orders/{order}/print', [OrderPdfController::class, 'print'])
    ->name('orders.print');

 // Route::get('/api/categories', [CategoryController::class, 'index']);
 // Route::get('/api/products', [ProductController::class, 'index']);
 // Route::get('/api/products/{slug}', [ProductController::class, 'show']);
 // Route::get('/api/categories/{slug}/products', [ProductController::class, 'productsByCategorySlug']);
 // Route::get('/api/publications', [PublicationController::class, 'index']);

require __DIR__.'/auth.php';
