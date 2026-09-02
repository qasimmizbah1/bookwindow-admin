<?php

// app/Http/Middleware/VendorMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isVendor()) {
            return redirect()->route('filament.vendor.auth.login');
        }

        return $next($request);
    }
}