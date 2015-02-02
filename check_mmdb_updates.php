<?php

$neoconfig = include 'config.php';

$last_md5_gzip = file_get_contents("http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.md5");
$tmp_file = tempnam(sys_get_temp_dir(), 'MMDB-check-updates');

file_put_contents($tmp_file, $last_md5_gzip);
$last_md5 = file_get_contents($tmp_file);

echo ($last_md5 != $neoconfig['mmdb'])
	? "There are updates available for MixMind GeoLite2!"
	: "Up-to-date";

die("\n");