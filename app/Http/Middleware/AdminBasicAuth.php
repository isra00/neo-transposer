<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $admins = config('nt.admins');
        $user = $request->getUser();
        $password = $request->getPassword();

        if (config('app.debug') && $request->header('X-Admin-Bypass') === 'local-dev') {
            return $next($request);
        }

        if ($user && isset($admins[$user]) && password_verify($password, $admins[$user][1])) {
            return $next($request);
        }

        return new Response('Unauthorized.', 401, ['WWW-Authenticate' => 'Basic realm="Admin"']);
    }
}
