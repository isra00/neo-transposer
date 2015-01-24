<?php

return array(

	'db' => array(
		'host'		=> 'localhost',
		'user'		=> 'root',
		'password'	=> 'root',
		'database'	=> 'transposer',
		'charset'	=> 'utf8'
	),

	// URLs for the Book controller. Every book must have an entry here!
	'book_url' => array(
		1 => '/nyimbo-njia-neokatekumenato',
		2 => '/cantos-camino-neocatecumenal',
	),

	'software_name'		=> 'Neo-Transposer',
	'analytics_id'		=> 'UA-57809429-1',

	'templates_dir'		=> __DIR__ . '/templates',
	'translation_file'	=> __DIR__ . '/translations.php',

	'debug'				=> false,
);