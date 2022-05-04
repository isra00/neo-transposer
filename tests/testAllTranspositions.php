<?php

use NeoTransposer\Domain\AdminTasks\TestAllTranspositions;

require __DIR__ . '/../vendor/autoload.php';

$app = new \NeoTransposer\NeoApp(
	require __DIR__ . '/../config.php',
	realpath(__DIR__ . '/..'),
	'dummy'
);

$test   = new TestAllTranspositions($app);
$output = $test->run();
$result = strpos($output, 'SUCCESSFUL');

/** @todo Change <strong> and <em> tags for command line colors */
file_put_contents('php://' . ($result ? 'stdout' : 'stderr'), $output . "\n");

exit($result ? 0 : 1);