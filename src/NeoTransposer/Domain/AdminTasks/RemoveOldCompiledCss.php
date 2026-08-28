<?php

namespace NeoTransposer\Domain\AdminTasks;

use App\Http\Controllers\ServeCssController;

final class RemoveOldCompiledCss implements AdminTask
{
    public function run(): string
    {
        $currentFile = basename(sprintf(ServeCssController::MIN_FILE_PATTERN, config('nt.css_cache')));
        $cssDir = dirname(public_path(ServeCssController::MIN_FILE_PATTERN));

        $allCssFiles = glob($cssDir . '/' . basename(sprintf(ServeCssController::MIN_FILE_PATTERN, '*')));
        $output = [];

        foreach ($allCssFiles as $file) {
            if (basename($file) != $currentFile) {
                unlink($file);
                $output[] = 'Deleted ' . basename($file);
            }
        }

        if (empty($output)) {
            $output[] = 'No old compiled CSS found';
        }

        return implode("\n", $output);
    }
}
