<?php

use NeoTransposerApp\Domain\AdminTasks\TestAllTranspositions;

require __DIR__ . '/../vendor/autoload.php';

$app = new \NeoTransposerWeb\NeoApp(
	require __DIR__ . '/../apps/NeoTransposerWeb/config.php',
	realpath(__DIR__ . '/..'),
	'dummy'
);

$test = new TestAllTranspositions(
    $app,
    __DIR__ . '/testAllTranspositions.expected.json.json',
    __DIR__ . '/testAllTranspositions.expected.PeopleCompatible.json'
);
$output = $test->run();
$result = strpos($output, 'SUCCESSFUL');

/** @todo Change <strong> and <em> tags for command line colors */
file_put_contents('php://' . ($result ? 'stdout' : 'stderr'), $output . "\n");

exit($result ? 0 : 1);
