<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Route;

final class ErrorPagesTest extends TestCase
{
    public function test404_renders_error_view_with_error_page_body_class(): void
    {
        $response = $this->get('/definitely-not-a-real-route');

        $response->assertStatus(404);
        $response->assertSee('error-page', false);
        $response->assertSee('Page not found');
        $response->assertSee('The address you have requested does not exist, or has been removed.');
    }

    public function test404_localizes_from_accept_language_header(): void
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
    public function test419_renders_error_view_instead_of_the_framework_page_expired_screen(): void
    {
        config(['app.debug' => false]);

        Route::get('/__test_stale_token', fn () => abort(419));

        $response = $this->get('/__test_stale_token');

        $response->assertStatus(419);
        $response->assertSee('error-page', false);
        $response->assertSee('Your session has expired');
    }

    public function test419_localizes_from_accept_language_header(): void
    {
        config(['app.debug' => false]);

        Route::get('/__test_stale_token_es', fn () => abort(419));

        $response = $this->withHeaders(['Accept-Language' => 'es-ES,es;q=0.9'])
            ->get('/__test_stale_token_es');

        $response->assertStatus(419);
        $response->assertSee('Tu sesión ha expirado');
    }

    public function test500_renders_error_view_when_debug_disabled(): void
    {
        config(['app.debug' => false]);

        Route::get('/__test_boom', fn () => throw new \RuntimeException('boom'));

        $response = $this->get('/__test_boom');

        $response->assertStatus(500);
        $response->assertSee('error-page', false);
        $response->assertSee('Internal error');
    }
}
