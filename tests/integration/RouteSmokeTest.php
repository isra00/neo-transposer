<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Walks every registered route with junk parameters and asserts none of them 5xx.
 */
final class RouteSmokeTest extends TestCase
{
    public function test_no_route_returns_a_server_error_on_junk_input(): void
    {
        $failures = [];
        $checked = [];

        foreach (Route::getRoutes() as $route) {
            // Skip other packages' routes (debugbar & co) and the CSS one, which recompiles
            // into public/static and rewrites config/nt.php when hit.
            if (!in_array('web', $route->gatherMiddleware(), true)
                || str_starts_with($route->uri(), 'static/compiled-')) {
                continue;
            }

            // Junk parameters, except the locale, which must be valid for the request to
            // reach the controller instead of being rejected by the router.
            $uri = '/' . ltrim(preg_replace(
                ['/\{locale\}/', '/\{\w+\??\}/'],
                ['en', '999999'],
                $route->uri()
            ), '/');

            foreach (array_intersect($route->methods(), ['GET', 'POST']) as $method) {
                // Plain and JSON requests take different branches in the controllers.
                foreach (['text/html', 'application/json'] as $accept) {
                    $status = $this->withHeaders(['Accept' => $accept])
                        ->call($method, $uri)
                        ->getStatusCode();

                    $checked[] = "$method $uri";

                    if ($status >= 500) {
                        $failures[] = "$method $uri (Accept: $accept) → $status";
                    }
                }
            }
        }

        $this->assertSame([], $failures);

        // Guards against the loop silently going empty if route registration changes.
        $this->assertContains('GET /transpose/999999', $checked);
        $this->assertContains('POST /en/login', $checked);
    }
}
