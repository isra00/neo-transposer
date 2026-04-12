<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NeoTransposer\Domain\AdminTasks\TestAllTranspositions;

class TestAllTranspositionsCommand extends Command
{
    protected $signature = 'app:test-all-transpositions';

    protected $description = 'Test all transpositions against expected results';

    public function handle(): int
    {
        $test = new TestAllTranspositions();
        $output = $test->run();

        $successful = str_contains($output, 'SUCCESSFUL');

        // Replace HTML tags with CLI formatting
        $output = str_replace(
            ['<strong>', '</strong>', '<em>', '</em>'],
            ['', '', '', ''],
            $output
        );

        if ($successful) {
            $this->info($output);
        } else {
            $this->error($output);
        }

        return $successful ? self::SUCCESS : self::FAILURE;
    }
}
