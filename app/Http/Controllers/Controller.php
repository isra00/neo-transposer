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
     */
    protected function setLocaleAutodetect(Request $request, IpToLocaleResolver $ipToLocaleResolver): void
    {
        $supportedLocales = array_keys(config('nt.languages'));

        // We don't use getPreferredLanguage() because it does an exact intersect
        // first, so a lower-priority exact match (e.g. "en") beats a higher-priority
        // regional variant (e.g. "es_ES" → "es"). Instead, iterate in priority order
        // and match on the base language code.
        foreach ($request->getLanguages() as $lang) {
            $base = substr($lang, 0, 2);
            if (in_array($base, $supportedLocales, true)) {
                App::setLocale($base);
                break;
            }
        }

        App::setLocale($ipToLocaleResolver->resolveIpToLocale($request->getClientIp()) ?? App::currentLocale());
    }
}
