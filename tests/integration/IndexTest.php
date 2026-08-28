<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase;
use NeoTransposer\Domain\GeoIp\Country;
use NeoTransposer\Domain\GeoIp\GeoIpException;
use NeoTransposer\Domain\GeoIp\GeoIpLocation;
use NeoTransposer\Domain\GeoIp\GeoIpResolver;
use NeoTransposer\Domain\GeoIp\IpToLocaleResolver;

final class IndexTest extends TestCase
{
    public function test_redirects_to_default_locale_login_when_no_headers(): void
    {
        $this->stubGeoIp(null);

        $this->get('/')->assertRedirect('/en/login');
    }

    public function test_accept_language_header_drives_locale(): void
    {
        $this->stubGeoIp(null);

        $this->withHeaders(['Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8'])
            ->get('/')
            ->assertRedirect('/es/login');
    }

    public function test_unsupported_accept_language_falls_through_to_default(): void
    {
        $this->stubGeoIp(null);

        $this->withHeaders(['Accept-Language' => 'fr-FR,fr;q=0.9'])
            ->get('/')
            ->assertRedirect('/en/login');
    }

    public function test_ip_geo_resolution_overrides_accept_language(): void
    {
        $this->stubGeoIp('TZ');

        $this->withHeaders(['Accept-Language' => 'es-ES,es;q=0.9'])
            ->get('/')
            ->assertRedirect('/sw/login');
    }

    public function test_ip_geo_resolution_sets_locale_when_no_accept_language(): void
    {
        $this->stubGeoIp('IT');

        $this->get('/')->assertRedirect('/it/login');
    }

    private function stubGeoIp(?string $isoCode): void
    {
        $stub = new class($isoCode) implements GeoIpResolver
        {
            public function __construct(private ?string $isoCode)
            {
            }

            public function resolve(string $ip): GeoIpLocation
            {
                if ($this->isoCode === null) {
                    throw new GeoIpException();
                }

                return new GeoIpLocation(new Country($this->isoCode, []));
            }
        };

        $this->app->instance(IpToLocaleResolver::class, new IpToLocaleResolver($stub));
    }
}
