<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a response out of any cache, including the browser's on-disk one.
 *
 * Laravel's default `no-cache, private` only forces revalidation before reuse — it
 * still allows the response to be *written to disk*. That is not good enough for the
 * admin pages, which render user data and CSRF tokens that would then outlive the
 * session on the admin's machine. `no-store` is the directive that forbids storage.
 */
final class PreventResponseCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, private, must-revalidate');

        // mod_expires is enabled with `ExpiresDefault "access plus 1 seconds"` in
        // public/.htaccess, which appends a max-age to every HTML response. It leaves an
        // Expires header alone if one is already present, so setting a past date here
        // both stops that and covers any cache that honours Expires over Cache-Control.
        $response->headers->set('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT');

        return $response;
    }
}
