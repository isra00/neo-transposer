<?php

namespace Tests\Integration;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

/**
 * CSRF verification is currently DISABLED on every route: bootstrap/app.php registers a
 * wildcard exemption, so ValidateCsrfToken runs but short-circuits before comparing
 * tokens. No write route is protected — including the admin ones, which write song data
 * behind browser-cached Basic auth, the scenario that originally motivated the checks.
 *
 * These tests no longer assert protection, because there is none. What they still pin is
 * everything needed to restore it by deleting the `except` argument in bootstrap/app.php:
 * the middleware is still wired into the `web` group ahead of the session, and every page
 * that targets a write route still emits a token. If one of those drifts, re-enabling CSRF
 * would silently break that form instead of protecting it.
 *
 * The wiring is asserted instead of issuing POSTs, because ValidateCsrfToken::handle()
 * short-circuits on runningUnitTests() — an HTTP-level test would return the same status
 * however the middleware is wired.
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

    public function test_write_routes_still_have_the_csrf_middleware_wired(): void
    {
        // Present but exempted, so this asserts reachability rather than enforcement:
        // removing the middleware would mean re-enabling CSRF takes more than deleting
        // the `except` argument in bootstrap/app.php.
        foreach (self::WRITE_ROUTES as $uri) {
            $this->assertContains(
                ValidateCsrfToken::class,
                $this->middlewareFor('POST', $uri),
                "POST /{$uri} must keep the CSRF middleware in its stack"
            );
        }
    }

    public function test_write_routes_start_session_before_verifying_csrf_tokens(): void
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

    public function test_csrf_verification_is_globally_exempted(): void
    {
        // Inverted on purpose: CSRF was disabled by request, and this pins that it is a
        // deliberate wildcard exemption rather than protection lost to an accident
        // elsewhere. Flip back to assertNotContains when CSRF is re-enabled.
        $exempt = (new \ReflectionProperty(ValidateCsrfToken::class, 'neverVerify'))->getValue();

        $this->assertContains(
            '*',
            $exempt,
            'CSRF is meant to be disabled globally; bootstrap/app.php should exempt "*"'
        );
    }

    public function test_the_login_page_renders_a_usable_token(): void
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

    public function test_every_post_form_in_the_views_carries_a_csrf_field(): void
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

    public function test_the_feedback_ajax_call_sends_the_csrf_token(): void
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
