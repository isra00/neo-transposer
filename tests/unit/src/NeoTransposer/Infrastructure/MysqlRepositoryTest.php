<?php

namespace NeoTransposer\Tests\Infrastructure;

use Faker\Factory;
use Illuminate\Foundation\Testing\TestCase;

class MysqlRepositoryTest extends TestCase
{
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
    }
}
