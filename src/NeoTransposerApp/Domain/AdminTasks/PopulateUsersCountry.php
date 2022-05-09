<?php

namespace NeoTransposerApp\Domain\AdminTasks;

use NeoTransposerApp\Domain\GeoIp\GeoIpResolver;
use NeoTransposerApp\Domain\Repository\UserRepository;

class PopulateUsersCountry implements AdminTask
{
    protected $userRepository;
    protected $geoIpResolver;

    public function __construct(UserRepository $userRepository, GeoIpResolver $geoIpResolver)
    {
        $this->userRepository = $userRepository;
        $this->geoIpResolver = $geoIpResolver;
    }

    public function run(): string
    {
		$ipOfUsersWithoutCountry = $this->userRepository->readIpFromUsersWithNullCountry();

		foreach ($ipOfUsersWithoutCountry as $ip)
		{
			$ip = $ip['register_ip'];

			if (!strlen(trim($ip)))
			{
				continue;
			}

			try
			{
                $location = $this->geoIpResolver->resolve($ip);
			}
			catch (\NeoTransposerApp\Domain\GeoIp\GeoIpNotFoundException $e)
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
