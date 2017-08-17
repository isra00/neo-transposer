<?php

// backward compatibility
// https://stackoverflow.com/questions/42811164/class-phpunit-framework-testcase-not-found
if (!class_exists('\PHPUnit\Framework\TestCase') &&
    class_exists('\PHPUnit_Framework_TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

include __DIR__ . '/../vendor/autoload.php';