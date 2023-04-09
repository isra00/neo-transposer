<?php

namespace NeoTransposer\Domain\AdminTasks;

use NeoTransposer\NeoApp;

/**
 * Re-compile the CSS file. Will not delete old compiled files.
 */
class RefreshCompiledCss implements AdminTask
{
    public function __construct(protected NeoApp $dependencyContainer)
    {
    }

    public function run(): string
    {
        $serveCssController = new \NeoTransposer\Controllers\ServeCss();
        //The controller returns the redirect URL to the new CSS file
        return 'Generated new file ' . $serveCssController->get($this->dependencyContainer)->getTargetUrl();
    }
}