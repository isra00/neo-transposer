<?php

namespace App\Domain\AdminTasks;

interface AdminTask
{
    public function run(): string;
}