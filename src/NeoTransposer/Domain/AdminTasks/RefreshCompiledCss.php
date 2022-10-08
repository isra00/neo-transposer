<?php

namespace NeoTransposer\Domain\AdminTasks;

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
        //This clearly breaks DDD and hexagonal
        $serveCssController = new \NeoTransposerWeb\Controllers\ServeCss();
        //The controller returns the redirect URL to the new CSS file
        return 'Generated new file ' . $serveCssController->get($this->dependencyContainer)->getTargetUrl();
    }
}
