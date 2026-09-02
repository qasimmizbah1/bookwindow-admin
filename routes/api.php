<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PublicationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductSearchController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CartController;
use Illuminate\Session\Middleware\StartSession;
use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\CmsPageController;
use App\Http\Controllers\Api\CmsPostController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\HomePageController;
use App\Http\Controllers\Api\ContactFormController;
use App\Http\Controllers\Api\ContactPageController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\CustomerForgotPasswordController;
use App\Http\Controllers\Api\CustomerResetPasswordController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\SitemapController;

//Basic API

Route::get('/category', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/category/{slug}', [ProductController::class, 'productsByCategorySlug']);
Route::get('/publications', [PublicationController::class, 'index']);
Route::get('/publication/{slug}', [PublicationController::class, 'productsBySlug']);
Route::get('/search', [ProductSearchController::class, 'search']);
Route::get('menus/{menuName}', [MenuController::class, 'getMenuItems']);


//Debug for session
Route::get('/debug', [DebugController::class, 'index'])->middleware('web');

//Forgot Password
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);

//Cart & Checkout

Route::prefix('cart')->group(function () {

Route::middleware(['api', \Illuminate\Session\Middleware\StartSession::class])->get('/viewcart', [CartController::class, 'viewcart']);
Route::middleware(['api', \Illuminate\Session\Middleware\StartSession::class])->get('/empty', [CartController::class, 'empty']);
Route::middleware(['api', \Illuminate\Session\Middleware\StartSession::class])->post('add', [CartController::class, 'add']);
Route::middleware(['api', \Illuminate\Session\Middleware\StartSession::class])->post('cartupdate', [CartController::class, 'cartupdate']);
Route::middleware(['api', \Illuminate\Session\Middleware\StartSession::class])->post('remove', [CartController::class, 'remove']);
Route::middleware(['api', \Illuminate\Session\Middleware\StartSession::class])->post('clear', [CartController::class, 'clear']);
Route::post('/checkout', [CheckoutController::class, 'process']);
Route::post('/razorpay/callback', [CheckoutController::class, 'razorpayCallback'])->name('razorpay.callback');
Route::post('/razorpay/cancel', [CheckoutController::class, 'handlePaymentCancel'])->name('razorpay.cancel');;
Route::post('/coupon/{coupon_code}', [CheckoutController::class, 'showCouponCode']);

});

Route::get('orders/{order_number}', [OrderApiController::class, 'show']);

Route::prefix('my-account')->group(function () {

Route::middleware('auth:customer')->group(function () { 
Route::post('/passwordchange', [AuthController::class, 'passwordchange']);

Route::get('user_order/{user_id}', [OrderApiController::class, 'userOrders']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/user', [AuthController::class, 'user']);
Route::post('/refresh', [AuthController::class, 'refresh']);
Route::get('/view-address/{user_id}', [AuthController::class, 'viewAddress']);
Route::get('/checkauth', [AuthController::class, 'checkAuth']);

Route::post('/add-wishlist',[WishlistController::class,'store']);
Route::get('/view-wishlist',[WishlistController::class,'index']);
Route::get('/view-wishlistid',[WishlistController::class,'wishlistid']);
Route::delete('/delete-wishlist',[WishlistController::class,'destroy']);




});


});

//Auth user (Login & Logout)
Route::prefix('v1')->group(function () {
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/checkuser', [AuthController::class, 'checkuser']);
Route::post('/updateuser', [AuthController::class, 'updateuser']);


});


//All Page 
Route::apiResource('blog', CmsPageController::class);
//Pages By Slug
Route::get('cms-pages/{slug}', [CmsPageController::class, 'showBySlug']);


//All Post 
Route::apiResource('blog', CmsPostController::class);
//Post By Slug
Route::get('blog/{slug}', [CmsPostController::class, 'showBySlug']);

//News
Route::apiResource('news', NewsController::class);
Route::get('news/{slug}', [NewsController::class, 'newsBySlug']);



//Home Page
Route::get('/home-page', [HomePageController::class, 'index']);
Route::get('/contact-page', [ContactPageController::class, 'index']);

//Conatct Form
Route::post('/contact-form', [ContactFormController::class, 'send']);

Route::post('/tutor-form', [ContactFormController::class, 'submitTutorForm']);
Route::post('/vendor-form', [ContactFormController::class, 'submitVendorForm']);
Route::post('/product-request', [ContactFormController::class, 'submitProductRequest']);


Route::post('/customer/forgot-password', [CustomerForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/customer/reset-password', [CustomerResetPasswordController::class, 'reset']);

Route::get('/state-of-india', [StateController::class, 'index']);
//Route::post('/razorpay/callback', [CheckoutController::class, 'razorpayCallback']);

Route::post('/newsletter', [NewsletterController::class, 'subscribe']);


Route::get('/sitemap-data', [SitemapController::class, 'index']);