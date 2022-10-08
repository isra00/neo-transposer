<?php

namespace NeoTransposer\Infrastructure;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Exception\GeoIp2Exception;
use MaxMind\Db\Reader\InvalidDatabaseException;
use NeoTransposer\Domain\GeoIp\{Country,
    GeoIpException,
    GeoIpLocation,
    GeoIpNotFoundException,
    GeoIpResolver};

class GeoIpResolverGeoIp2 implements GeoIpResolver
{
    protected $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @throws GeoIpNotFoundException
     * @throws GeoIpException
     */
    public function resolve(string $ip): GeoIpLocation
    {
        try {
            $geoIp2Result = $this->reader->country($ip);
        } catch (AddressNotFoundException $e) {
            throw new GeoIpNotFoundException();
        } catch (InvalidDatabaseException $e) {
            throw new GeoIpException("Error in GeoIp2 database file");
        }

        return new GeoIpLocation(
            new Country($geoIp2Result->country->isoCode, $geoIp2Result->country->names)
        );
    }
}
