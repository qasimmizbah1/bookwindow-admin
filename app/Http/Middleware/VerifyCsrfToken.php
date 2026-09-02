<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Exclude all API routes from CSRF protection
        'api/*',
        'api/add',
        // Or you can specify individual API routes
        // 'api/register',
        // 'api/login',
        // 'api/logout',
        
        // Add any other routes that don't need CSRF protection
        // 'webhook/*',
        // 'payment-callback',
    ];
}