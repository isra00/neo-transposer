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
	
	'languages'	=> array(
		/*'en' => array(
			'name'		=> 'English',
			'notation'	=> 'american'
		),*/
		'es' => array(
			'name'		=> 'Español',
			'notation'	=> 'latin',
			'file'		=> __DIR__ . '/trans/es.php'
		),
		'sw' => array(
			'name'		=> 'Kiswahili',
			'notation'	=> 'american',
			'file'		=> __DIR__ . '/trans/sw.php'
		)
	),

	'software_name'		=> 'Neo-Transposer',
	'voice_wizard'		=> include 'config.wizard.php',
	'templates_dir'		=> __DIR__ . '/templates',

	'analytics_id'		=> 'UA-57809429-1',

	/* MD5 for the MaxMind geo-ip Country database file */
	'mmdb'				=> 'd04df2c3a0f6e8999e36d86987474480',

	'css_cache'			=> '4723e58f10a4d95037a4aab0bc8744ff',

	'sitemap_lastmod'	=> '2015-02-016T10:00Z',

	'seo_title_suffix'	=> 'Transpose chords',

	'debug'				=> true,
);