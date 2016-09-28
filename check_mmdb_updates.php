<?php

$config_file = 'config.php';

$neoconfig = include $config_file;

//Download last version's MD5
$last_md5_gzip = file_get_contents("http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.md5");
$tmp_file = tempnam(sys_get_temp_dir(), 'MMDB-check-updates');
file_put_contents($tmp_file, $last_md5_gzip);
$last_md5 = file_get_contents($tmp_file);

if ($last_md5 == $neoconfig['mmdb'])
{
	die("Up-to-date\n");
}

echo "Update found! Downloading last version $last_md5...\n";
exec("wget -q http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz -O $last_md5.mmdb.gz");
exec("gzip -df $last_md5.mmdb.gz");

//Update config.php
file_put_contents(
	$config_file, 
	str_replace($neoconfig['mmdb'], $last_md5, file_get_contents($config_file))
);

//Remove old DB
unlink($neoconfig['mmdb'] . '.mmdb');