<?php

$config = array();

$config['db'] = array(
	'host' => 'localhost',
	'user' => 'root',
	'password' => 'root',
	'database' => 'transposer',
	'charset' => 'utf8'
);

$config['software_name'] = 'Neo-Transposer';

$config['analytics_id'] = 'UA-57809429-1';
$config['default_book'] = '1';
$config['default_chord_printer'] = 'English';

$config['templates_dir'] = __DIR__ . '/templates';

return $config;