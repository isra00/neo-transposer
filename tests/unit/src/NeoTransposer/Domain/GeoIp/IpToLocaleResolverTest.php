<?php

namespace NeoTransposer\Tests\Domain\GeoIp;

use NeoTransposer\Domain\GeoIp\Country;
use NeoTransposer\Domain\GeoIp\GeoIpException;
use NeoTransposer\Domain\GeoIp\GeoIpLocation;
use NeoTransposer\Domain\GeoIp\GeoIpResolver;
use NeoTransposer\Domain\GeoIp\IpToLocaleResolver;
use PHPUnit\Framework\TestCase;

final class IpToLocaleResolverTest extends TestCase
{
    /**
     * @dataProvider countryToLocaleProvider
     */
    public function test_maps_country_iso_code_to_locale(string $isoCode, ?string $expectedLocale): void
    {
        $sut = new IpToLocaleResolver($this->fakeResolverReturning($isoCode));

        $this->assertSame($expectedLocale, $sut->resolveIpToLocale('1.2.3.4'));
    }

    public static function countryToLocaleProvider(): array
    {
        return [
            'Tanzania → sw'   => ['TZ', 'sw'],
            'Kenya → sw'      => ['KE', 'sw'],
            'Brazil → pt'     => ['BR', 'pt'],
            'Portugal → pt'   => ['PT', 'pt'],
            'Spain → es'      => ['ES', 'es'],
            'Mexico → es'     => ['MX', 'es'],
            'Italy → it'      => ['IT', 'it'],
            'USA → null'      => ['US', null],
            'UK → null'       => ['GB', null],
            'France → fr'     => ['FR', 'fr'],
            'Germany → null'  => ['DE', null],
            'Belgium → fr'    => ['BE', 'fr'],
            'Switzerland → fr' => ['CH', 'fr'],
            'Senegal → fr'    => ['SN', 'fr'],
            'DR Congo → fr'   => ['CD', 'fr'],
            'Haiti → fr'      => ['HT', 'fr'],
            'Equatorial Guinea → es (not fr)' => ['GQ', 'es'],
            'Canada → null'   => ['CA', null],
        ];
    }

    public function test_returns_null_when_geoip_lookup_fails(): void
    {
        $failing = $this->createMock(GeoIpResolver::class);
        $failing->method('resolve')->willThrowException(new GeoIpException());

        $sut = new IpToLocaleResolver($failing);

        $this->assertNull($sut->resolveIpToLocale('127.0.0.1'));
    }

    private function fakeResolverReturning(string $isoCode): GeoIpResolver
    {
        return new class($isoCode) implements GeoIpResolver {
            public function __construct(private string $isoCode) {}

            public function resolve(string $ip): GeoIpLocation
            {
                return new GeoIpLocation(new Country($this->isoCode, []));
            }
        };
    }
}
