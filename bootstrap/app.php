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

        // CSRF verification is disabled on every route by request. The middleware still runs
        // in the `web` group, but the wildcard exemption makes it a no-op, so every POST —
        // login, /set-user-data, /feedback, and the admin song writes — now accepts requests
        // with no token or a forged one, whatever origin they come from.
        //
        // To restore protection, drop the `except` argument (or this call entirely): the
        // Blade forms still emit @csrf and the AJAX /feedback payload still merges
        // csrf_token(), so nothing else has to change. See tests/integration/CsrfProtectionTest.
        $middleware->validateCsrfTokens(except: ['*']);
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
