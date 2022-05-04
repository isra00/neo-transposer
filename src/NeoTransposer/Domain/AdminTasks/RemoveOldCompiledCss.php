<?php

namespace NeoTransposer\Domain\AdminTasks;

use Silex\Application;

/**
 * Delete all compiled-*.css files except the one refered to in config.php
 */
class RemoveOldCompiledCss implements AdminTask
{
    protected $dependencyContainer;

    public function __construct(Application $dependencyContainer)
    {
        $this->dependencyContainer = $dependencyContainer;
    }

    public function run(): string
    {
        $serveCssController = new \NeoTransposer\Controllers\ServeCss();
        $fileScheme = $serveCssController->min_file;
        $cssDir = realpath('.' . dirname($fileScheme));
        chdir($cssDir);
        $currentFile = sprintf($fileScheme, $this->dependencyContainer['neoconfig']['css_cache']);

        $allCssFiles = glob(sprintf(basename($fileScheme), '*'));
        $deletedCounter = 0;
        $output = [];
        foreach ($allCssFiles as $file) {
            if ($file != basename($currentFile)) {
                unlink($cssDir . '/' . $file);
                $deletedCounter++;
                $output[] = "Deleted $file";
            }
        }

		if (empty($output))
		{
			$output[] = 'No old compiled CSS found';
		}

		return implode("\n", $output);
    }
}