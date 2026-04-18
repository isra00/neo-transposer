<?php

namespace NeoTransposer\Domain\AdminTasks;

use App\Http\Controllers\ServeCssController;

final class RefreshCompiledCss implements AdminTask
{
    public function run(): string
    {
        $serveCssController = new ServeCssController();
        return 'Generated new file ' . $serveCssController->get()->getTargetUrl();
    }
}
