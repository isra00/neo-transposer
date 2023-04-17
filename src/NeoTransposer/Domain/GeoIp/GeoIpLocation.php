<?php

namespace NeoTransposer\Domain\GeoIp;

final class GeoIpLocation
{
    public function __construct(protected Country $country)
    {
    }

    public function country()
    {
        return $this->country;
    }
}