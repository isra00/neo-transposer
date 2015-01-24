<?php

$config = array();

$config['db'] = array(
	'host' => 'localhost',
	'user' => 'root',
	'password' => 'root',
	'database' => 'transposer',
	'charset' => 'utf8'
);
$config['templates_dir'] = __DIR__ . '/templates';
$config['translation_file'] = __DIR__ . '/translations.php';

$config['software_name'] = 'Neo-Transposer';

$config['analytics_id'] = 'UA-57809429-1';
$config['default_book'] = '1';
$config['default_chord_printer'] = 'English';

$config['book_url'] = array(
	1 => '/nyimbo-njia-neokatekumenato',
	2 => '/cantos-camino-neocatecumenal'
);

$config['debug'] = false;

return $config;