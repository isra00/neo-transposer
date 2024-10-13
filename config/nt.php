<?php

return [

    'db' => [
        'host'     => getenv('NT_DB_HOST'),
        'user'     => getenv('NT_DB_USER'),
        'password' => getenv('NT_DB_PASSWORD'),
        'database' => getenv('NT_DB_DATABASE'),
        'charset'  => 'utf8',
    ],

    'trusted_proxies' => explode(',', getenv('NT_TRUSTED_PROXIES')),

    // URLs for the Book controller. Every book must have an entry here!
    'book_url' => [
        1 => '/nyimbo-njia-neokatekumenato',
        2 => '/cantos-camino-neocatecumenal',
        3 => '/songs-neocatechumenal-way',
        4 => '/cantos-caminho-neocatecumenal',
        5 => '/canti-cammino-neocatecumenale',
    ],

    'languages' => [
        'en' => [
            'name'     => 'English',
            'notation' => 'american'
        ],
        'es' => [
            'name'     => 'Español',
            'notation' => 'latin',
            'file'     => __DIR__ . '/trans/es.php'
        ],
        'sw' => [
            'name'     => 'Kiswahili',
            'notation' => 'american',
            'file'     => __DIR__ . '/trans/sw.php'
        ],
        'pt' => [
            'name'     => 'Português',
            'notation' => 'latin',
            'file'     => __DIR__ . '/trans/pt.php'
        ],
        'it' => [
            'name'     => 'Italiano',
            'notation' => 'latin',
            'file'     => __DIR__ . '/trans/it.php'
        ]
    ],

    'voice_wizard'                           => include 'nt.wizard.php',
    'chord_scores'                           => include 'nt.scores.php',
    'templates_dir'                          => __DIR__ . '/templates',
    'mmdb'                                   => 'GeoLite2-Country.mmdb',
    'test_all_transpositions_expected'       => __DIR__ . '/tests/testAllTranspositions.expected.json',
    'test_all_transpositions_expected_pc'    => __DIR__ . '/tests/testAllTranspositions.expected.PeopleCompatible.json',
    'css_cache'                              => '1314aa7b1c8163def2c403ef1f8dade8',

    'analytics_id'                           => getenv('NT_ANALYTICS_ID'),
    'sitemap_lastmod'                        => '2022-03-08T10:00Z',
    'recaptcha_secret'                       => getenv('NT_RECAPTCHA_SECRET'),

    'software_name'                          => 'Neo-Transposer',
    'seo_title_suffix'                       => 'Transpose chords',

    'admins' => [getenv('NT_ADMIN_USERNAME') => ['ROLE_ADMIN', getenv('NT_ADMIN_PASSWORD')]],

    'people_range'                           => ['B1', 'B2'],

    'debug'                                  => getenv('NT_DEBUG'),
    'profiler'                               => getenv('NT_PROFILER'),

    //Feature flags
    'hide_second_centered_if_not_equivalent' => false,
    'people_compatible'                      => true,
    'detailed_feedback'                      => true,
    'audio'                                  => true,
    'show_manifesto'                         => false,
    'disable_recaptcha'                      => true,
];
