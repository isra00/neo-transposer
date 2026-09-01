<?php

return [

    'languages' => [
        'en' => [
            'name'     => 'English',
            'notation' => 'american',
        ],
        'es' => [
            'name'     => 'Español',
            'notation' => 'latin',
        ],
        'sw' => [
            'name'     => 'Kiswahili',
            'notation' => 'american',
        ],
        'pt' => [
            'name'     => 'Português',
            'notation' => 'latin',
        ],
        'it' => [
            'name'     => 'Italiano',
            'notation' => 'latin',
        ],
        'fr' => [
            'name'     => 'Français',
            'notation' => 'latin',
        ],
    ],

    'voice_wizard'                           => include 'nt.voice_wizard.php',
    'chord_scores'                           => include 'nt.scores.php',
    'mmdb'                                   => 'GeoLite2-Country.mmdb',
    'test_all_transpositions_expected'       => __DIR__ . '/../tests/testAllTranspositions.expected.json',
    'css_cache'                              => '332db24297179e87b53376af59e92869',

    'analytics_id'                           => env('NT_ANALYTICS_ID'),
    'recaptcha_secret'                       => env('NT_RECAPTCHA_SECRET'),

    'software_name'                          => 'Neo-Transposer', /* Deprecated */
    'seo_title_suffix'                       => 'Transpose chords',
    'canonical_domain'                       => 'neo-transposer.com',

    'admins' => [env('NT_ADMIN_USERNAME') => ['ROLE_ADMIN', env('NT_ADMIN_PASSWORD')]],

    'people_range'                           => ['B1', 'B2'],

    // Feature flags
    'hide_second_centered_if_not_equivalent' => false,
    'detailed_feedback'                      => true,
    'audio'                                  => true,
    'show_manifesto'                         => false,
    'show_commitment'                        => false,
    'show_people_compatible_in_report'       => false,
    'disable_recaptcha'                      => true,
];
