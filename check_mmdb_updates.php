<?php

$config_file = 'config.php';

$neoconfig = include $config_file;

$last_md5 = file_get_contents("http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.md5");

if ($last_md5 == md5(file_get_contents('./' . $neoconfig['mmdb'])))
{
	die("Up-to-date\n");
}

echo "Update found! Downloading last version $last_md5...\n";
exec("wget -q http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz -O $last_md5.mmdb.gz");
exec("gzip -df $last_md5.mmdb.gz");

rename("$last_md5.mmdb", $neoconfig['mmdb']);
echo $neoconfig['mmdb'] . " updated with version $last_md5\n";
