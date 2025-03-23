<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use NeoTransposer\Infrastructure\LoginFlow;

class NeedsLoginMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($redirect = LoginFlow::redirectIfUserDoesNotComply($request->route()->getName(), $request->session()->get('user')))
        {
            //Locale necessary for Admin pages, which set no es/sw locale.
            return redirect()->route($redirect, [
                'locale' => ('en' == App::getLocale()) ? 'es' : App::getLocale()
            ]);
        }

        return $next($request);
    }
}
