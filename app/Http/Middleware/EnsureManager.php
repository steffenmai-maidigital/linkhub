<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureManager
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->canEdit(), 403, 'Nur für Manager oder Administratoren.');
        return $next($request);
    }
}
