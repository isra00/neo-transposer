<?php

namespace NeoTransposer\Domain\AdminTasks;

interface AdminTask
{
    public function run(): string;
}
