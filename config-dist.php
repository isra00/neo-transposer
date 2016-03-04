<?php

return array(

	'db' => array(
		'host'		=> 'localhost',
		'user'		=> 'your mysql user',
		'password'	=> 'your mysql password',
		'database'	=> 'your mysql database',
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
	'test_all_transpositions_expected' => __DIR__ . '/tests/testAllTranspositions.expected.json',
	'pdf_reports_dir'	=> 'cache-pdf', /* @todo Remove */

	'analytics_id'		=> 'UA-57809429-1',

	/* MD5 for the MaxMind geo-ip Country database file */
	'mmdb'				=> '457d467caa9f67e8fda0865bf9890801',

	'css_cache'			=> '4723e58f10a4d95037a4aab0bc8744ff',

	'sitemap_lastmod'	=> '2015-12-31T10:00Z',

	'seo_title_suffix'	=> 'Transpose chords',

	'debug'				=> true,
);