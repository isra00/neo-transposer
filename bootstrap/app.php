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

        // CSRF is not verified app-wide: the public forms (login, wizard, feedback) predate
        // the Laravel migration and don't all send a token yet. Instead of exempting every
        // URI from the global middleware, we drop it from the `web` group and attach it
        // explicitly to the admin routes in routes/web.php, which are the ones worth
        // protecting (they sit behind browser-cached Basic auth and write song data).
        $middleware->web(remove: [\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
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
