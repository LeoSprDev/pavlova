<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChangeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->needsPasswordChange() && !$request->routeIs('filament.admin.pages.change-password')) {
            return redirect()->route('filament.admin.pages.change-password');
        }

        return $next($request);
    }
}
