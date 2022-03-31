<?php

namespace NeoTransposer\Domain\GeoIp;

interface GeoIpResolver
{
    public function resolve(string $ip): GeoIpLocation;
}