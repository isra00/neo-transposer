<?php

namespace Tests\Integration;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

/**
 * CSRF verification is scoped to the admin routes: they write song data behind
 * browser-cached Basic auth, so a forged POST from any page an admin visits used
 * to be enough to corrupt the catalogue.
 *
 * These tests assert the middleware wiring instead of issuing POSTs, because
 * ValidateCsrfToken::handle() short-circuits on runningUnitTests() — an
 * HTTP-level test would return the same status however the middleware is wired.
 */
final class CsrfProtectionTest extends TestCase
{
    private const ADMIN_WRITE_ROUTES = [
        'admin/insert-song',
        'admin/chord-correction',
    ];

    /** Public forms predate the Laravel migration and don't all send a token yet. */
    private const PUBLIC_WRITE_ROUTES = [
        'feedback',
        '{locale}/login',
        '{locale}/wizard/lowest',
        '{locale}/wizard/highest',
    ];

    /** @return list<class-string|string> the full middleware stack, in execution order */
    private function middlewareFor(string $method, string $uri): array
    {
        $route = collect(Route::getRoutes()->getRoutes())->first(
            fn ($route) => $route->uri() === $uri && in_array($method, $route->methods(), true)
        );

        $this->assertNotNull($route, "Route {$method} /{$uri} is not registered");

        return app('router')->gatherRouteMiddleware($route);
    }

    public function testAdminWriteRoutesVerifyCsrfTokens(): void
    {
        foreach (self::ADMIN_WRITE_ROUTES as $uri) {
            $this->assertContains(
                ValidateCsrfToken::class,
                $this->middlewareFor('POST', $uri),
                "POST /{$uri} must verify CSRF tokens"
            );
        }
    }

    public function testAdminWriteRoutesStartSessionBeforeVerifyingCsrfTokens(): void
    {
        foreach (self::ADMIN_WRITE_ROUTES as $uri) {
            $middleware = $this->middlewareFor('POST', $uri);

            $session = array_search(StartSession::class, $middleware, true);
            $csrf = array_search(ValidateCsrfToken::class, $middleware, true);

            $this->assertIsInt($session, "POST /{$uri} must start a session");
            $this->assertLessThan(
                $csrf,
                $session,
                "POST /{$uri} must start the session before verifying the token, or no token can ever match"
            );
        }
    }

    public function testCsrfVerificationIsNotGloballyExempted(): void
    {
        $exempt = (new \ReflectionProperty(ValidateCsrfToken::class, 'neverVerify'))->getValue();

        $this->assertNotContains(
            '*',
            $exempt,
            'A wildcard exemption silently disables CSRF verification on the admin routes too'
        );
    }

    public function testTheLoginPageRendersAUsableToken(): void
    {
        // LoginController::get() flushes the session to force a log-out, which also drops
        // the _token StartSession had just created. It has to be regenerated, or the form
        // renders value="" and login can never be brought under CSRF verification.
        $response = $this->get('/es/login');

        $response->assertOk();
        preg_match('/name="_token" value="([^"]*)"/', $response->getContent(), $match);

        $this->assertNotSame('', $match[1] ?? '', 'the login form must carry a real token');
        $this->assertSame(session()->token(), $match[1], 'the rendered token must be the session token');
    }

    public function testPublicWriteRoutesStayExemptFromCsrfVerification(): void
    {
        foreach (self::PUBLIC_WRITE_ROUTES as $uri) {
            $this->assertNotContains(
                ValidateCsrfToken::class,
                $this->middlewareFor('POST', $uri),
                "POST /{$uri} would start rejecting requests: add @csrf to its form before protecting it"
            );
        }
    }
}
