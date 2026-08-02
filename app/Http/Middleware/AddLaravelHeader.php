<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AddLaravelHeader
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Laravel', 'true');
        return $response;
    }
}
