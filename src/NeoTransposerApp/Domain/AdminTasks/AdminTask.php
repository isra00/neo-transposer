<?php

namespace NeoTransposerApp\Domain\AdminTasks;

interface AdminTask
{
    public function run(): string;
}
