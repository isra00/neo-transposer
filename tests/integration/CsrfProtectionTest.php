<?php

namespace Tests\Integration;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

/**
 * Every write route verifies CSRF tokens, via the `web` group rather than per-route, so
 * that a new POST route is protected by default. The forged-POST scenario that motivated
 * this is the admin one — those routes write song data behind browser-cached Basic auth —
 * but the public forms are covered too now that they all send a token.
 *
 * The wiring is asserted instead of issuing POSTs, because ValidateCsrfToken::handle()
 * short-circuits on runningUnitTests() — an HTTP-level test would return the same status
 * however the middleware is wired. What a POST cannot prove, the two source-level tests
 * at the bottom cover: a route that verifies tokens is only useful if the page that
 * targets it actually sends one.
 */
final class CsrfProtectionTest extends TestCase
{
    private const WRITE_ROUTES = [
        'admin/insert-song',
        'admin/chord-correction',
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

    public function testWriteRoutesVerifyCsrfTokens(): void
    {
        foreach (self::WRITE_ROUTES as $uri) {
            $this->assertContains(
                ValidateCsrfToken::class,
                $this->middlewareFor('POST', $uri),
                "POST /{$uri} must verify CSRF tokens"
            );
        }
    }

    public function testWriteRoutesStartSessionBeforeVerifyingCsrfTokens(): void
    {
        foreach (self::WRITE_ROUTES as $uri) {
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
            'A wildcard exemption silently disables CSRF verification on every write route'
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

    public function testEveryPostFormInTheViewsCarriesACsrfField(): void
    {
        foreach ($this->bladeFiles() as $path) {
            $source = file_get_contents($path);
            preg_match_all('#<form\b[^>]*\bmethod=(["\'])post\1.*?</form>#is', $source, $forms);

            foreach ($forms[0] as $form) {
                $this->assertStringContainsString(
                    '@csrf',
                    $form,
                    basename($path) . ' has a POST form without @csrf, which the web group will now reject'
                );
            }
        }
    }

    public function testTheFeedbackAjaxCallSendsTheCsrfToken(): void
    {
        // This POST is the one @csrf cannot cover: the payload is a hand-built object, so
        // the hidden field in the surrounding form is never read.
        $source = file_get_contents(resource_path('views/transpose_song.blade.php'));

        $this->assertMatchesRegularExpression(
            '/_token:\s*"\{\{\s*csrf_token\(\)\s*\}\}"/',
            $source,
            'the AJAX feedback payload must carry a token, or every feedback POST 419s'
        );
    }

    /** @return list<string> absolute paths of every Blade template */
    private function bladeFiles(): array
    {
        $files = new \RegexIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))),
            '/\.blade\.php$/'
        );

        $paths = array_keys(iterator_to_array($files));
        sort($paths);

        $this->assertNotEmpty($paths, 'no Blade templates found — the scan would pass vacuously');

        return $paths;
    }
}
