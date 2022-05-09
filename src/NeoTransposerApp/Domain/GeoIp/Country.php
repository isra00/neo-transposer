<?php

namespace NeoTransposerApp\Domain\GeoIp;

class Country
{
    protected $isoCode;
    protected $names = [];

    public function __construct($isoCode, $names)
    {
        $this->isoCode = $isoCode;
        $this->names = $names;
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
