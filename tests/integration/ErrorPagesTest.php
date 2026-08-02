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
