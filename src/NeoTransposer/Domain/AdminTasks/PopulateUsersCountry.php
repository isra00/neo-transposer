<?php

namespace NeoTransposer\Domain\AdminTasks;

use NeoTransposer\Domain\GeoIp\GeoIpResolver;
use NeoTransposer\Domain\Repository\UserRepository;

final class PopulateUsersCountry implements AdminTask
{
    public function __construct(
        protected UserRepository $userRepository,
        protected GeoIpResolver $geoIpResolver)
    {
    }

    public function run(): string
    {
		$ipOfUsersWithoutCountry = $this->userRepository->readIpFromUsersWithNullCountry();

		foreach ($ipOfUsersWithoutCountry as $ip)
		{
			$ip = $ip['register_ip'];

			if (trim((string) $ip) === '')
			{
				continue;
			}

			try
			{
                $location = $this->geoIpResolver->resolve($ip);
			}
			catch (\NeoTransposer\Domain\GeoIp\GeoIpNotFoundException)
			{
				continue;
			}

            if ($country = $location->country()->isoCode()) {
                $this->userRepository->saveUserCountryByIp($country, $ip);
            }
		}

		return 'user.country populated for ' . count($ipOfUsersWithoutCountry) . ' IPs';
	}
}