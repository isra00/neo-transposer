<?php

/*
 * Protection against request floods and scripted logins (incident 2026-11-21).
 *
 * Every protection has a mode, so it can be rolled out without blocking real users:
 *   off     - does nothing
 *   shadow  - only logs "would block" warnings to the Laravel log
 *   enforce - blocks
 */
return [

    // Per-IP throttle on POST /{locale}/login and POST /set-user-data.
    'rate_limit_mode' => env('NT_RATE_LIMIT_MODE', 'shadow'),

    'rate_limits_per_minute' => [
        'login'         => 20,
        'set-user-data' => 30,
    ],
];
