<?php

namespace NeoTransposer\Tests\Infrastructure;

use Faker\Factory;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;

class MysqlRepositoryTest extends TestCase
{
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();

        // Change Laravel's DB to the test one.

        config(['database.connections.mysql.database' => getenv('NT_DB_DATABASE_INTEGRATION')]);
        DB::purge('mysql');

        // Reset the static DBAL connection so it picks up the integration database
        $reflection = new \ReflectionClass(\NeoTransposer\Infrastructure\MysqlRepository::class);
        $prop = $reflection->getProperty('dbal');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }
}
