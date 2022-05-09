<?php

namespace NeoTransposerApp\Domain\GeoIp;

interface GeoIpResolver
{
    /**
     * @throws GeoIpException
     */
    public function resolve(string $ip): GeoIpLocation;
}
