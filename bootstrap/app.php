<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use NeoTransposer\Infrastructure\LoginFlow;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->validateCsrfTokens(except: [
            '*',
        ]);

        /*$middleware->redirectGuestsTo(function(\Illuminate\Http\Request $request) {

            $currentRoute = request()->route()->getName();
            dd($currentRoute);

            //Login page has its own redirection logic.
            if ($currentRoute === 'login')
            {
                return null;
            }

            $currentUser = new \NeoTransposer\Domain\Entity\User('fake', 1, null, 2);

            if (empty($currentUser->id_user))
            {
                return 'login';
            }

            if (!$currentUser->hasRange())
            {
                $exempt = [
                    'user_settings',
                    'user_voice',
                    'set_user_data',
                    'wizard_step1',
                    'wizard_select_standard',
                    'wizard_empiric_lowest',
                    'wizard_empiric_highest'
                ];

                if (!in_array($currentRoute, $exempt))
                {
                    return 'user_voice';
                }
            }

            return null;
        });*/
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
