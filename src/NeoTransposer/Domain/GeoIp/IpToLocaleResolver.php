<?php

namespace NeoTransposer\Domain\GeoIp;

class IpToLocaleResolver
{
    protected $geoIpResolver;

    protected const LOCALES_BY_COUNTRY = [
            'sw' => ['TZ', 'KE'],
            'pt' => ['BR', 'PT', 'AO', 'CV', 'GW', 'MZ', 'ST', 'TL'],
            'es' => [
                'AR', 'BO', 'CL', 'CO', 'CR', 'DO', 'EC', 'SV', 'GT', 'HN', 'MX', 'NI', 'PA', 'PY', 'PE', 'PR', 'ES', 'UY', 'VE', 'CU', 'GQ'
            ],
            'it' => ['IT']
        ];

    public function __construct(GeoIpResolver $geoIpResolver)
    {
        $this->geoIpResolver = $geoIpResolver;
    }

    public function resolveIpToLocale($ip): ?string
    {
        try {
            $record = $this->geoIpResolver->resolve($ip);
        } catch (GeoIpException $e) {
            return null;
        }

        foreach (self::LOCALES_BY_COUNTRY as $locale => $countries) {
            if (in_array($record->country()->isoCode(), $countries)) {
                return $locale;
            }
        }

        return null;
    }
}