<?php

namespace NeoTransposerApp\Domain\AdminTasks;

use Silex\Application;

/**
 * Re-compile the CSS file. Will not delete old compiled files.
 */
class RefreshCompiledCss implements AdminTask
{
    protected $dependencyContainer;

    public function __construct(Application $dependencyContainer)
    {
        $this->dependencyContainer = $dependencyContainer;
    }

    public function run(): string
    {
        $serveCssController = new \NeoTransposerApp\Controllers\ServeCss();
        //The controller returns the redirect URL to the new CSS file
        return 'Generated new file ' . $serveCssController->get($this->dependencyContainer)->getTargetUrl();
    }
}
