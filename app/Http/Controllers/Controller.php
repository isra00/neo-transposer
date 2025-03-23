<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use NeoTransposer\Domain\GeoIp\IpToLocaleResolver;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Sets the Locale based on country (geoIP) and, as a fallback, on the
     * request header Accept-Language. Though Accept-Language works reasonably
     * well, the NCW is organized by country, and where a country speaks a
     * language, the catechumens sing in that language (with a few exceptions,
     * like USA). This is why geoip language detection works better.
     *
     * The way getPreferredLanguage() works is by 'expanding' the array with the
     * 'only language' values, e.g. [es_ES, en_US] => [es_ES, es, en_US, en],
     * but if the 'only language' values are already present, leave them where
     * they are. This way, in a request like [es_ES, en_GB, en, es] 'en' will be
     * selected, because es_ES is not found in config('nt.languages')
     * (because in NeoApp locales are defined only by languages). Such a tricky
     * case though, has only occurred in my Chrome/LineageOS for reasons unknown.
     *
     * @param Request $request  The HTTP request.
     */
    protected function setLocaleAutodetect(Request $request, IpToLocaleResolver $ipToLocaleResolver): void
    {
        App::setLocale($request->getPreferredLanguage(array_keys(config('nt.languages'))));
        App::setLocale($ipToLocaleResolver->resolveIpToLocale($request->getClientIp()) ?? App::currentLocale());
    }
}
