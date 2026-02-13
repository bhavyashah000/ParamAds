<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CheckInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if (!File::exists(storage_path('installed'))) {
            // Allow installer routes
            if ($request->is('install*')) {
                return $next($request);
            }
            return redirect('/install');
        }

        // Block installer routes if already installed
        if ($request->is('install*') && !$request->is('install/complete')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
