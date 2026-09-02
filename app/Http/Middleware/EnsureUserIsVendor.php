<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVendor
{
    public function handle(Request $request, Closure $next): Response
    {
         if (!auth()->check() || !auth()->user()->isAdmin()) {
        auth()->logout();
        return redirect()->route('admin.login');
    }

    return $next($request);
    }
}