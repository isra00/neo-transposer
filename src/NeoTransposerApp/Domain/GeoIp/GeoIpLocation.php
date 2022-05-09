<?php

namespace NeoTransposerApp\Domain\GeoIp;

class GeoIpLocation
{
    protected $country;

    public function __construct(Country $country)
    {
        $this->country = $country;
    }

    public function country()
    {
        return $this->country;
    }
}
