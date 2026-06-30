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
        $middleware->validateCsrfTokens(except: ['*']);
        $middleware->append(\App\Http\Middleware\AddLaravelHeader::class);
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
