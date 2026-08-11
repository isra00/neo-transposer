<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;
use NeoTransposer\Infrastructure\LoginFlow;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php'
    )
    ->withMiddleware(function (Middleware $middleware) {
        $proxies = env('NT_TRUSTED_PROXIES', '');
        $middleware->trustProxies(at: $proxies === '*' ? '*' : array_filter(explode(',', $proxies)));

        // CSRF verification is left in the `web` group on purpose, so a new POST route is
        // protected by default rather than by remembering to opt in. Every write path now
        // sends a token: the Blade forms via @csrf, and the AJAX /feedback call by merging
        // csrf_token() into its hand-built payload. See tests/integration/CsrfProtectionTest.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            app(\App\Support\LocaleAutodetector::class)->detect($request);
            return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
