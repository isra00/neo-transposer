<?php

namespace NeoTransposer\Domain\GeoIp;

final class IpToLocaleResolver
{
    protected const LOCALES_BY_COUNTRY = [
        'sw' => ['TZ', 'KE'],
        'pt' => ['BR', 'PT', 'AO', 'CV', 'GW', 'MZ', 'ST', 'TL'],
        'es' => [
            'AR', 'BO', 'CL', 'CO', 'CR', 'DO', 'EC', 'SV', 'GT', 'HN', 'MX', 'NI', 'PA', 'PY', 'PE', 'PR', 'ES', 'UY', 'VE', 'CU', 'GQ',
        ],
        'it' => ['IT'],
        'fr' => [
            'FR', 'MC', 'BE', 'CH', 'LU', 'HT', // Europe
            'CD', 'CG', 'SN', 'CI', 'ML', 'BF', 'NE', 'GN', 'BJ', 'TG', 'TD', 'CF', 'CM', 'GA', 'DJ', 'MG', 'RW', 'BI', 'KM', 'SC', 'MU', // Africa
            'GP', 'MQ', 'GF', 'RE', 'YT', 'PF', 'NC', 'WF', 'PM', 'BL', 'MF', //French overseas territories
        ],
    ];

    public function __construct(protected GeoIpResolver $geoIpResolver)
    {
    }

    public function resolveIpToLocale($ip): ?string
    {
        try {
            $record = $this->geoIpResolver->resolve($ip);
        } catch (GeoIpException) {
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
