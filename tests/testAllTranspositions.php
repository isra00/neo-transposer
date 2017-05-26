<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new \NeoTransposer\NeoApp(
	require __DIR__ . '/../config-dist.php',
	realpath(__DIR__ . '/..')
);

$adminTools = new \NeoTransposer\Model\AdminTools($app);
$output 	= $adminTools->testAllTranspositions();
$result 	= strpos($output, 'SUCCESSFUL');

/** @todo Change <strong> and <em> tags for command line colors */
file_put_contents('php://' . ($result ? 'stdout' : 'stderr'), $output . "\n");

return ($result) ? 0 : 1;