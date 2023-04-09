<?php

namespace NeoTransposer\Domain\GeoIp;

class Country
{
    public function __construct(protected $isoCode, protected $names)
    {
    }

    public function isoCode()
    {
        return $this->isoCode;
    }

    public function names()
    {
        return $this->names;
    }
}