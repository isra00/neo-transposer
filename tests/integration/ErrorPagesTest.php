<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Route;

final class ErrorPagesTest extends TestCase
{
    public function test404RendersErrorViewWithErrorPageBodyClass(): void
    {
        $response = $this->get('/definitely-not-a-real-route');

        $response->assertStatus(404);
        $response->assertSee('error-page', false);
        $response->assertSee('Page not found');
        $response->assertSee('The address you have requested does not exist, or has been removed.');
    }

    public function test404LocalizesFromAcceptLanguageHeader(): void
    {
        $response = $this->withHeaders(['Accept-Language' => 'es-ES,es;q=0.9'])
            ->get('/definitely-not-a-real-route');

        $response->assertStatus(404);
        $response->assertSee('Página no encontrada');
        $response->assertSee('La dirección que buscas no existe, o ha sido eliminada.');
    }

    /**
     * Reached for real when a form is submitted with a stale token — most likely on the
     * login page, which is also the landing page and so gets left open in tabs. Aborting
     * stands in for the token mismatch, which ValidateCsrfToken suppresses under tests.
     */
    public function test419RendersErrorViewInsteadOfTheFrameworkPageExpiredScreen(): void
    {
        config(['app.debug' => false]);

        Route::get('/__test_stale_token', fn () => abort(419));

        $response = $this->get('/__test_stale_token');

        $response->assertStatus(419);
        $response->assertSee('error-page', false);
        $response->assertSee('Your session has expired');
    }

    public function test419LocalizesFromAcceptLanguageHeader(): void
    {
        config(['app.debug' => false]);

        Route::get('/__test_stale_token_es', fn () => abort(419));

        $response = $this->withHeaders(['Accept-Language' => 'es-ES,es;q=0.9'])
            ->get('/__test_stale_token_es');

        $response->assertStatus(419);
        $response->assertSee('Tu sesión ha expirado');
    }

    public function test500RendersErrorViewWhenDebugDisabled(): void
    {
        config(['app.debug' => false]);

        Route::get('/__test_boom', fn () => throw new \RuntimeException('boom'));

        $response = $this->get('/__test_boom');

        $response->assertStatus(500);
        $response->assertSee('error-page', false);
        $response->assertSee('Internal error');
    }
}
