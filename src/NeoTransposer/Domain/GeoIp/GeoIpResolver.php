<?php

namespace NeoTransposer\Domain\GeoIp;

interface GeoIpResolver
{
    /**
     * @throws GeoIpException
     */
    public function resolve(string $ip): GeoIpLocation;
}
