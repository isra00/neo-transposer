<?php

namespace NeoTransposer\Domain\GeoIp;

class GeoIpLocation
{
    public function __construct(protected Country $country)
    {
    }

    public function country()
    {
        return $this->country;
    }
}