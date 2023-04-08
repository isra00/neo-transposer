<?php

namespace NeoTransposer\Tests\Infrastructure;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Faker\Factory;

class MysqlRepositoryTest extends \Codeception\Test\Unit
{
    protected $dbConnection;
    protected $entityManager;

    protected $faker;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->dbConnection = \Doctrine\DBAL\DriverManager::getConnection([
            'driver'   => 'pdo_mysql',
            'host'     => getenv('NT_DB_HOST'),
            'user'     => getenv('NT_DB_USER'),
            'password' => getenv('NT_DB_PASSWORD'),
            'database' => getenv('NT_DB_DATABASE_INTEGRATION'),
            'charset'  => 'utf8',
        ]);
        $this->dbConnection->executeQuery('USE ' . getenv('NT_DB_DATABASE_INTEGRATION'));

        $doctrineConfig = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/../../../../../src'],
            isDevMode: true,
        );

        $this->entityManager = new EntityManager($this->dbConnection, $doctrineConfig);

        $this->faker = Factory::create();

        parent::__construct($name, $data, $dataName);
    }
}
