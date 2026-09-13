<?php

namespace NeoTransposer\Infrastructure;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use NeoTransposer\Domain\GeoIp\Country;
use NeoTransposer\Domain\GeoIp\GeoIpException;
use NeoTransposer\Domain\GeoIp\GeoIpLocation;
use NeoTransposer\Domain\GeoIp\GeoIpNotFoundException;
use NeoTransposer\Domain\GeoIp\GeoIpResolver;

final class GeoIpResolverGeoIp2 implements GeoIpResolver
{
    public function __construct(protected Reader $reader)
    {
    }

    /**
     * @throws GeoIpNotFoundException
     * @throws GeoIpException
     */
    public function resolve(string $ip): GeoIpLocation
    {
        try {
            $geoIp2Result = $this->reader->country($ip);
        } catch (AddressNotFoundException) {
            throw new GeoIpNotFoundException();
        } catch (InvalidDatabaseException) {
            throw new GeoIpException('Error in GeoIp2 database file');
        }

        return new GeoIpLocation(
            new Country($geoIp2Result->country->isoCode, $geoIp2Result->country->names)
        );
    }
}
