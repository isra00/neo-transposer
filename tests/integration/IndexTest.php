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
    public function testRedirectsToDefaultLocaleLoginWhenNoHeaders(): void
    {
        $this->stubGeoIp(null);

        $this->get('/')->assertRedirect('/en/login');
    }

    public function testAcceptLanguageHeaderDrivesLocale(): void
    {
        $this->stubGeoIp(null);

        $this->withHeaders(['Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8'])
            ->get('/')
            ->assertRedirect('/es/login');
    }

    public function testUnsupportedAcceptLanguageFallsThroughToDefault(): void
    {
        $this->stubGeoIp(null);

        $this->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])
            ->get('/')
            ->assertRedirect('/en/login');
    }

    public function testIpGeoResolutionOverridesAcceptLanguage(): void
    {
        $this->stubGeoIp('TZ');

        $this->withHeaders(['Accept-Language' => 'es-ES,es;q=0.9'])
            ->get('/')
            ->assertRedirect('/sw/login');
    }

    public function testIpGeoResolutionSetsLocaleWhenNoAcceptLanguage(): void
    {
        $this->stubGeoIp('IT');

        $this->get('/')->assertRedirect('/it/login');
    }

    private function stubGeoIp(?string $isoCode): void
    {
        $stub = new class($isoCode) implements GeoIpResolver {
            public function __construct(private ?string $isoCode) {}

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
