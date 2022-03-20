<?php

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

$app = new \NeoTransposer\NeoApp(
	$config,
	realpath(__DIR__ . '/..'),
	'dummy'
);

$adminTools = new \NeoTransposer\Model\AdminTools($app);
$output 	= $adminTools->testAllTranspositions();
$result 	= strpos($output, 'SUCCESSFUL');

/** @todo Change <strong> and <em> tags for command line colors */
file_put_contents('php://' . ($result ? 'stdout' : 'stderr'), $output . "\n");

exit($result ? 0 : 1);