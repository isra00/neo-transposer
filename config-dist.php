<?php

return [

	'db' => [
		'host'		=> 'localhost',
		'user'		=> 'your mysql user',
		'password'	=> 'your mysql password',
		'database'	=> 'your mysql database',
		'charset'	=> 'utf8'
	],

	// URLs for the Book controller. Every book must have an entry here!
	'book_url' => [
		1 => '/nyimbo-njia-neokatekumenato',
		2 => '/cantos-camino-neocatecumenal',
	],
	
	'languages'	=> [
		/*'en' => [
			'name'		=> 'English',
			'notation'	=> 'american'
		],*/
		'es' => [
			'name'		=> 'Español',
			'notation'	=> 'latin',
			'file'		=> __DIR__ . '/trans/es.php'
		],
		'sw' => [
			'name'		=> 'Kiswahili',
			'notation'	=> 'american',
			'file'		=> __DIR__ . '/trans/sw.php'
		]
	],

	'voice_wizard'		=> include 'config.wizard.php',
	'chord_scores'		=> include 'config.scores.php',
	'templates_dir'		=> __DIR__ . '/templates',
	'mmdb'				=> 'GeoLite2-Country.mmdb',
	'test_all_transpositions_expected' => __DIR__ . '/tests/testAllTranspositions.expected.json',
	'test_all_transpositions_expected_pc' => __DIR__ . '/tests/testAllTranspositions.expected.PeopleCompatible.json',
	'css_cache'			=> '4723e58f10a4d95037a4aab0bc8744ff',

	'analytics_id'		=> 'UA-57809429-1',
	'sitemap_lastmod'	=> '2016-03-12T10:00Z',

	'software_name'		=> 'Neo-Transposer',
	'seo_title_suffix'	=> 'Transpose chords',

	'admins'			=> ['your admin user' => ['ROLE_ADMIN', 'encrypted admin password']],

	'people_range'		=> ['B1', 'B2'],

	'debug'				=> true,
	'profiler'			=> true,

	//Feature flags
	'hide_second_centered_if_not_equivalent' => false,
	'people_compatible' => false,

	'people_compatible_users' => [5],
];
