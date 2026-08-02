<?php

namespace App\Http\Controllers;

use App\Support\LocaleAutodetector;
use Illuminate\Http\Request;
use NeoTransposer\Domain\GeoIp\IpToLocaleResolver;

abstract class Controller
{
    protected function setLocaleAutodetect(Request $request, IpToLocaleResolver $ipToLocaleResolver): void
    {
        (new LocaleAutodetector($ipToLocaleResolver))->detect($request);
    }
}
