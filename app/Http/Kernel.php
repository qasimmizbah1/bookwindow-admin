<?php
// protected $middleware = [
//     // ...
//     \App\Http\Middleware\Cors::class,
// ];

protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\Cors::class,
    ],

    'api' => [
        
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

protected $routeMiddleware = [
    // ...
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    'vendor' => \App\Http\Middleware\VendorMiddleware::class,
];